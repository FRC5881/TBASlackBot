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

use TBASlackbot\tba\objects\Team;

/**
 * UpcomingMatch web hook object.
 * @package TBASlackbot\tba\objects\webhooks
 * @author Brian Rozmierski
 */
class UpcomingMatch
{
    /**
     * @var \stdClass
     */
    public $data;

    /**
     * Upcoming Match constructor.
     *
     * @param \stdClass $data returned from webhook
     */
    public function __construct(\stdClass $data)
    {
        $this->data = $data;
    }

    /**
     * Gets the Event Name.
     *
     * @return string|null Event Name
     */
    public function getEventName() {
        return $this->data->event_name;
    }

    /**
     * Gets the scheduled time for the match.
     *
     * @return int|null Scheduled time for match as UNIX Epoch
     */
    public function getScheduledTime() {
        return $this->data->scheduled_time;
    }

    /**
     * Gets the Match Key.
     *
     * @return string|null Match Key
     */
    public function getMatchKey() {
        return $this->data->match_key;
    }

    /**
     * Gets the Team Keys.
     *
     * @return string[]|null Array of team keys (frcXXXX) in the match
     */
    public function getTeamKeys() {
        return $this->data->team_keys;
    }

    /**
     * Gets the Team Numbers.
     *
     * @return int[] Array of team numbers
     */
    public function getTeamNumbers() {
        return Team::stripTagFromTeams($this->getTeamKeys());
    }

    /**
     * Gets the TBA predicted start time of the match
     *
     * @return int|null Predicted start time as UNIX Epoch
     */
    public function getPredictedTime() {
        return $this->data->predicted_time;
    }
}