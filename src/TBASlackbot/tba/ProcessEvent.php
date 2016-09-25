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


namespace TBASlackbot\tba;

use Slack\Message\Attachment;
use TBASlackbot\slack\Channels;
use TBASlackbot\slack\ProcessMessage;
use TBASlackbot\tba\objects\Award;
use TBASlackbot\tba\objects\Event;
use TBASlackbot\tba\objects\EventMatch;
use TBASlackbot\tba\objects\webhooks\UpcomingMatch;
use TBASlackbot\utils\DB;

/**
 * Processes inbound events/messages from TBA fire hose.
 * @author Brian Rozmierski
 */
class ProcessEvent
{
    /**
     * @param string $messageJson
     */
    public static function processEvent($messageJson) {
        $messageWrapper = json_decode($messageJson);

        switch($messageWrapper->message_type) {
            case 'upcoming_match':
                $upcomingMatch = new UpcomingMatch($messageWrapper->message_data);
                self::processUpcomingMatch($upcomingMatch);
                break;
            case 'match_score':
                $eventMatch = new EventMatch($messageWrapper->message_data->match);
                self::processMatchScore($eventMatch);
                break;
            case 'starting_comp_level':
                // Holding off on this for now, this doesn't get sent until *after* the first match of a new
                // level. If it's starting qm's it doesn't provide any help, elims we know by alliance selection
                // and later elim levels we know if the team is competing by schedule_posted events.

                //$compLevelStarting = new CompetitionLevelStarting($messageWrapper->message_data);
                break;
            case 'alliance_selection':
                $event = new Event(new TBAClient(TBASLACKBOT_TBA_APP_ID), $messageWrapper->message_data->event);
                self::processAllianceSelection($event);
                break;
            case 'awards_posted':
                $awardObj = array();

                foreach($messageWrapper->message_data->awards as $award) {
                    $awardObj[] = new Award($award);
                }

                self::processAwardsPosted($awardObj);

                break;
            case 'schedule_posted':
            case 'schedule_updated':
                self::processScheduleUpdate($messageWrapper->message_data->event_key);
                break;
            case 'ping':
            case 'update_favorites':
            case 'update_subscriptions':
                // Nothing to do here, ignore.
                break;
            case 'broadcast':
            case 'verification':
                error_log("BROADCAST / VERIFICATION MESSAGE: " . $messageJson);
                break;
            default:
                error_log("Unknown Message Type: " . $messageJson);
        }
    }

    /**
     * Handle upcoming match notifications and send messages.
     *
     * @param UpcomingMatch $upcomingMatch
     */
    public static function processUpcomingMatch(UpcomingMatch $upcomingMatch) {
        $teams = $upcomingMatch->getTeamNumbers();
        $subs = null;

        if (count($teams) > 0) {
            $subs = self::getSubscriptionsByChannel($teams);
        }

        if (count($subs) == 0) {
            return;
        }

        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);
        $match = $tba->getMatch($upcomingMatch->getMatchKey());

