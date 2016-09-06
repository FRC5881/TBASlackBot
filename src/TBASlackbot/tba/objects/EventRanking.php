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
 * Holds a single Event Ranking from the TBA API.
 * This seems to vary dramatically from year to year, so only basics are listed.
 * @author Brian Rozmierski
 */
class EventRanking
{
    /**
     * @var string[]
     */
    public $header;

    /**
     * @var mixed[]
     */
    public $data;

    /**
     * Event Ranking constructor.
     *
     * @param string[] $header header values from the event ranking API
     * @param mixed[] $data returned from event ranking API
     */
    public function __construct($header, $data)
    {
        $this->header = $header;
        $this->data = $data;
    }

    /**
     * Gets the rank of the team at the event.
     *
     * @return int rank position
     */
    public function getRank() {
        return $this->data[0];
    }

    /**
     * Gets the team number at this rank.
     *
     * @return int team number
     */
    public function getTeam() {
        return $this->data[1];
    }

    /**
     * Notes if the W-L-T record through qualifications is available.
     *
     * @return bool true if record is available
     */
    public function isRecordAvailable() {
        return $this->getOther("Record (W-L-T)") != null;
    }

    /**
     * Gets the number of wins through qualifications.
     *
     * @return null|int wins or null if not available
     */
    public function getWins() {
        if ($this->getOther("Record (W-L-T)") != null) {
            return substr($this->getOther("Record (W-L-T)"), 0, strpos($this->getOther("Record (W-L-T)"), '-'));
        }

        return null;
    }

    /**
     * Gets the number of losses through qualifications.
     *
     * @return null|int losses or null if not available
     */
    public function getLosses() {
        if ($this->getOther("Record (W-L-T)") != null) {
            $pos = strpos($this->getOther("Record (W-L-T)"), '-') + 1;
            return substr($this->getOther("Record (W-L-T)"), $pos,
                strrpos($this->getOther("Record (W-L-T)"), '-') - $pos);
        }

        return null;
    }

    /**
     * Gets the number of ties through qualifications.
     *
     * @return null|int ties or null if not available
     */
    public function getTies() {
        if ($this->getOther("Record (W-L-T)") != null) {
            return substr($this->getOther("Record (W-L-T)"), strrpos($this->getOther("Record (W-L-T)"), '-') + 1);
        }

        return null;
    }

    /**
     * @param string $name Header value to lookup
     * @return mixed|null
     */
    public function getOther($name) {
        for ($i = 0; $i < count($this->header); $i++) {
            if (strtolower($name) === strtolower($this->header[$i])) {
                return $this->data[$i];
            }
        }

        return null;
    }
}