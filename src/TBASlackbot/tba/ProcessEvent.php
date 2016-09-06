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

use TBASlackbot\tba\objects\Award;
use TBASlackbot\tba\objects\Event;
use TBASlackbot\tba\objects\EventMatch;
use TBASlackbot\tba\objects\webhooks\CompetitionLevelStarting;
use TBASlackbot\tba\objects\webhooks\UpcomingMatch;

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
                break;
            case 'match_score':
                $eventMatch = new EventMatch($messageWrapper->message_data->match);
                break;
            case 'starting_comp_level':
                $compLevelStarting = new CompetitionLevelStarting($messageWrapper->message_data);
                break;
            case 'alliance_selection':
                $event = new Event(new TBAClient(TBASLACKBOT_TBA_APP_ID), $messageWrapper->message_data->event);
                break;
            case 'awards_posted':
                foreach($messageWrapper->message_data->awards as $award) {
                    $awardObj[] = new Award($award);
                }
                break;
            case 'schedule_posted':
                $eventKey = $messageWrapper->message_data->event_key;
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
     * @param UpcomingMatch $upcomingMatch
     */
    public static function processUpcomingMatch(UpcomingMatch $upcomingMatch) {

    }
}