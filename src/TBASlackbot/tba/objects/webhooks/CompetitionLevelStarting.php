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


namespace TBASlackbot\tba\objects\webhooks;

/**
 * Object for Competition Level Starting webhook
 * @author Brian Rozmierski
 */
class CompetitionLevelStarting
{
    /**
     * @var \stdClass
     */
    public $data;

    /**
     * Competition Level Starting constructor.
     *
     * @param \stdClass $data returned from webhook
     */
    public function __construct(\stdClass $data)
    {
        $this->data = $data;
    }

    /**
     * Gets the Event Name
     *
     * @return string|null Event name
     */
    public function getEventName() {
        return $this->data->event_name;
    }

    /**
     * Gets the Event Key
     *
     * @return string|null Event Key
     */
    public function getEventKey() {
        return $this->data->event_key;
    }

    /**
     * Gets the Competition Level
     *
     * @return string|null Competition Level code 'f', 'sf', 'qf', 'qm'
     */
    public function getCompLevel() {
        return $this->data->comp_level;
    }

    /**
     * Gets the scheduled time for the next match
     *
     * @return int|null Scheduled Time as UNIX Epoch
     */
    public function getScheduledTime() {
        return $this->data->scheduled_time;
    }
}