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
 * Alliances object, used by various TBA match and event objects.
 * @author Brian Rozmierski
 */
class Alliances
{
    /**
     * @var \stdClass
     */
    public $data;

    /**
     * Alliance constructor.
     *
     * @param \stdClass $data returned from event match API
     */
    public function __construct(\stdClass $data)
    {
        $this->data = $data;
    }

    /**
     * Gets the blue alliance score.
     *
     * @return int blue alliance score
     */
    public function getBlueScore() {
        return $this->data->blue->score;
    }

    /**
     * Gets an array of the blue alliance team numbers.
     *
     * @return int[] array of blue team numbers
     */
    public function getBlueTeams() {
        return Team::stripTagFromTeams($this->data->blue->teams);
    }

    /**
     * Gets the red alliance score.
     *
     * @return int red alliance score
     */
    public function getRedScore() {
        return $this->data->red->score;
    }

    /**
     * Gets an array of the red alliance team numbers.
     *
     * @return int[] array of red team numbers
     */
    public function getRedTeams() {
        return Team::stripTagFromTeams($this->data->red->teams);
    }

    /**
     * Denotes if a given team is in one of the alliances.
     *
     * @param string $team team number as 'frcXXXX'
     * @return bool True if the team number is represented in either red or blue alliance
     */
    public function isTeamInAlliances($team) {
        return in_array($team, $this->getRedTeams()) || in_array($team, $this->getBlueTeams());
    }

    /**
     * Gets the alliance a given team is on.
     *
     * @param string $team team number as 'frcXXXX'
     * @return string|null 'red' or 'blue' or null if not in match
     */
    public function getAllianceForTeam($team) {
        if (in_array($team, $this->getRedTeams())) {
            return 'red';
        } else if (in_array($team, $this->getBlueTeams())) {
            return 'blue';
        }

        return null;
    }
}