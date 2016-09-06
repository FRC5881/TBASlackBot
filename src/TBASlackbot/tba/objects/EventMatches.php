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
 * Handles the output from the TBA event matches API
 * @author Brian Rozmierski
 */
class EventMatches
{
    /**
     * @var array
     */
    public $data;

    /**
     * @var EventMatch[]
     */
    public $matches = array();

    /**
     * Event Matches constructor.
     *
     * @param $data array returned from event matches API
     */
    public function __construct($data)
    {
        $this->data = $data;

        foreach ($data as $match) {
            $matchObj = new EventMatch($match);
            $this->matches[$matchObj->getKey()] = $matchObj;
        }
    }

    /**
     * @return EventMatch[]
     */
    public function getMatches() {
        return $this->matches;
    }

    /**
     * @param $key string Full match key, eg, 2016nytr_qm20
     * @return EventMatch match or null if key not found
     */
    public function getMatchByKey($key) {
        return $this->matches[$key];
    }

    /**
     * @param int $team Team number
     * @return null|EventMatch
     */
    public function getNextMatchForTeam($team) {
        // Pull the lowest time value for which score breakdown is null

        $lowTime = PHP_INT_MAX;
        $nextMatch = null;

        foreach ($this->matches as $match) {
            if ($match->getScoreBreakdown() == null && $match->getTime() < $lowTime
                && $match->getAlliances()->isTeamInAlliances($team)) {
                $lowTime = $match->getTime();
                $nextMatch = $match;
            }
        }

        return $nextMatch;
    }

    /**
     * @param int $team Team number
     * @return null|EventMatch
     */
    public function getLastMatchForTeam($team) {
        // Pull the highest time value for which score breakdown is not null

        $highTime = PHP_INT_MIN;
        $lastMatch = null;

        foreach ($this->matches as $match) {
            if ($match->getScoreBreakdown() != null && $match->getTime() > $highTime
                && $match->getAlliances()->isTeamInAlliances($team)) {
                $highTime = $match->getTime();
                $lastMatch = $match;
            }
        }

        return $lastMatch;
    }

    /**
     * @param int $team Team number
     * @return EventMatch[]
     */
    public function getMatchesForTeam($team) {
        $teamMatches = array();

        foreach ($this->matches as $match) {
            if ($match->getAlliances()->isTeamInAlliances($team)) {
                $teamMatches[] = $match;
            }
        }

        return $teamMatches;
    }
}