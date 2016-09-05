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
 * A single Event Match from the TBA API
 * @author Brian Rozmierski
 */

namespace TBASlackbot\tba\objects;


use TBASlackbot\tba\objects\yearspecific\ScoreBreakdown2016;

class EventMatch
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
     * @return string
     */
    public function getKey() {
        return $this->data->key;
    }

    /**
     * @return string
     */
    public function getCompetitionLevel() {
        return $this->data->comp_level;
    }

    /**
     * @return int
     */
    public function getSetNumber() {
        return $this->data->set_number;
    }

    /**
     * @return int
     */
    public function getMatchNumber() {
        return $this->data->match_number;
    }

    /**
     * @return Alliances
     */
    public function getAlliances() {
        return new Alliances($this->data->alliances);
    }

    public function getScoreBreakdown() {
        return $this->data->score_breakdown;
    }

    /**
     * @return string
     */
    public function getEventKey() {
        return $this->data->event_key;
    }

    public function getVideos() {
        return $this->data->videos;
    }

    /**
     * @return string
     */
    public function getTimeString() {
        return $this->data->time_string;
    }

    /**
     * @return int
     */
    public function getTime() {
        return $this->data->time;
    }

    public function getYear() {
        return substr($this->getEventKey(), 0, 4);
    }

    /**
     * @return bool
     */
    public function isComplete() {
        return $this->getScoreBreakdown() != null || $this->getAlliances()->getBlueScore() != null;
    }

    /**
     * @return null|string red or blue or null if tied
     */
    public function getWinningAlliance() {
        if (!$this->isComplete()) {
            echo "\nNot Complete " . $this->getKey() . "\n";
            return null;
        }

        if (!isset($this->getScoreBreakdown()->red)
            || !isset($this->getScoreBreakdown()->blue)) {

            echo "\nNo Breakdown " . $this->getKey() . "\n";
            return $this->redOrBlue($this->getAlliances()->getRedScore(), $this->getAlliances()->getBlueScore());
        }

        if ($this->getYear() == 2016) {
            $scoreBreakdown = new ScoreBreakdown2016($this->getScoreBreakdown());

            $pointsWinner = $this->redOrBlue($scoreBreakdown->getTotalPoints('red'),
                $scoreBreakdown->getTotalPoints('blue'));

            if ($pointsWinner != null || $this->getCompetitionLevel() === 'qm') {
                return $pointsWinner;
            }

            //echo "\n Into tiebreaker for " . $this->getKey() . "\n";

            // Tiebreakers... Elims only

            $foulPointsWinner = $this->redOrBlue($scoreBreakdown->getFoulPoints('red'),
                $scoreBreakdown->getFoulPoints('blue'));
            if ($foulPointsWinner != null) {
                //echo "TB fouls: $foulPointsWinner\n";
                return $foulPointsWinner;
            }

            $breechAndCaptureWinner = $this->redOrBlue($scoreBreakdown->getBreachPoints('red')
                + $scoreBreakdown->getCapturePoints('red'), $scoreBreakdown->getBreachPoints('blue')
                + $scoreBreakdown->getCapturePoints('blue'));
            if ($breechAndCaptureWinner != null) {
                //echo "TB Breech $breechAndCaptureWinner\n";
                return $breechAndCaptureWinner;
            }

            $autoWinner = $this->redOrBlue($scoreBreakdown->getAutoPoints('red'),
                $scoreBreakdown->getAutoPoints('blue'));
            if ($autoWinner != null) {
                return $autoWinner;
            }

            $scaleAndChallengeWinner = $this->redOrBlue($scoreBreakdown->getTeleopScalePoints('red')
                + $scoreBreakdown->getTeleopChallengePoints('red'), $scoreBreakdown->getTeleopScalePoints('blue')
                + $scoreBreakdown->getTeleopChallengePoints('blue'));
            if ($scaleAndChallengeWinner != null) {
                return $scaleAndChallengeWinner;
            }

            $towerGoalPointWinner = $this->redOrBlue($scoreBreakdown->getAutoBoulderPoints('red')
                + $scoreBreakdown->getTeleopBoulderPoints('red'), $scoreBreakdown->getAutoBoulderPoints('blue')
                + $scoreBreakdown->getTeleopBoulderPoints('blue'));
            if ($towerGoalPointWinner != null) {
                return $towerGoalPointWinner;
            }

            $crossingPointWinner = $this->redOrBlue($scoreBreakdown->getAutoCrossingPoints('red')
                + $scoreBreakdown->getTeleopCrossingPoints('red'), $scoreBreakdown->getAutoCrossingPoints('blue')
                + $scoreBreakdown->getTeleopCrossingPoints('blue'));
            if ($crossingPointWinner != null) {
                return $crossingPointWinner;
            }

            // It went to the tossup, and we don't know how FMS decided.
            return null;
        }
    }

    /**
     * @param int $red red score
     * @param int $blue blue score
     * @param bool $invert false to return the higher score, true to return lower score
     * @return null|string red or blue or null if tied
     */
    private function redOrBlue($red, $blue, $invert = false) {
        if ($red > $blue) {
            return $invert ? 'blue' : 'red';
        } else if ($blue > $red) {
            return $invert ? 'red' : 'blue';
        } else {
            return null;
        }
    }
}