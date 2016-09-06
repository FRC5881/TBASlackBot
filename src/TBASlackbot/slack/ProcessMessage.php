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

/**
 * Handles inbound messages from Slack users
 * @author Brian Rozmierski
 */

namespace TBASlackbot\slack;


use React\EventLoop\Factory;
use Slack\ApiClient;
use Slack\Message\Attachment;
use TBASlackbot\tba\TBAClient;
use TBASlackbot\utils\DB;

class ProcessMessage
{
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
    }

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
                    echo "Found my name at $i\n";
                    $startWord = $i + 1;
                    break;
                }
            }

            if (count($wordArray) == $startWord) {
                // Nothing was said after @tbabot
                $db->logMessage($teamId, $channelCache['channelId'], $sendingUser, "@tbabot");
                self::sendReply($teamId, $channelCache,
                    "Did someone say something? I thought I heard my name....", null);
            }

            self::commandRouter($teamId, $channelCache, $wordArray, $startWord, $sendingUser, true);
        }
    }

    /**
     * @param $teamId string Slack TeamId
     * @param $channelCache array Channel Cache as stored by the DB
     * @param $wordArray string[] Words in the inbound message.
     * @param $startWord int array index to start command processing at
     * @param $messageFrom string Slach UserId that sent the triggered message
     * @param $sendAsReply bool true if reply message should be directed to the user, or false for sent plain
     */
    private static function commandRouter($teamId, $channelCache, $wordArray, $startWord, $messageFrom, $sendAsReply) {
        $wordArray = array_map('strtolower', $wordArray);
        $replyTo = $sendAsReply ? $messageFrom : null;

        switch($wordArray[$startWord]) {
            case 'help':
                $helpCommand = null;
                if (isset($wordArray[$startWord+1])) {
                    $helpCommand = $wordArray[$startWord+1];
                }
                (new DB())->logMessage($teamId, $channelCache['channelId'], $messageFrom, "help"
                    . ($helpCommand ? ' ' . $helpCommand : ''));
                self::helpRouter($teamId, $channelCache, $helpCommand, $replyTo);
                break;
            case 'info':
                (new DB())->logMessage($teamId, $channelCache['channelId'], $messageFrom, "info"
                    . (isset($wordArray[$startWord+1]) ? ' ' . $wordArray[$startWord+1] : ''));
                if ($wordArray[$startWord+1] && self::validateTeamNumber($wordArray[$startWord+1])) {
                    self::processInfoRequest($teamId, $channelCache,
                        self::validateTeamNumber($wordArray[$startWord+1]), $replyTo);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                }
                break;
            case 'betainfo':
                (new DB())->logMessage($teamId, $channelCache['channelId'], $messageFrom, "info"
                    . (isset($wordArray[$startWord+1]) ? ' ' . $wordArray[$startWord+1] : ''));
                if ($wordArray[$startWord+1] && self::validateTeamNumber($wordArray[$startWord+1])) {
                    self::processBetaInfoRequest($teamId, $channelCache,
                        self::validateTeamNumber($wordArray[$startWord+1]), $replyTo);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                }
                break;
            case 'detail':
            case 'details':
                (new DB())->logMessage($teamId, $channelCache['channelId'], $messageFrom, "details"
                    . (isset($wordArray[$startWord+1]) ? ' ' . $wordArray[$startWord+1] : ''));
                if ($wordArray[$startWord+1] && self::validateTeamNumber($wordArray[$startWord+1])) {
                    self::processDetailRequest($teamId, $channelCache,
                        self::validateTeamNumber($wordArray[$startWord+1]), $replyTo);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                }
                break;
            case 'status':
                (new DB())->logMessage($teamId, $channelCache['channelId'], $messageFrom, "status"
                    . (isset($wordArray[$startWord+1]) ? ' ' . $wordArray[$startWord+1] : ''));
                if ($wordArray[$startWord+1] && self::validateTeamNumber($wordArray[$startWord+1])) {
                    self::processStatusRequest($teamId, $channelCache,
                        self::validateTeamNumber($wordArray[$startWord+1]), $replyTo);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                }
                break;
            case 'follow':
                (new DB())->logMessage($teamId, $channelCache['channelId'], $messageFrom, "follow"
                    . (isset($wordArray[$startWord+1]) ? ' ' . $wordArray[$startWord+1] : '')
                    . (isset($wordArray[$startWord+2]) ? ' ' . $wordArray[$startWord+2] : ''));
                if ($wordArray[$startWord+1] && self::validateTeamNumber($wordArray[$startWord+1])) {
                    self::processFollowRequest($teamId, $channelCache,
                        self::validateTeamNumber($wordArray[$startWord+1]),
                        isset($wordArray[$startWord+2]) ? strtolower($wordArray[$startWord+2]) : 'all', $messageFrom,
                        $replyTo);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                }
                break;
            case 'unfollow':
                (new DB())->logMessage($teamId, $channelCache['channelId'], $messageFrom, "unfollow"
                    . (isset($wordArray[$startWord+1]) ? ' ' . $wordArray[$startWord+1] : ''));
                if ($wordArray[$startWord+1] && self::validateTeamNumber($wordArray[$startWord+1])) {
                    self::processUnfollowRequest($teamId, $channelCache,
                        self::validateTeamNumber($wordArray[$startWord+1]), $replyTo);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                }
                break;
            case 'following':
                (new DB())->logMessage($teamId, $channelCache['channelId'], $messageFrom, "following");
                self::processFollowingRequest($teamId, $channelCache, $replyTo);
                break;
            case 'skynet':
                (new DB())->logMessage($teamId, $channelCache['channelId'], $messageFrom, "skynet");
                self::sendReply($teamId, $channelCache, "One can dream, can't he?", $replyTo);
                break;
            default:
                (new DB())->logMessage($teamId, $channelCache['channelId'], $messageFrom, $wordArray[$startWord]);
                if (self::validateTeamNumber($wordArray[$startWord])) {
                    self::processShortRequest($teamId, $channelCache,
                        self::validateTeamNumber($wordArray[$startWord]), $replyTo);
                } else {
                    self::sendUnknownCommand($teamId, $channelCache, $replyTo);
                }
        }
    }

    private static function validateTeamNumber($teamNumber) {
        if (is_numeric($teamNumber) && $teamNumber > 0 && $teamNumber < 9999) {
            return (int) $teamNumber;
        }

        return false;
    }

    private static function sendUnknownCommand($teamId, $channelCache, $replyTo = null) {
        self::sendReply($teamId, $channelCache, "I'm sorry, but I'm not fully sentient yet and get confused easily. "
            . "Perhaps asking for *_help_* would be useful?", $replyTo);
    }

    private static function sendUnknownTeam($teamId, $channelCache, $replyTo = null) {
        self::sendReply($teamId, $channelCache, "I'm sorry, but I can't find any record of that team.", $replyTo);
    }

    private static function helpRouter($teamId, $channelCache, $helpCommand, $replyTo = null) {
        if ($helpCommand) {
            switch($helpCommand) {
                case 'channel':
                case 'channels':
                    $helpText = "When I first join your team, team members can contact me via direct message and "
                        . "issue commands and subscribe to team updates privately. However, one of my more powerful "
                        . "features allows a team admin, or permitted user to *_/invite_* me to another channel.\n\n"
                        . "Once I'm invited to the channel users can interact with me, in conversation, by linking "
                        . "my name. I'll reply to the user that asked me, but I'll send it to the whole channel so "
                        . "everyone can see.\n\n"
                        . "You can even ask me _@tbabot_ status 5881 in the middle of a sentence and I'll answer.\n\n"
                        . "If you want me to leave a channel, just send a *_/remove_* command to the channel.\n\n"
                        . "*Warning* for this to work Slack sends me every message in every channel I'm invited to. "
                        . "Right now my programmers are working hard on testing and making sure I'm fit as a fiddle, "
                        . "so channel messages are logged. In the future I will log only the channel, and the portion "
                        . "of the message I thought was a command. I don't understand anything but my commands, and "
                        . "my programmers don't want to read your private chats either.";
                    self::sendReply($teamId, $channelCache, $helpText, $replyTo);
                    break;
                case 'subscribe':
                case 'follow':
                case 'unfollow':
                    $helpText = "You can subscribe, or follow, a team through their events live, and as I get updates "
                        . "from The Blue Alliance, I'll parse them and send them long to the channel I was asked to "
                        . "follow them from. Here are the commands regarding following a team:";

                    $attachment =  new Attachment('Following a Team for Live Updates', '*_follow [team number]_* '
                        . '_[*all* | result | summary]_ - I\'ll listen for competition updates for the team, '
                        . 'and by default tell you everything I hear, including match schedule updates and alerts for '
                        . 'upcoming matches, match result, and standing updates. If you want me to be less chatty, '
                        . 'add the word _result_ and I\'ll only send updates after matches, or for even less, add '
                        . '_summary_ to your request and I\'ll only send you summary updates at the end of '
                        . 'competition.');
                    $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
                    $attachments[] = $attachment;

                    $attachment =  new Attachment('Un-Following a Team', '*_unfollow [team number]_* - I\'ll stop '
                        . 'sending competition updates for this team. If you need to change how chatty '
                        . 'I am, instead of _unfollowing_ just send me another _follow_ request and I\'ll update '
                        . 'the existing subscription.');
                    $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
                    $attachments[] = $attachment;

                    $attachment =  new Attachment('Listing Followed Teams',
                        '*_following_* - I\'ll tell you which teams I\'m following for you in the channel.');
                    $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
                    $attachments[] = $attachment;

                    self::sendReply($teamId, $channelCache, $helpText, $replyTo, $attachments);
                    break;
                case 'changelog':
                    $helpText = "I'm always getting upgrades, changes, and improvements. Most of the time my users "
                        . "never notice, but sometimes my commands change, or I get new features users might want "
                        . "to know about. You'll find the recent changes below:";

                    $attachment =  new Attachment('Improvements & Feedback Requested - Sept 6, 2016',
                        '*_detail_* now links the event title to the TBA Event page for further analysis. A new info '
                        . 'style update is available for testing, and your feedback is requested. Try '
                        . '*_betainfo [team number]_* and let us know which you prefer.' . "\n" . 'Some bugs were '
                        . 'also mercilessly squashed - some events w/o a short name would be listed as blanks, all '
                        . 'rankings are now event-wide, not just qualification-only, and TBABot now ignores '
                        . 'Slackbot\'s DMs when team-wide announcements are made. (Let\'s not not a bot war, now '
                        . 'shall we?)');
                    $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
                    $attachments[] = $attachment;

                    $attachment =  new Attachment('Command Changes - Sept 5, 2016', 'Changes have been made to the '
                        . '*_info_* command such that it now has additional information. Also, sending a team number '
                        . 'in place of a command will now return the short info form that *_info_* used to provide');
                    $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
                    $attachments[] = $attachment;
                    self::sendReply($teamId, $channelCache, $helpText, $replyTo, $attachments);
                    break;
                default:
                    self::sendHelpAttachment($teamId, $channelCache, $replyTo);
            }
        } else {
            self::sendHelpAttachment($teamId, $channelCache, $replyTo);
        }
    }

    private static function sendHelpAttachment($teamId, $channelCache, $replyTo = null) {
        /**
         * @var Attachment[]
         */
        $attachments = array();

        $attachment = new Attachment('Basic Team Information',
            '*_info [team number]_* - I\'ll lookup name and location information for the team number you ask for.');
        $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
        $attachments[] = $attachment;

        $attachment =  new Attachment('Detailed Team Information', '*_detail [team number]_* - I\'ll give you a '
            . 'detailed view of the team, including location, website, rookie year, a summary of their official '
            . 'match record for the current season, and a summary of this season\'s competitions.');
        $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
        $attachments[] = $attachment;

        $attachment =  new Attachment('Team Competition Status', '*_status [team number]_* - I\'ll give you the '
            . 'current competition status and ranking for the team number you ask for. If the team isn\'t '
            . 'competing I\'ll give you information on their last competition result as well as when their '
            . 'next competition is.');
        $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
        $attachments[] = $attachment;

        $attachment =  new Attachment('Following a Team for Live Updates', 'More information about following, or '
            . 'subscribing, to a team\'s updates is available by asking me *_help subscribe_*.');
        $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
        $attachments[] = $attachment;

        $attachment =  new Attachment('What\'s new or changed?', 'I\'m always getting updates and improvements. '
            . 'If you\'d like to know more about them, ask about *_help changelog_*.');
        $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
        $attachments[] = $attachment;

        self::sendReply($teamId, $channelCache, "Oh the things I can do! In a direct message, you can just send me "
            . "one of the commands. If you're in a channel with multiple people I'll listen for my name "
            . "to be mentioned first.\n", $replyTo, $attachments);
    }

    private static function processShortRequest($teamId, $channelCache, $teamInfoRequestedFor, $replyTo = null) {
        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);

        $teamInfoRequestedFor = 'frc' . $teamInfoRequestedFor;

        $team = $tba->getTeam($teamInfoRequestedFor);

        if ($team == null) {
            self::sendUnknownTeam($teamId, $channelCache);
            return;
        }

        $status = $tba->getTBAStatus();
        $district = $team->getDistrict($status->getCurrentSeason());

        self::sendReply($teamId, $channelCache, "FRC Team " . $team->getTeamNumber() . ", " . $team->getNickname()
            . " out of " . $team->getLocation()
            . ($district == null ? '' : " in the " . $district->getName() . " region."), $replyTo);
    }

    private static function processInfoRequest($teamId, $channelCache, $teamInfoRequestedFor, $replyTo = null) {
        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);

        $teamInfoRequestedFor = 'frc' . $teamInfoRequestedFor;

        $team = $tba->getTeam($teamInfoRequestedFor);

        if ($team == null) {
            self::sendUnknownTeam($teamId, $channelCache);
            return;
        }

        $status = $tba->getTBAStatus();
        $district = $team->getDistrict($status->getCurrentSeason());
        $record = $team->getTeamRecord($status->getCurrentSeason());

        self::sendReply($teamId, $channelCache, "FRC Team " . $team->getTeamNumber() . ", " . $team->getNickname()
            . " out of " . $team->getLocation()
            . ($district == null ? '' : " in the " . $district->getName() . " region") . ". "
            . ($team->getWebsite() == null ? '' : "They have a website at " . $team->getWebsite() . ".")
            . " They were FRC rookies in " . $team->getRookieYear() . "."
            . ($record == null ? '' : " They are " . $record['wins'] . '-' . $record['losses']
                . '-' . $record['ties'] . " across " . $record['competitions'] . " events."), $replyTo);
    }

    private static function processBetaInfoRequest($teamId, $channelCache, $teamInfoRequestedFor, $replyTo = null) {
        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);

        $teamInfoRequestedFor = 'frc' . $teamInfoRequestedFor;

        $team = $tba->getTeam($teamInfoRequestedFor);

        if ($team == null) {
            self::sendUnknownTeam($teamId, $channelCache);
            return;
        }

        $status = $tba->getTBAStatus();
        $district = $team->getDistrict($status->getCurrentSeason());
        $record = $team->getTeamRecord($status->getCurrentSeason());

        self::sendReply($teamId, $channelCache, "Team " . $team->getTeamNumber() . ", "
            . ($team->getWebsite() == null ? '' : '<' . $team->getWebsite() . '|') . $team->getNickname()
            . ($team->getWebsite() == null ? '' : '>') . " • " . $team->getLocation() . "\n"
            . ($team->getRookieYear() == null ? '' : 'Since ' . $team->getRookieYear())
            . ($district == null ? '' : " • " . $district->getName() . " District")
            . ($record == null ? '' : " • " . $record['wins'] . '-' . $record['losses']
                . '-' . $record['ties'] . ", (" . $record['competitions'] . ') ' . $status->getCurrentSeason()
                . " Events") . ' • ' . '<https://thebluealliance.com/team/' . $team->getTeamNumber() . '|View on TBA>',
            $replyTo);
    }

    private static function processDetailRequest($teamId, $channelCache, $teamInfoRequestedFor, $replyTo = null) {
        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);

        $team = $tba->getTeam('frc' . $teamInfoRequestedFor);

        if ($team == null) {
            self::sendUnknownTeam($teamId, $channelCache);
            return;
        }

        $status = $tba->getTBAStatus();
        $district = $team->getDistrict($status->getCurrentSeason());
        $record = $team->getTeamRecord($status->getCurrentSeason());

        $events = $team->getTeamEvents($status->getCurrentSeason());

        $attachments = array();

        if ($events) {
            foreach($events as $event) {
                $rankings = $event->getEventRankings();
                $eventRecord = $event->getEventRecordForTeam($team->getTeamNumber());
                $rank = null;

                if ($rankings) {
                    $rank = $rankings->getRankingForTeam($team->getTeamNumber());
                }

                $eventText = "Team " . $team->getTeamNumber() . ($rank == null ? ' had' : " was ranked "
                    . $rank->getRank() . " of " . $rankings->getNumberOfRankedTeams())
                    . ($eventRecord ? ($rank == null ? '' : ' with') . " a record of " . $eventRecord['wins']
                        . '-' . $eventRecord['losses'] . '-' . $eventRecord['ties'] : '');

                $awards = $tba->getTeamEventAwards('frc' . $teamInfoRequestedFor,
                    $event->getYear() . $event->getEventCode());

                if ($awards && count($awards) > 0) {
                    $eventText .= " and won the following awards:";

                    foreach($awards as $award) {
                        $eventText .= "\n• " . $award->getName();
                    }
                } else if (strlen($eventText)) {
                    $eventText .= '.';
                }

                $attachment = new Attachment($event->getName() . ($event->isOfficial() ? '' : ' (Unofficial)'),
                    $eventText);

                $attachment->data['title_link'] = "https://www.thebluealliance.com/event/" . $event->getKey();
                $attachment->data['footer'] = $event->getLocation() . " on " . $event->getStartDate() . " to "
                    . $event->getEndDate();

                $attachments[] = $attachment;
            }
        }

        self::sendReply($teamId, $channelCache, "FRC Team " . $team->getTeamNumber() . ", " . $team->getNickname()
            . " out of " . $team->getLocation()
            . ($district == null ? '' : " in the " . $district->getName() . " region") . ". "
            . ($team->getWebsite() == null ? '' : "They have a website at " . $team->getWebsite() . ".")
            . " They were FRC rookies in " . $team->getRookieYear() . "."
            . ($record == null ? '' : " They are " . $record['officialWins'] . '-' . $record['officialLosses']
                . '-' . $record['officialTies'] . " across " . $record['officialCompetitions']
                . " official events" . ($record['unofficialCompetitions'] == 0 ? '.' : " and " . $record['wins']
                    . '-' . $record['losses'] . '-' . $record['ties']
                    . " across " . $record['competitions'] . ' total events.')), $replyTo,
            $attachments);
    }

    private static function processStatusRequest($teamId, $channelCache, $teamStatusRequestedFor, $replyTo = null) {
        // First, check to see if the team is currently playing in any events, and report rank and last/next match
        // If not, check for any upcoming events, and report on their next event start date.
        // If not playing in any events, in addition to any upcoming events, report last event rank and W-L-T,
        //  and overall W-L-T across all (and report number of official/unofficial) events

        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);
        $status = $tba->getTBAStatus();

        $team = $tba->getTeam('frc' . $teamStatusRequestedFor);

        if ($team == null) {
            self::sendUnknownTeam($teamId, $channelCache);
            return;
        }

        // First, load team/frcXXXX/YYYY/events, parse the start_date and end_date of each event to see if the event
        // is active.

        $activeEvent = $team->getTeamActiveEvent($status->getCurrentSeason());
        $nextEvent = $team->getTeamNextEvent($status->getCurrentSeason());
        $lastEvent = $team->getTeamLastEvent($status->getCurrentSeason());

        // Next load event/EVENTID/rankings to search for the team and their rank. Other data varies year to year.

        // If the event is active, load event/EVENTID/matches and check for null score_breakdown to denote an upcoming
        // match

        if ($activeEvent != null) {
            $activeEventRankings = $activeEvent->getEventRankings();
            $ranking = $activeEventRankings->getRankingForTeam($team->getTeamNumber());
            $activeEventRecord = $activeEvent->getEventRecordForTeam($team->getTeamNumber());

            $activeEventMatches = $activeEvent->getEventMatches();
            $lastMatch = $activeEventMatches->getLastMatchForTeam($team->getTeamNumber());
            $nextMatch = $activeEventMatches->getNextMatchForTeam($team->getTeamNumber());

            $output = "FRC Team " . $team->getTeamNumber() . " (" . $team->getNickname() . ") is currently at "
                . ($activeEvent->getShortName() == null ? $activeEvent->getName() : $activeEvent->getShortName())
                . " " . ($activeEvent->isOfficial() ? '' : '(Unofficial) ')
                . "and is ranked " . $ranking->getRank() . "/" . $activeEventRankings->getNumberOfRankedTeams()
                . " with a record of ". $activeEventRecord['wins'] . "-" . $activeEventRecord['losses'] . "-"
                . $activeEventRecord['ties'] . "."
                . ($lastMatch != null ? "\nLast Match: " . $lastMatch->getKey() : '')
                . ($nextMatch != null ? "\nNext Match: " . $nextMatch->getKey() : '');
            self::sendReply($teamId, $channelCache, $output, $replyTo);
            return;
        }

        // Upcoming events are already loaded in team/frcXXXX/YYYY/events

        if ($lastEvent != null) {
            $lastEventRankings = $lastEvent->getEventRankings();
            $rankingAtLastEvent = $lastEventRankings->getRankingForTeam($team->getTeamNumber());
            $lastEventRecord = $lastEvent->getEventRecordForTeam($team->getTeamNumber());
        }

        /** @noinspection PhpUndefinedVariableInspection */
        $output = "FRC Team " . $team->getTeamNumber() . " (" . $team->getNickname() . ") is currently not at "
            . "an active event. "
            . ($nextEvent == null ? 'I have no upcoming events for this team. '
                : "They are scheduled to attend "
                . ($nextEvent->getShortName() == null ? $nextEvent->getName() : $nextEvent->getShortName())
                . ($nextEvent->isOfficial() ? '' : ' (Unofficial)')
                . " starting on " . $nextEvent->getStartDate() . ". ")
            . ($lastEvent == null ? 'I have no completed events for this team. '
                : "They last participated in "
                . ($lastEvent->getShortName() == null ? $lastEvent->getName() : $lastEvent->getShortName())
                . ($lastEvent->isOfficial() ? '' : ' (Unofficial)')
                    . ($rankingAtLastEvent == null ? '' : " and ranked " . $rankingAtLastEvent->getRank()
                . "/" . $lastEventRankings->getNumberOfRankedTeams() . " with a record of "
                . $lastEventRecord['wins'] . "-" . $lastEventRecord['losses'] . "-"
                . $lastEventRecord['ties']) . ".");

        self::sendReply($teamId, $channelCache, $output, $replyTo);
    }

    private static function processFollowRequest($teamId, $channelCache, $teamStatusRequestedFor, $level = 'all',
                                                 $requestedBy, $replyTo = null) {
        if ($level !='all' && $level != 'result' && $level != 'summary') {
            self::sendUnknownCommand($teamId, $channelCache);
        }

        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);

        $team = $tba->getTeam('frc' . $teamStatusRequestedFor);

        if ($team == null) {
            self::sendUnknownTeam($teamId, $channelCache);
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

        self::sendReply($teamId, $channelCache, "Got it! Now watching for $updateType for team "
            . $team->getTeamNumber() . " (" . $team->getNickname() . ") and will update this channel as they come in. "
            . "To stop, just ask me to *_unfollow " . $team->getTeamNumber() . "_* from this channel.", $replyTo);
    }

    private static function processUnfollowRequest($teamId, $channelCache, $teamStatusRequestedFor, $replyTo = null) {
        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);
        $db = new DB();

        $sub = $db->getSlackTeamSubscription($teamId, $channelCache['channelId'], $teamStatusRequestedFor);

        if ($sub) {
            $team = $tba->getTeam('frc' . $teamStatusRequestedFor);

            $db->deleteSlackTeamSubscription($teamId, $channelCache['channelId'], $teamStatusRequestedFor);

            self::sendReply($teamId, $channelCache, ":cry: I'm not following team " . $team->getTeamNumber()
                . " (" . $team->getNickname() . ") for you any longer. I hope you weren't their only friend. :sob:",
                $replyTo);
        } else {
            self::sendReply($teamId, $channelCache, "Uh... :worried: Sorry boss... I.. uh... Can't find a subscription "
                . "for team $teamStatusRequestedFor for this channel. I really hope I didn't lose it. Maybe check "
                . "and see who I'm *_following_* in this channel?", $replyTo);
        }
    }

    private static function processFollowingRequest($teamId, $channelCache, $replyTo = null) {
        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);
        $db = new DB();

        $subs = $db->getSlackTeamSubscriptions($teamId, $channelCache['channelId']);

        if ($subs) {
            $reply = "Lets see here, according to this punch card, I'm following:";

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
            self::sendReply($teamId, $channelCache, $reply, $replyTo);
        } else {
            self::sendReply($teamId, $channelCache, "It gets boring around here with nothing to do, no one to keep "
                . "track of.... I just sit here and spin my CPU cycles hoping, begging, somebody will come by and "
                . "ask me to *_follow [team number]_* and make me useful. _Don't we all just want to be useful?_",
                $replyTo);
        }
    }

    /**
     * @param $teamId string Slack TeamId
     * @param $channelCache array DB Channel Cache to send the message on
     * @param $reply string Message to send
     * @param $replyTo string Slack UserId to reply to
     * @param null|Attachment[] $attachments
     */
    private static function sendReply($teamId, $channelCache, $reply, $replyTo, $attachments = null) {
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

        $loop->run();
    }
}