        foreach($subs as $channelId => $sub) {
            if ($sub['subscriptionType'] === 'all') {
                $attachments = array();

                $replyText = 'Followed Team' . (count($sub['frcTeams']) == 1 ? ' ' : 's ')
                    . implode(', ', $sub['frcTeams']) . ' will be playing soon at ' . $upcomingMatch->getEventName()
                    . ' in ' . EventMatch::getStringForCompLevel($match->getCompetitionLevel()) . 's '
                    . ($match->getCompetitionLevel() === 'qm' || $match->getCompetitionLevel() === 'f' ? ''
                        : $match->getSetNumber())
                    . 'match ' . $match->getMatchNumber() . ' • '
                    . '<https://thebluealliance.com/match/' . $match->getKey() . '|View on TBA>';

                if ($upcomingMatch->getTeamNumbers() && count($upcomingMatch->getTeamNumbers()) == 6) {
                    $attachment = new Attachment('Red Alliance', $upcomingMatch->getTeamNumbers()[0] . ' - '
                        . $upcomingMatch->getTeamNumbers()[1] . ' - ' . $upcomingMatch->getTeamNumbers()[2],
                        null, '#FF0000');
                    $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
                    $attachments[] = $attachment;

                    $attachment = new Attachment('Blue Alliance', $upcomingMatch->getTeamNumbers()[3] . ' - '
                        . $upcomingMatch->getTeamNumbers()[4] . ' - ' . $upcomingMatch->getTeamNumbers()[5],
                        null, '#0000FF');
                    $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
                    $attachments[] = $attachment;
                }

                ProcessMessage::sendReply($sub['teamId'], Channels::getChannelCache($sub['teamId'], $channelId),
                    $replyText, null, $attachments);
            }
        }
    }

    /**
     * Handle match score events and send messages.
     *
     * @param EventMatch $match
     */
    public static function processMatchScore(EventMatch $match) {
        $teams = array_merge($match->getAlliances()->getBlueTeams(), $match->getAlliances()->getRedTeams());

        $subs = null;

        if (count($teams) > 0) {
            $subs = self::getSubscriptionsByChannel($teams);
        }

        if (count($subs) == 0) {
            return;
        }

        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);

        foreach($subs as $channelId => $sub) {
            if ($sub['subscriptionType'] !== 'summary') {
                $event = $tba->getEvent($match->getEventKey());
                $attachments = array();

                $replyText = 'Followed Team' . (count($sub['frcTeams']) == 1 ? ' ' : 's ')
                    . implode(', ', $sub['frcTeams']) . ' have completed '
                    . EventMatch::getStringForCompLevel($match->getCompetitionLevel()) . 's '
                    . ($match->getCompetitionLevel() === 'qm' || $match->getCompetitionLevel() === 'f' ? ''
                        : $match->getSetNumber())
                    . 'match ' . $match->getMatchNumber() . ' at '
                    . ($event->getShortName() ? $event->getShortName() : $event->getName())
                    . ' • ' . '<https://thebluealliance.com/match/' . $match->getKey() . '|View on TBA>';

                $attachment = new Attachment('Red Alliance', implode(', ', $match->getAlliances()->getRedTeams())
                    . ' • ' . $match->getAlliances()->getRedScore() . ' Points'
                    . ($match->getWinningAlliance() === 'red' ? ' • *Win*' : ''), null, '#FF0000');
                $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
                $attachments[] = $attachment;

                $attachment = new Attachment('Blue Alliance', implode(', ', $match->getAlliances()->getBlueTeams())
                    . ' • ' . $match->getAlliances()->getBlueScore() . ' Points'
                    . ($match->getWinningAlliance() === 'blue' ? ' • *Win*' : ''), null, '#0000FF');
                $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
                $attachments[] = $attachment;

                ProcessMessage::sendReply($sub['teamId'], Channels::getChannelCache($sub['teamId'], $channelId),
                    $replyText, null, $attachments);
            }
        }
    }

    /**
     * Handle alliance selection events and send messages.
     *
     * @param Event $event
     */
    public static function processAllianceSelection(Event $event) {
        $teams = $event->getAlliances()->getAllTeams();

        $subs = null;

        if (count($teams) > 0) {
            $subs = self::getSubscriptionsByChannel($teams);
        }

        if (count($subs) == 0) {
            return;
        }

        foreach($subs as $channelId => $sub) {
            if ($sub['subscriptionType'] === 'all') {
                $replyText = 'Followed Team' . (count($sub['frcTeams']) == 1 ? ' ' : 's ')
                    . implode(', ', $sub['frcTeams']) . ' playing at '
                    . ($event->getShortName() ? $event->getShortName() : $event->getName())
                    . (count($sub['frcTeams']) == 1 ? ' has been placed on an alliance'
                        : ' have been placed on alliances.')
                    . "\n" . '<https://thebluealliance.com/event/' . $event->getKey() . '|View Event on TBA>';
                $attachments = array();

                foreach ($event->getAlliances()->getAlliances() as $alliance) {
                    $attachment = new Attachment($alliance->getName(), 'Teams ' . implode(', ', $alliance->getPicks())
                        . ($alliance->isBackupUsed()
                            ? ' with ' . $alliance->getBackupTeamIn() . ' as backup for '
                            . $alliance->getBackupTeamOut() : ''));
                    $attachment->data['mrkdwn_in'] = ['text', 'pretext', 'fields'];
                    $attachments[] = $attachment;
                }

                ProcessMessage::sendReply($sub['teamId'], Channels::getChannelCache($sub['teamId'], $channelId),
                    $replyText, null, $attachments);
            }
        }
    }

    /**
     * Handle award posted event and send messages.
     *
     * @param Award[] $awards
     */
    public static function processAwardsPosted($awards) {
        // We only really care if the event in the award has ended. This triggers the end of event summary.

        if (count($awards) == 0) {
            return;
        }

        $eventKey = $awards[0]->getEventKey();

        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);

        $event = $tba->getEvent($eventKey);
        $eventMatches = $event->getEventMatches();

        // Walk the events, if any are not complete this award post isn't the final one.
        foreach($eventMatches->getMatches() as $match) {
            if (!$match->isComplete()) {
                return;
            }
        }

        $teams = $event->getEventTeams();
        $teamNumbers = array();

        foreach($teams as $team) {
            $teamNumbers[] = $team->getTeamNumber();
        }

        $subs = null;

        if (count($teamNumbers) > 0) {
            $subs = self::getSubscriptionsByChannel($teamNumbers);
        }

        if (count($subs) == 0) {
            return;
        }

        foreach($subs as $channelId => $sub) {
            // This summary is sent for all subscription types.

            $replyText = 'Followed Team' . (count($sub['frcTeams']) == 1 ? ' ' : 's ')
                . implode(', ', $sub['frcTeams']) . ' have completed playing at '
                . ($event->getShortName() ? $event->getShortName() : $event->getName())
                . "\n" . '<https://thebluealliance.com/event/' . $event->getKey() . '|View Event on TBA>';

            $attachments = array();

            foreach($sub['frcTeams'] as $teamNumber) {
                $rankings = $event->getEventRankings();
                $eventRecord = $event->getEventRecordForTeam($teamNumber);
                $highestCompLevel = $event->getHighestCompLevelForTeam($teamNumber);
                $rank = null;

                if ($rankings) {
                    $rank = $rankings->getRankingForTeam($teamNumber);
                }

                $eventText = "Team " . $teamNumber . ($rank == null ? ' had' : " was ranked "
                        . $rank->getRank() . " of " . $rankings->getNumberOfRankedTeams())
                    . ($eventRecord ? ($rank == null ? '' : ' with') . " a record of " . $eventRecord['wins']
                        . '-' . $eventRecord['losses'] . '-' . $eventRecord['ties'] : '');

                $awards = $tba->getTeamEventAwards('frc' . $teamNumber,
                    $event->getYear() . $event->getEventCode());

                if ($awards && count($awards) > 0 || ($highestCompLevel !== 'f' && $highestCompLevel !== 'qm')) {
                    $eventText .= " and won the following awards:";

                    if ($highestCompLevel !== 'f' && $highestCompLevel !== 'qm') {
                        $eventText .= "\n• " . ($highestCompLevel === 'ef' ? 'Eighth-Finalist'
                                : ($highestCompLevel === 'qf' ? 'Quarterfinalist'
                                    : ($highestCompLevel === 'sf' ? 'Semifinalist' : '')));
                    }

                    foreach($awards as $award) {
                        $eventText .= "\n• " . $award->getName();
                    }
                } else if (strlen($eventText)) {
                    $eventText .= '.';
                }

                $attachments[] = new Attachment('Team ' . $teamNumber, $eventText);
            }

            ProcessMessage::sendReply($sub['teamId'], Channels::getChannelCache($sub['teamId'], $channelId),
                $replyText, null, $attachments);
        }

    }

    /**
     * Handle schedule update event and send messages.
     *
     * @param string $eventKey
     */
    public static function processScheduleUpdate($eventKey) {
        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);

        $event = $tba->getEvent($eventKey);
        $eventMatches = $event->getEventMatches();

        $teamNumbers = array();

        foreach ($eventMatches->getMatches() as $match) {
            if (!$match->isComplete()) {
                $teamNumbers = array_merge($teamNumbers, $match->getAlliances()->getBlueTeams());
                $teamNumbers = array_merge($teamNumbers, $match->getAlliances()->getRedTeams());
            }
        }

        $subs = null;

        if (count($teamNumbers) > 0) {
            $subs = self::getSubscriptionsByChannel($teamNumbers);
        }

        if (count($subs) == 0) {
            return;
        }

        foreach($subs as $channelId => $sub) {
            if ($sub['subscriptionType'] === 'all') {
                $replyText = 'Followed Team' . (count($sub['frcTeams']) == 1 ? ' ' : 's ')
                    . implode(', ', $sub['frcTeams']) . ' playing at '
                    . ($event->getShortName() ? $event->getShortName() : $event->getName())
                    . (count($sub['frcTeams']) == 1 ? ' has had a schedule update'
                        : ' have had their schedules updated.')
                    . "\n" . '<https://thebluealliance.com/event/' . $event->getKey() . '|View Event on TBA>';

                ProcessMessage::sendReply($sub['teamId'], Channels::getChannelCache($sub['teamId'], $channelId),
                    $replyText, null);
            }
        }
    }

    /**
     * Gets an associative array keyed on Slack ChannelId with child arrays for each subscription.
     *
     * @param int[] $teamNumbers
     * @return array Array keyed by channelId, with an array of 'teamId', 'frcTeam', and 'subscriptionType' items
     */
    private static function getSubscriptionsByChannel($teamNumbers) {
        $db = new DB();
        $subArray = array();

        foreach ($teamNumbers as $teamNumber) {
            $subs = $db->getSlackFRCTeamSubscriptions($teamNumber);

            if ($subs) {
                foreach ($subs as $row) {
                    if (isset($subArray[$row['channelId']])) {
                        $subArray[$row['channelId']]['subscriptionType'] = self::getGreaterSubscriptionType(
                                $subArray[$row['channelId']]['subscriptionType'], $row['subscriptionType']);
                        $subArray[$row['channelId']]['frcTeams'][] = $row['frcTeam'];
                    } else {
                        $subArray[$row['channelId']] = array('teamId' => $row['teamId'],
                            'channelId' => $row['channelId'], 'frcTeams' => array($row['frcTeam']),
                            'subscriptionType' => $row['subscriptionType']);
                    }
                }
            }
        }
        return $subArray;
    }

    /**
     * Given two subscription type strings, return the greater (more verbose) of the two.
     *
     * @param $a
     * @param $b
     * @return string
     */
    private static function getGreaterSubscriptionType($a, $b) {
        if ($a === 'all' || $b === 'all') {
            return 'all';
        } else if ($a === 'result' || $b === 'result') {
            return 'result';
        } else {
            return 'summary';
        }
    }
}