<?php
// FRC5881 Unofficial TBA Slack Bot
// Copyright (c) 2017.
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
 * Handles the output from the TBA event ranking API and creates an array of EventRanking objects to iterate.
 * @author Brian Rozmierski
 */
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
     * Event Rankings constructor.
     *
     * @param array $data returned from event ranking API
     */
    public function __construct($data)
    {
        $this->data = $data;

        foreach ($data->rankings as $ranking) {
            $this->rankings[] = new EventRanking($ranking, $this->data->extra_stats_info);
        }
    }

    /**
     * Gets an array of EventRanking objects for this event.
     *
     * @return EventRanking[]
     */
    public function getRankings() {
        return $this->rankings;
    }

    /**
     * Gets the number of teams ranked at this event.
     *
     * @return int number of ranked teams
     */
    public function getNumberOfRankedTeams() {
        return count($this->rankings);
    }

    /**
     * Gets the individual ranking for a given team.
     *
     * @param int $team team number
     * @return EventRanking|null Ranking or null if team number not found in ranking
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