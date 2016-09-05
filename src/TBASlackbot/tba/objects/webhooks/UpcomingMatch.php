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
 * UpcomingMatch webhook object.
 * @author Brian Rozmierski
 */

namespace TBASlackbot\tba\objects\webhooks;


class UpcomingMatch
{
    /**
     * @var array
     */
    public $data;

    /**
     * Upcoming Match constructor.
     * @param $data array returned from webhook
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getEventName() {
        $this->data->event_name;
    }

    public function getScheduledTime() {
        $this->data->scheduled_time;
    }

    public function getMatchKey() {
        $this->data->match_key;
    }

    public function getTeamKeys() {
        $this->data->team_keys;
    }

    public function getPredictedTime() {
        $this->data->predicted_time;
    }
}