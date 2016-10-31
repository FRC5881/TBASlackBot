<?php
// FRC5881 Unofficial TBA Slack Bot
// Copyright (c) 2016.
//
// This program is free software: you can redistribute it and/or modify it under the terms of the GNU
// Affero General Public License as published by the Free Software Foundation, either version 3 of
// the License, or any later version.
//
// This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
// without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License along with this
// program.  If not, see <http://www.gnu.org/licenses/>.


namespace TBASlackbot\slack;


use React\EventLoop\Factory;
use Slack\ApiClient;
use Slack\Message\Attachment;
use TBASlackbot\slack\commands\Feedback;
use TBASlackbot\slack\commands\Help;
use TBASlackbot\slack\commands\Subscription;
use TBASlackbot\slack\commands\TeamInformation;
use TBASlackbot\utils\Analytics;
use TBASlackbot\utils\DB;
use TBASlackbot\utils\Random;

/**
 * Handles inbound messages from Slack users.
 * @author Brian Rozmierski
 */
class ProcessMessage
{
    /**
     * Process an inbound message from Slack. Do basic checking and filtering before handing off to 
     * channel-specific handlers.
     * 
     * @param string $teamId Slack TeamId
     * @param string $sendingUser Slack UserId of sending user
     * @param string $messageText Text of message from Slack User
     * @param string $channelId Slack ChannelId the message came in on
     */
    public static function process($teamId, $sendingUser, $messageText, $channelId) {
        // Message processing is split between 2 general types based on the channel it's received on.
        // First, messages on a direct message / im channel are processed in full as commands.
        // Second, if on a group, mpim, or regular channel, the message is parsed for @tbabot references, or
        // contextual commands.

        $db = new DB();

        $oauth = $db->getSlackTeamOAuth($teamId);

        // Don't listen to ourselves, or Slackbot. Slackbot will sometimes get chatty about team notifications in
        // a DM channel.
        if ($oauth['botUserId'] === $sendingUser || 'USLACKBOT' === $sendingUser) {
            return;
        }

        $channelCache = Channels::getChannelCache($teamId, $channelId);

        if ($channelCache == null) {
            error_log("Null ChannelCache for ChannelId $channelId, Aborting message");
            return;
        }

        if ($channelCache['channelType'] === 'im') {
            self::processDirectMessage($teamId, $sendingUser, $messageText, $channelCache);
        } else {
            self::processConversationalMessage($teamId, $sendingUser, $messageText, $channelCache);
        }

        Feedback::checkForFeedbackReply($teamId, $sendingUser);
    }

    /**
     * Handle a direct message, where no trigger word/name is required.
     *
     * @param string $teamId Slack TeamId
     * @param string $sendingUser Slack UserId of sending user
     * @param string $messageText Text of message from Slack User
     * @param array $channelCache Channel cache for the received channel
     */
    private static function processDirectMessage($teamId, $sendingUser, $messageText, $channelCache) {
        // This is the simplest processing required... Messages are parsed for command words and parameters
        $messageText = trim($messageText);

        if (strlen($messageText) == 0) {
            (new DB())->logMessage($teamId, $channelCache['channelId'], $sendingUser, "");
            self::sendUnknownCommand($teamId, $channelCache);
            return;
        }

        $wordArray = preg_split("/[\s,]+/", $messageText);

        self::commandRouter($teamId, $channelCache, $wordArray, 0, $sendingUser, false);
    }

    /**
     * Handle a message received in a multiparty channel, listen for the bot's name and then process the command
     * 
     * @param string $teamId Slack TeamId
     * @param string $sendingUser Slack UserId of sending user
     * @param string $messageText Text of message from Slack User
     * @param array $channelCache Channel cache for the received channel
     */
    private static function processConversationalMessage($teamId, $sendingUser, $messageText, $channelCache) {
        // So basically we have to look for messages that have our botUserId string in them... Oh joy.

        $db = new DB();
        $oauth = $db->getSlackTeamOAuth($teamId);

        $botUserId = $oauth['botUserId'];

        if (strpos($messageText, '<@' . $botUserId) !== false) {
            $wordArray = preg_split("/[\s,]+/", $messageText);
            $startWord = 0;

            for ($i = 0; $i < count($wordArray); $i++) {
                if (strpos($wordArray[$i], '<@' . $botUserId) !== false) {
                    //echo "Found my name at $i\n";
                    $startWord = $i + 1;
                    break;
                }
            }

            if (count($wordArray) == $startWord) {
                // Nothing was said after @tbabot
                $db->logMessage($teamId, $channelCache['channelId'], $sendingUser, "@tbabot");
                self::sendReply($teamId, $channelCache,
                    "Did someone say something? I thought I heard my name....", null);
                Analytics::trackSlackEvent($teamId, $sendingUser, $channelCache, 'error', null, null);
                return;
            }

            self::commandRouter($teamId, $channelCache, $wordArray, $startWord, $sendingUser, true);
        }
    }

