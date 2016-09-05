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
 * Object for Competition Level Starting webhook
 * @author Brian Rozmierski
 */

namespace TBASlackbot\tba\objects\webhooks;


class CompetitionLevelStarting
{
    /**
     * @var array
     */
    public $data;

    /**
     * Competition Level Starting constructor.
     * @param $data array returned from webhook
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getEventName() {
        return $this->data->event_name;
    }

    public function getEventKey() {
        return $this->data->event_key;
    }

    public function getCompLevel() {
        return $this->data->comp_level;
    }

    public function getScheduledTime() {
        return $this->data->scheduled_time;
    }
}