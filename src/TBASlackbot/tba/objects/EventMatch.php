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
     * Gets the scheduled match time.
     *
     * @return int Scheduled match time as UNIX Epoch
     */
    public function getTime() {
        return $this->data->time;
    }

    /**
     * Gets the TBA predicted match time.
     *
     * @return int TBA predicted match time as UNIX Epoch
     */
    public function getPredictedTime() {
        return $this->data->predicted_time;
    }

    /**
     * Gets the year this match took place.
     *
     * @return int Year
     */
    public function getYear() {
        return (int)substr($this->getEventKey(), 0, 4);
    }

    /**
     * Gets an English conversation name for a match, eg "Quarterfinals 1 Match 1"
     *
     * @return string
     */
    public function getConversationalName() {
        return self::getStringForCompLevel($this->getCompetitionLevel()) . ' '
            . ($this->getCompetitionLevel() === 'qm' ? '' : $this->getSetNumber() . ' ')
            . 'Match ' . $this->getMatchNumber();
    }

    /**
     * Notes if this match is complete.
     *
     * @return bool true if either the score breakdown is present, or an alliance has a score applied
     */
    public function isComplete() {
        return $this->getScoreBreakdown() != null
        || ($this->getAlliances()->getBlueScore() != null && $this->getAlliances()->getBlueScore() >= 0);
    }

    /**
     * Gets the name of the winning alliance.
     *
     * @return null|string red or blue or null if tied
     */
    public function getWinningAlliance() {
        return $this->data->winning_alliance;
    }

    /**
     * Gets a string suitable for humans from the competition level.
     *
     * @param string $compLevel
     * @return string Competition level as a full, stable word
     */
    public static function getStringForCompLevel($compLevel) {
        if ($compLevel === 'qm') {
            return 'Qualification';
        } else if ($compLevel === 'ef') {
            return 'Eighth-final';
        } else if ($compLevel === 'qf') {
            return 'Quarterfinal';
        } else if ($compLevel === 'sf') {
            return 'Semifinal';
        } else if ($compLevel === 'f') {
            return 'Final';
        } else {
            error_log("Unknown Competition Level: $compLevel");
            return null;
        }
    }
}