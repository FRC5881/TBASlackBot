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

/**
 * Handles inbound events from the Slack Event API and sends them on for further processing.
 * @author Brian Rozmierski
 */
class ProcessEvent
{
    /**
     * Process the given JSON string Slack Event.
     *
     * @param String $eventJson The raw JSON-encoded string from the Slack Event API
     */
    public static function processEvent($eventJson) {
        $eventWrapper = json_decode($eventJson);

        $teamId = $eventWrapper->team_id;
        $event = $eventWrapper->event;

        if ($event->type === 'message') {
            if (isset($event->user)) { // Some bot messages are sent w/o a user field in the message, we can ignore them
                ProcessMessage::process($teamId, $event->user, $event->text, $event->channel);
            }
            return;
        } else if ($event->type === 'team_join') {
            // New user joining the Slack team, ignore.
            return;
        }

        error_log("Unknown handler for event type " . $event->type);
    }
}