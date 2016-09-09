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


namespace TBASlackbot\slack\commands;

use TBASlackbot\slack\ProcessMessage;
use TBASlackbot\tba\TBAClient;
use TBASlackbot\utils\DB;
use TBASlackbot\utils\Random;

/**
 * Handles responses to the various subscription-related commands.
 * @package TBASlackbot\slack\commands
 */
class Subscription
{
    /**
     * Subscribe a channel to updates from an FRC team at competition.
     *
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param int $teamStatusRequestedFor FRC team number
     * @param string $level Subscription level, 'all', 'result' or 'summary' only
     * @param string $requestedBy Slack UserId that requested the subscription
     * @param string|null $replyTo User to @ mention in the reply, or null to not mention user
     */
    public static function processFollowRequest($teamId, $channelCache, $teamStatusRequestedFor, $level = 'all',
                                                 $requestedBy, $replyTo = null) {
        if ($level !='all' && $level != 'result' && $level != 'summary') {
            ProcessMessage::sendUnknownCommand($teamId, $channelCache);
        }

        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);

        $team = $tba->getTeam('frc' . $teamStatusRequestedFor);

        if ($team == null) {
            ProcessMessage::sendUnknownTeam($teamId, $channelCache);
            return;
        }

        $db = new DB();
        $db->setSlackTeamSubscription($teamId, $channelCache['channelId'], $team->getTeamNumber(), $level,
            $requestedBy);

        $updateType = null;
        if ($level === 'all') {
            $updateType = 'all competition updates';
        } else if ($level === 'result') {
            $updateType = 'competition match updates';
        } else if ($level === 'summary') {
            $updateType = 'summary competition updates';
        }

        $replyOptions[] = "Got it!";
        $replyOptions[] = "Ok, boss!";
        $replyOptions[] = "I'm on it!";
        $rareReplyOptions[] = "Printing the punch card!";
        $rareReplyOptions[] = "Etching the stone tablet!";


        ProcessMessage::sendReply($teamId, $channelCache, Random::replyRandomizer($replyOptions, $rareReplyOptions)
            . " Now watching for $updateType for team "
            . $team->getTeamNumber() . " (" . $team->getNickname() . ") and will update this channel as they come in. "
            . "To stop, just ask me to *_unfollow " . $team->getTeamNumber() . "_* from this channel.", $replyTo);
    }

    /**
     * Unsubscribe a channel to an FRC team's competition updates.
     *
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param int $teamStatusRequestedFor FRC team number
     * @param string|null $replyTo User to @ mention in the reply, or null to not mention user
     */
    public static function processUnfollowRequest($teamId, $channelCache, $teamStatusRequestedFor, $replyTo = null) {
        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);
        $db = new DB();

        $sub = $db->getSlackTeamSubscription($teamId, $channelCache['channelId'], $teamStatusRequestedFor);

        if ($sub) {
            $team = $tba->getTeam('frc' . $teamStatusRequestedFor);

            $db->deleteSlackTeamSubscription($teamId, $channelCache['channelId'], $teamStatusRequestedFor);

            $replyOptions[] = ":cry: I'm not following team " . $team->getTeamNumber()
                . " (" . $team->getNickname() . ") for you any longer.";
            $replyOptions[] = ":sob: I'm not following team " . $team->getTeamNumber()
                . " (" . $team->getNickname() . ") for you any longer. I hope they don't get lonely.";
            $rareReplyOptions[] = "I'm not following team " . $team->getTeamNumber()
                . " (" . $team->getNickname() . ") for you any longer. And I had just finished sorting the "
                . "punch cards.";

            ProcessMessage::sendReply($teamId, $channelCache, Random::replyRandomizer($replyOptions, $rareReplyOptions),
                $replyTo);
        } else {
            $replyOptions[] = "Uh... :worried: Sorry boss... I.. uh... Can't find a subscription "
                . "for team $teamStatusRequestedFor for this channel. I really hope I didn't lose it. Maybe check "
                . "and see who I'm *_following_* in this channel?";
            $replyOptions[] = "One second... Uh... :worried: I.. uh... Can't find a subscription "
                . "for team $teamStatusRequestedFor for this channel. I usually have a good memory, maybe check "
                . "and see who I'm *_following_* in this channel?";
            $rareReplyOptions[] = "I've scoured the matrix and can't find any subscription for $teamStatusRequestedFor "
                . "anywhere. Have you tried asking your operator who I'm supposed to be *_following_*?";

            ProcessMessage::sendReply($teamId, $channelCache, Random::replyRandomizer($replyOptions, $rareReplyOptions),
                $replyTo);
        }
    }

    /**
     * List the teams currently being followed in the channel.
     *
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param string|null $replyTo User to @ mention in the reply, or null to not mention user
     */
    public static function processFollowingRequest($teamId, $channelCache, $replyTo = null) {
        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);
        $db = new DB();

        $subs = $db->getSlackTeamSubscriptions($teamId, $channelCache['channelId']);

        if ($subs) {
            $replyOptions[] = "Lets see here, according to this punch card, I'm following:";
            $replyOptions[] = "Lets see here, according to this stone tablet, I'm following:";
            $replyOptions[] = "Lets see here, according to this reel-to-reel tape drive, I'm following:";
            $reply = Random::replyRandomizer($replyOptions, array());

            foreach ($subs as $sub) {
                $team = $tba->getTeam('frc' . $sub['frcTeam']);
                $reply .= "\n• Team " . $team->getTeamNumber() . " (" . $team->getNickname() . ") - ";

                if ($sub['subscriptionType'] === 'all') {
                    $reply .= 'All competition updates';
                } else if ($sub['subscriptionType'] === 'result') {
                    $reply .= 'Competition match updates';
                } else if ($sub['subscriptionType'] === 'summary') {
                    $reply .= 'Summary competition updates';
                }
            }
            ProcessMessage::sendReply($teamId, $channelCache, $reply, $replyTo);
        } else {
            $replyOptions[] = "I'm not following any teams in this channel. If you'd like me to, there's information "
                . "available if you ask for *_help subscribe_*.";
            $rareReplyOptions[] = "What teams am I following? It's a short list, because there aren't any. Try asking "
                . "*_help subscribe_* for how to get started.";
            $veryRareReplyOptions[] = "It gets boring around here with nothing to do, no one to keep "
                . "track of.... I just sit here and spin my CPU cycles hoping, begging, somebody will come by and "
                . "ask me to *_follow [team number]_* and make me useful. _Don't we all just want to be useful?_";
            ProcessMessage::sendReply($teamId, $channelCache, Random::replyRandomizer($replyOptions, $rareReplyOptions, 5,
                $veryRareReplyOptions), $replyTo);
        }
    }
}