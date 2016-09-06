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
 * TBA District Object.
 * @author Brian Rozmierski
 */
class District
{
    /**
     * @var \stdClass
     */
    public $data;

    /**
     * @var int
     */
    public $year;

    /**
     * District constructor.
     *
     * @param \stdClass $data returned from district API
     * @param int $year year from the API for this district
     */
    public function __construct(\stdClass $data, $year)
    {
        $this->data = $data;
        $this->year = $year;
    }

    /**
     * Gets the name of the district.
     *
     * @return string district name
     */
    public function getName() {
        return $this->data->name;
    }

    /**
     * Gets the key to identify the district
     *
     * @return string district key
     */
    public function getKey() {
        return $this->data->key;
    }

    /**
     * Since the TBA v2 Team History API uses the format <year><district> as the key for district history, we
     * present a compatible version here.
     *
     * @return string Key prepended with the year
     */
    public function getYearKey() {
        return $this->year . $this->getKey();
    }
}