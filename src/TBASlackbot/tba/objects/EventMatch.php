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


use TBASlackbot\tba\objects\yearspecific\ScoreBreakdown2016;

/**
 * A single Event Match from the TBA API.
 * @author Brian Rozmierski
 */
class EventMatch
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
     * Gets the match key.
     *
     * @return string
     */
    public function getKey() {
        return $this->data->key;
    }

    /**
     * Gets the code for the competition level the match is in.
     *
     * @return string 'qm', 'ef', 'qf', 'sf', 'f'
     */
    public function getCompetitionLevel() {
        return $this->data->comp_level;
    }

    /**
     * For elimination matches, the match number in the set.
     *
     * @return int|null Match set number, or null if not in elims
     */
    public function getSetNumber() {
        return $this->data->set_number;
    }

    /**
     * For qualification matches the match number.
     *
     * @return int match number
     */
    public function getMatchNumber() {
        return $this->data->match_number;
    }

    /**
     * The alliances in this match.
     *
     * @return Alliances
     */
    public function getAlliances() {
        return new Alliances($this->data->alliances);
    }

    /**
     * The score breakdown, if available, for this match.
     *
     * @return \stdClass|null
     */
    public function getScoreBreakdown() {
        return $this->data->score_breakdown;
    }

    /**
     * Gets the event key that this match takes place at.
     *
     * @return string Event key
     */
    public function getEventKey() {
        return $this->data->event_key;
    }

    /**
     * Gets the videos of this match.
     *
     * @return \stdClass[]
     */
    public function getVideos() {
        return $this->data->videos;
    }

    /**
     * Gets the match time as a string.
     *
     * @return string|null Match time as string, seems to be null more often than it's not
     */
    public function getTimeString() {
        return $this->data->time_string;
    }

    /**
     * Gets the scheduled match time.
     *
     * @return int Scheduled match time as UNIX Epoch
     */
    public function getTime() {
        return $this->data->time;
    }

    /**
     * Gets the year this match took place.
     *
     * @return int Year
     */
    public function getYear() {
        return substr($this->getEventKey(), 0, 4);
    }

    /**
     * Notes if this match is complete.
     *
     * @return bool true if either the score breakdown is present, or an alliance has a score applied
     */
    public function isComplete() {
        return $this->getScoreBreakdown() != null || $this->getAlliances()->getBlueScore() != null;
    }

    /**
     * Gets the name of the winning alliance.
     *
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

        return null;
    }

    /**
     * Red or Blue helper to make tie breaking reporting easier.
     *
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