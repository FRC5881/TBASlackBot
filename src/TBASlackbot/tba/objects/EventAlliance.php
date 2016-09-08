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


namespace TBASlackbot\tba\objects;

/**
 * Contains the details for an elimination alliance as proved by the TBA Event API.
 *
 * @package TBASlackbot\tba\objects
 * @author Brian Rozmierski
 */
class EventAlliance
{
    /**
     * @var \stdClass
     */
    public $data;

    /**
     * Event Match constructor.
     *
     * @param \stdClass $data returned from event match API
     */
    public function __construct(\stdClass $data)
    {
        $this->data = $data;
    }

    /**
     * Gets the name of the alliance.
     *
     * @return string Alliance name
     */
    public function getName() {
        return isset($this->data->name) ? $this->data->name : null;
    }

    /*
     * Disabled as the TBA API can provide this info, but there is no datafeed to populate it. See
     * https://github.com/the-blue-alliance/the-blue-alliance/pull/948
     *
    public function getDeclines() {
        return $this->data->declines;
    }*/

    /**
     * Gets the list of teams picked for the alliance. Note, this is not the exhaustive list of alliance members as
     * the backup team, if called for, will not be in this list.
     *
     * @return int[] Array of team numbers in the alliance
     */
    public function getPicks() {
        return Team::stripTagFromTeams($this->data->picks);
    }

    /**
     * Notes if a backup team has been called up to the alliance.
     *
     * @return bool true if a backup team has been called up
     */
    public function isBackupUsed() {
        return isset($this->data->backup) && $this->data->backup != null;
    }

    /**
     * Gets the team number of the alliance member who was replaced by the backup team.
     *
     * @return int|null team number
     */
    public function getBackupTeamOut() {
        return isset($this->data->backup->out) ? (int) substr($this->data->backup->out, 3) : null;
    }

    /**
     * Gets the team number of the backup team called up
     *
     * @return int|null team number
     */
    public function getBackupTeamIn() {
        return isset($this->data->backup->in) ? (int) substr($this->data->backup->in, 3) : null;
    }

    /**
     * Notes if a team is a member of the alliance. This does include the backup team and the team they replace.
     *
     * @param int $teamNumber Team number
     * @return bool true if team is a pick or backup team on the alliance
     */
    public function isTeamOnAlliance($teamNumber) {
        return in_array($teamNumber, $this->getPicks()) || $this->getBackupTeamIn() == $teamNumber;
    }
}