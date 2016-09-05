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
 * TBS Status Object
 * @author Brian Rozmierski
 */

namespace TBASlackbot\tba\objects;


class Status
{

    /**
     * @var array
     */
    public $data;

    /**
     * Status constructor.
     * @param $data array returned from status API
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return int Current Season (year)
     */
    public function getCurrentSeason() {
        return $this->data->current_season;
    }

    /**
     * @return array Array of events that are down
     */
    public function getDownEvents() {
        return $this->data->down_events;
    }

    /**
     * @return int Minimum IOS Version
     */
    public function getIosMinVersion() {
        return $this->data->ios->min_app_version;
    }

    /**
     * @return int Latest IOS Version
     */
    public function getIosLatestVersion() {
        return $this->data->ios->latest_app_version;
    }

    /**
     * @return int Latest (max) Season (year)
     */
    public function getMaxSeason() {
        return $this->data->max_season;
    }

    /**
     * @return int Minimum Android Version
     */
    public function getAndroidMinVersion() {
        return $this->data->android->min_app_version;
    }

    /**
     * @return int Latest Android Version
     */
    public function getAndroidLatestVersion() {
        return $this->data->android->latest_app_version;
    }

    /**
     * @return bool true if the FIRST FMS API datafeed is down
     */
    public function isDataFeedDown() {
        return $this->data->is_datafeed_down;
    }
}