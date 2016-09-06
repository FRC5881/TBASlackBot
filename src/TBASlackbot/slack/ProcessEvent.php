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
     * @param String $eventJson The raw JASON-encoded string from the Slack Event API
     */
    public static function processEvent($eventJson) {
        $eventWrapper = json_decode($eventJson);

        $teamId = $eventWrapper->team_id;
        $event = $eventWrapper->event;

        if ($event->type === 'message') {
            ProcessMessage::process($teamId, $event->user, $event->text, $event->channel);
            return;
        }

        error_log("Unknown handler for event type " . $event->type);
    }
}