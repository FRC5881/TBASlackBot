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
 * TBA Status Object.
 * @author Brian Rozmierski
 */
class Status
{

    /**
     * @var \stdClass
     */
    public $data;

    /**
     * Status constructor.
     * @param \stdClass $data returned from status API
     */
    public function __construct(\stdClass $data)
    {
        $this->data = $data;
    }

    /**
     * Gets the current season.
     *
     * @return int Current Season (year)
     */
    public function getCurrentSeason() {
        return $this->data->current_season;
    }

    /**
     * Gets an array of events that are offline.
     *
     * @return array Array of events that are down
     */
    public function getDownEvents() {
        return $this->data->down_events;
    }

    /**
     * Gets the minimum supported IOS version number.
     *
     * @return int Minimum IOS Version
     */
    public function getIosMinVersion() {
        return $this->data->ios->min_app_version;
    }

    /**
     * Gets the latest IOS version number.
     *
     * @return int Latest IOS Version
     */
    public function getIosLatestVersion() {
        return $this->data->ios->latest_app_version;
    }

    /**
     * Gets the latest season (year) that data is available for.
     *
     * @return int Latest (max) Season (year)
     */
    public function getMaxSeason() {
        return $this->data->max_season;
    }

    /**
     * Gets the minimum supported Android version number.
     *
     * @return int Minimum Android Version
     */
    public function getAndroidMinVersion() {
        return $this->data->android->min_app_version;
    }

    /**
     * Gets the latest Android version number.
     *
     * @return int Latest Android Version
     */
    public function getAndroidLatestVersion() {
        return $this->data->android->latest_app_version;
    }

    /**
     * Notes if the FIRST FMS API data feed is down.
     *
     * @return bool true if the FIRST FMS API data feed is down
     */
    public function isDataFeedDown() {
        return $this->data->is_datafeed_down;
    }
}