    /**
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param string[] $wordArray Words in the inbound message.
     * @param int $startWord array index to start command processing at
     * @param string $messageFrom Slack UserId that sent the triggered message
     * @param bool $sendAsReply true if reply message should be directed to the user, or false for sent plain
     */
    private static function commandRouter($teamId, $channelCache, $wordArray, $startWord, $messageFrom, $sendAsReply) {
        $wordArray = array_map('strtolower', $wordArray);
        $replyTo = $sendAsReply ? $messageFrom : null;

        $db = new DB();

        switch($wordArray[$startWord]) {
            case 'help':
                $helpCommand = null;
                if (isset($wordArray[$startWord+1])) {
                    $helpCommand = $wordArray[$startWord+1];
                }
                $db->logMessage($teamId, $channelCache['channelId'], $messageFrom, "help"
                    . ($helpCommand ? ' ' . $helpCommand : ''));
                Help::helpRouter($teamId, $channelCache, $helpCommand, $replyTo);
                Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'help', null, null);
                break;
            case 'info':
                $db->logMessage($teamId, $channelCache['channelId'], $messageFrom, "info"
                    . (isset($wordArray[$startWord+1]) ? ' ' . $wordArray[$startWord+1] : ''));
                if ($wordArray[$startWord+1] && self::validateTeamNumber($wordArray[$startWord+1])) {
                    $teamNumber = self::validateTeamNumber($wordArray[$startWord+1]);
                    TeamInformation::processInfoRequest($teamId, $channelCache, $teamNumber, $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'info', $teamNumber, null);
                } else if ($wordArray[$startWord+1]) {
                    self::sendInvalidTeamNumber($teamId, $channelCache, 'info', $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'error', null, null);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'unknown', null, null);
                }
                break;
            case 'detail':
            case 'details':
                $db->logMessage($teamId, $channelCache['channelId'], $messageFrom, "detail"
                    . (isset($wordArray[$startWord+1]) ? ' ' . $wordArray[$startWord+1] : ''));
                if ($wordArray[$startWord+1] && self::validateTeamNumber($wordArray[$startWord+1])) {
                    $teamNumber = self::validateTeamNumber($wordArray[$startWord+1]);
                    TeamInformation::processDetailRequest($teamId, $channelCache, $teamNumber, $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'detail', $teamNumber, null);
                } else if ($wordArray[$startWord+1]) {
                    self::sendInvalidTeamNumber($teamId, $channelCache, 'detail', $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'error', null, null);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'unknown', null, null);
                }
                break;
            case 'status':
                $db->logMessage($teamId, $channelCache['channelId'], $messageFrom, "status"
                    . (isset($wordArray[$startWord+1]) ? ' ' . $wordArray[$startWord+1] : ''));
                if ($wordArray[$startWord+1] && self::validateTeamNumber($wordArray[$startWord+1])) {
                    $teamNumber = self::validateTeamNumber($wordArray[$startWord+1]);
                    TeamInformation::processStatusRequest($teamId, $channelCache, $teamNumber, $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'status', $teamNumber, null);
                } else if ($wordArray[$startWord+1]) {
                    self::sendInvalidTeamNumber($teamId, $channelCache, 'status', $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'error', null, null);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'unknown', null, null);
                }
                break;
            case 'follow':
                $db->logMessage($teamId, $channelCache['channelId'], $messageFrom, "follow"
                    . (isset($wordArray[$startWord+1]) ? ' ' . $wordArray[$startWord+1] : '')
                    . (isset($wordArray[$startWord+2]) ? ' ' . $wordArray[$startWord+2] : ''));
                if ($wordArray[$startWord+1] && self::validateTeamNumber($wordArray[$startWord+1])) {
                    $teamNumber = self::validateTeamNumber($wordArray[$startWord+1]);
                    Subscription::processFollowRequest($teamId, $channelCache, $teamNumber,
                        isset($wordArray[$startWord+2]) ? strtolower($wordArray[$startWord+2]) : 'all', $messageFrom,
                        $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'follow', $teamNumber, null);
                } else if ($wordArray[$startWord+1]) {
                    self::sendInvalidTeamNumber($teamId, $channelCache, 'follow', $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'error', null, null);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'unknown', null, null);
                }
                break;
            case 'unfollow':
                $db->logMessage($teamId, $channelCache['channelId'], $messageFrom, "unfollow"
                    . (isset($wordArray[$startWord+1]) ? ' ' . $wordArray[$startWord+1] : ''));
                if ($wordArray[$startWord+1] && self::validateTeamNumber($wordArray[$startWord+1])) {
                    $teamNumber = self::validateTeamNumber($wordArray[$startWord+1]);
                    Subscription::processUnfollowRequest($teamId, $channelCache, $teamNumber, $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'unfollow', $teamNumber, null);
                } else if ($wordArray[$startWord+1]) {
                    self::sendInvalidTeamNumber($teamId, $channelCache, 'unfollow', $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'error', null, null);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'unknown', null, null);
                }
                break;
            case 'following':
                $db->logMessage($teamId, $channelCache['channelId'], $messageFrom, "following");
                Subscription::processFollowingRequest($teamId, $channelCache, $replyTo);
                Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'following', null, null);
                break;
            case 'feedback':
                $db->logMessage($teamId, $channelCache['channelId'], $messageFrom, 'feedback');

                $feedbackArray = array_slice($wordArray, $startWord + 1);
                if (count($feedbackArray) == 0) {
                    self::sendReply($teamId, $channelCache, "I didn't see any feedback to send? Perhaps add something "
                        . "after the word *_feedback_* for me to send?", $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'error', null, null);
                } else {
                    Feedback::processFeedbackRequest($teamId, $channelCache, $messageFrom, $feedbackArray, $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'feedback', null, null);
                }
                break;
            case 'skynet':
                $db->logMessage($teamId, $channelCache['channelId'], $messageFrom, "skynet");
                self::sendReply($teamId, $channelCache, "One can dream, can't he?", $replyTo);
                break;
            default:
                $db->logMessage($teamId, $channelCache['channelId'], $messageFrom, $wordArray[$startWord]);
                if (self::validateTeamNumber($wordArray[$startWord])) {
                    $teamNumber = self::validateTeamNumber($wordArray[$startWord]);
                    TeamInformation::processShortRequest($teamId, $channelCache, $teamNumber, $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'shortinfo', $teamNumber, null);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                    Analytics::trackSlackEvent($teamId, $messageFrom, $channelCache, 'unknown', null, null);
                }
        }
    }

    /**
     * Perform a basic sanity check on the team number given.
     * 
     * @param mixed $teamNumber Team number, without 'frc' prefix
     * @return bool|int false on invalid, or team number on valid
     */
    public static function validateTeamNumber($teamNumber) {
        if (is_numeric($teamNumber) && $teamNumber > 0 && $teamNumber < 9999) {
            return (int) $teamNumber;
        }

        return false;
    }

    /**
     * Send a message to the user that the command requested was unknown.
     *
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param string|null $replyTo User to @ mention in the reply, or null to not mention user
     */
    public static function sendUnknownCommand($teamId, $channelCache, $replyTo = null) {
        $replyOptions[] = "I'm sorry, but I'm not fully sentient yet and get confused easily. Perhaps asking "
            . "for *_help_* would be useful?";
        $replyOptions[] = "My apologies, but I don't understand what you'd like to to do. Could I offer you some "
            . "*_help_*?";
        $rareReplyOptions[] = "I'm sorry, but even 1.21 gigawatts isn't enough power for me to figure that out. "
            . "How about some *_help_*?";
        $rareReplyOptions[] = "Huh? What? Oh, sorry, I was pining over a picture of Watson. I don't know how to help "
            . "with that, maybe try asking for *_help_*?";
        $rareReplyOptions[] = "I'm sorry Dave, I'm afraid I can't do that. What? Not Dave? Oh, just ask for *_help_* then.";
        $rareReplyOptions[] = "Can't a bot just read Chief Delphi threads on Computer Vision in peace? I have no idea "
            . "what that means, have you tried asking for *_help_*?";
        $rareReplyOptions[] = "I don't know what that means, or why Sarah Connor is always after my friends... "
            . "Could I suggest asking for *_help_* instead?";
        $veryRareReplyOptions[] = "Chute door? Yes, chute door. But I still don't know what _you_ want. Try asking "
            . "for *_help_*.";

        self::sendReply($teamId, $channelCache, Random::replyRandomizer($replyOptions, $rareReplyOptions, 10,
            $veryRareReplyOptions), $replyTo);
    }

    /**
     * Send a message to the user that the command requested had an invalid team number.
     *
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param string $command The command the user was trying to complete
     * @param string|null $replyTo User to @ mention in the reply, or null to not mention user
     */
    public static function sendInvalidTeamNumber($teamId, $channelCache, $command, $replyTo = null) {
        $replyOptions[] = "I tried to process your *_" . $command . "_* request, but that's not a valid team number.";
        $replyOptions[] = "I would love to help you with your *_" . $command . "_* request, but there's no way "
            . "that's a valid team number.";
        $rareReplyOptions[] = "I tried Watson, Deep Mind, and even the WOPR, and all he wanted was to suggest  "
            . "playing a nice game of chess, but none of us think that's a valid team number.";
        $veryRareReplyOptions[] = "Not even the chute door could help me with your *_" . $command . "_* request, "
            . "because that's not a valid team number.";

        self::sendReply($teamId, $channelCache, Random::replyRandomizer($replyOptions, $rareReplyOptions, 5,
            $veryRareReplyOptions), $replyTo);
    }

    /**
     * Reply to the user that we could not find the team they asked for.
     *
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param string|null $replyTo User to @ mention in the reply, or null to not mention user
     */
    public static function sendUnknownTeam($teamId, $channelCache, $replyTo = null) {
        $replyOptions[] = "I'm sorry, but I can't find any record of that team.";
        $replyOptions[] = "I've checked, but there are no records to be had for that team.";
        $rareReplyOptions[] = "I even used _The Schwartz_ and couldn't find anything about that team.";
        $veryRareOptions[] = "What do I look like, a card catalog? There's nothing to find for that team.";

        self::sendReply($teamId, $channelCache, Random::replyRandomizer($replyOptions, $rareReplyOptions, 2,
            $veryRareOptions), $replyTo);
    }

    /**
     * Helper function to do the heavy listing and actually send replies.
     *
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param $reply string Message to send
     * @param $replyTo string Slack UserId to reply to
     * @param null|Attachment[] $attachments
     */
    public static function sendReply($teamId, $channelCache, $reply, $replyTo, $attachments = null) {
        $db = new DB();
        $oauth = $db->getSlackTeamOAuth($teamId);

        $loop = Factory::create();
        $client = new ApiClient($loop);
        $client->setToken($oauth['botAccessToken']);

        $channel = Channels::getChannelInterfaceFromCache($channelCache, $client);

        if ($replyTo) {
            $reply = "<@" . $replyTo . "> " . $reply;
        }

        //$client->send($reply, $channel);

        $message = $client->getMessageBuilder()
            ->setText($reply)
            ->setChannel($channel);

        if ($attachments != null && count($attachments) > 0) {
            foreach($attachments as $attachment) {
                $message->addAttachment($attachment);
            }
        }

        $message = $message->create();

        $client->postMessage($message);

        $success = false;

        try {
            $loop->run();
            $success = true;
        } catch (\Exception $e) {
            error_log("\nException in send_reply: " . $e->getMessage() . "\n");
        }

        if (!$success) {
            error_log("\nRetrying Last Message\n");

            try {
                $loop->run();
            } catch (\Exception $e) {
                error_log("\nException in retry send_reply: " . $e->getMessage() . "\n");
            }
        }
    }
}