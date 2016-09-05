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
 * Handles the output from the TBA event ranking API and creates an array of EventRanking objects to iterate.
 * @author Brian Rozmierski
 */

namespace TBASlackbot\tba\objects;


class EventRankings
{
    /**
     * @var array
     */
    public $data;

    /**
     * @var EventRanking[]
     */
    public $rankings = array();

    /**
     * Event Ranking constructor.
     * @param $data array returned from event ranking API
     */
    public function __construct($data)
    {
        $this->data = $data;

        if (count($data) > 1) {
            for ($i = 1; $i < count($data); $i++) {
                $this->rankings[] = new EventRanking($this->data[0], $this->data[$i]);
            }
        }
    }

    /**
     * @return EventRanking[]
     */
    public function getRankings() {
        return $this->rankings;
    }

    /**
     * @return int
     */
    public function getNumberOfRankedTeams() {
        return count($this->rankings);
    }

    /**
     * @param $team int FRC team number
     * @return EventRanking|null
     */
    public function getRankingForTeam($team) {
        foreach ($this->rankings as $ranking) {
            if ($ranking->getTeam() == $team) {
                return $ranking;
            }
        }

        return null;
    }
}