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
 * Handles the list of alliances presented in the TBA Event API reply.
 *
 * @package TBASlackbot\tba\objects
 * @author Brian Rozmierski
 */

class EventAlliances
{
    /**
     * @var array
     */
    public $data;

    /**
     * @var EventAlliance[]
     */
    public $alliances = array();

    /**
     * Event Matches constructor.
     *
     * @param $data array returned from event matches API
     */
    public function __construct($data)
    {
        $this->data = $data;

        foreach ($data as $alliance) {
            $allianceObj = new EventAlliance($alliance);
            $this->alliances[] = $allianceObj;
        }
    }

    /**
     * Gets the elimination alliances as picked at the event. May be empty if alliance selection is not complete.
     *
     * @return EventAlliance[]
     */
    public function getAlliances() {
        return $this->alliances;
    }

    /**
     * Gets the EventAlliance object for the given team number if they are on an alliance as a pick or as backup.
     *
     * @param int $teamNumber Team number
     * @return null|EventAlliance Null if the team is not on an alliance, or the EventAlliance
     */
    public function getAllianceForTeam($teamNumber) {
        foreach ($this->alliances as $alliance) {
            if ($alliance->isTeamOnAlliance($teamNumber)) {
                return $alliance;
            }
        }

        return null;
    }

    /**
     * Gets an array of all the teams picked, and any backups called up in the alliances.
     *
     * @return int[] team numbers
     */
    public function getAllTeams() {
        $teams = array();

        foreach ($this->alliances as $alliance) {
            $teams = array_merge($teams, $alliance->getPicks());
            if ($alliance->isBackupUsed()) {
                $teams[] = $alliance->getBackupTeamIn();
            }
        }

        return $teams;
    }
}