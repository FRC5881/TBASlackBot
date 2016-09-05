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
 * Alliances object, used by various TBA match and event objects
 * @author Brian Rozmierski
 */

namespace TBASlackbot\tba\objects;


class Alliances
{
    /**
     * @var array
     */
    public $data;

    /**
     * Event Match constructor.
     * @param $data array returned from event match API
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getBlueScore() {
        return $this->data->blue->score;
    }

    /**
     * @return array
     */
    public function getBlueTeams() {
        return $this->stripTagFromTeams($this->data->blue->teams);
    }

    /**
     * @return int
     */
    public function getRedScore() {
        return $this->data->red->score;
    }

    /**
     * @return array
     */
    public function getRedTeams() {
        return $this->stripTagFromTeams($this->data->red->teams);
    }

    /**
     * @param $team
     * @return bool True if the team number is represented in either red or blue alliance
     */
    public function isTeamInAlliances($team) {
        return in_array($team, $this->getRedTeams()) || in_array($team, $this->getBlueTeams());
    }

    /**
     * @param $team
     * @return null|string red or blue or null if not in match
     */
    public function getAllianceForTeam($team) {
        if (in_array($team, $this->getRedTeams())) {
            return 'red';
        } else if (in_array($team, $this->getBlueTeams())) {
            return 'blue';
        }

        return null;
    }

    /**
     * @param $teams array Teams with 'frc' prefix
     * @return array
     */
    private function stripTagFromTeams($teams) {
        $newTeams = array();
        for ($i = 0; $i < count($teams); $i++) {
            $newTeams[] = substr($teams[$i], 3);
        }
        return $newTeams;
    }
}