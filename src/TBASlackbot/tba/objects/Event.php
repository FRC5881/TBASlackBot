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
 * TBA Event Object
 * @author Brian Rozmierski
 */

namespace TBASlackbot\tba\objects;


use TBASlackbot\tba\TBAClient;

class Event
{
    /**
     * @var array
     */
    public $data;

    /**
     * @var TBAClient
     */
    private $tba;

    /**
     * Event constructor.
     * @param $TBAClient TBAClient
     * @param $data array returned from API containing a single event record
     */
    public function __construct(TBAClient $TBAClient, $data)
    {
        $this->tba = $TBAClient;
        $this->data = $data;
    }

    /**
     * @return string TBA event key
     */
    public function getKey() {
        return $this->data->key;
    }

    /**
     * @return string Website URL
     */
    public function getWebsite() {
        return $this->data->website;
    }

    /**
     * @return bool true if an official event
     */
    public function isOfficial() {
        // Yeah, this is broken. It's if it uses official FMS, not offseasion. See TBA GitHub #1607
        //return $this->data->official;

        // https://github.com/the-blue-alliance/the-blue-alliance/blob/master/consts/event_type.py#L38
        // 10 is an arbitrary number in case additional types are added. All offseason/preseason is >90
        return $this->getEventType() < 10;
    }

    /**
     * @return string Event end date as yyyy-mm-dd
     */
    public function getEndDate() {
        return $this->data->end_date;
    }

    /**
     * @return int
     */
    public function getEndDateTimestamp() {
        return date_create_from_format("Y-m-d", $this->getEndDate())->getTimestamp();
    }

    /**
     * @return string Event name
     */
    public function getName() {
        return $this->data->name;
    }

    /**
     * @return string Event short name
     */
    public function getShortName() {
        return $this->data->short_name;
    }

    /**
     * @return string If a district event, name of the district, otherwise null
     */
    public function getDistrictEventString() {
        return $this->data->event_district_string;
    }

    /**
     * @return string Event venue address, may include newline escape strings \n
     */
    public function getVenueAddress() {
        return $this->data->venue_address;
    }

    /**
     * @return int District Id
     * @link https://github.com/the-blue-alliance/the-blue-alliance/blob/master/consts/district_type.py#L6
     */
    public function getEventDistrict() {
        return $this->data->event_district;
    }

    /**
     * @return string Location, city, state, country
     */
    public function getLocation() {
        return $this->data->location;
    }

    /**
     * @return string Event code
     */
    public function getEventCode() {
        return $this->data->event_code;
    }

    /**
     * @return int Year the event is to be held
     */
    public function getYear() {
        return $this->data->year;
    }

    /**
     * @return array webcast information
     */
    public function getWebcast() {
        return $this->data->webcast;
    }

    /**
     * @return string Timezone of the event (eg America/New_York)
     */
    public function getTimeZone() {
        return $this->data->timezone;
    }

    /**
     * @return array Elimination Alliances
     */
    public function getAlliances() {
        return $this->data->alliances;
    }

    /**
     * @return string Event Type
     */
    public function getEventTypeString() {
        return $this->data->event_type_string;
    }

    /**
     * @return string Event start date as yyyy-mm-dd
     */
    public function getStartDate() {
        return $this->data->start_date;
    }

    /**
     * @return int
     */
    public function getStartDateTimestamp() {
        $existing = date_default_timezone_get();
        date_default_timezone_set('GMT');

        $ret = date_create_from_format("Y-m-d", $this->getStartDate())->getTimestamp();

        date_default_timezone_set($existing);

        return $ret;
    }

    /**
     * @return int Event Type
     * @link https://github.com/the-blue-alliance/the-blue-alliance/blob/master/consts/event_type.py#L2
     */
    public function getEventType() {
        return $this->data->event_type;
    }

    /**
     * @return bool
     */
    public function isFuture() {
        return $this->getStartDateTimestamp() > time();
    }

    /**
     * @return bool
     */
    public function isPast() {
        return $this->getEndDateTimestamp() < time();
    }

    /**
     * @return bool
     */
    public function isUnderway() {
        return $this->getStartDateTimestamp() < time() && time() < $this->getEndDateTimestamp();
    }

    /**
     * @return null|EventRankings
     */
    public function getEventRankings() {
        return $this->tba->getEventRankings($this->getKey());
    }

    /**
     * @return null|EventMatches
     */
    public function getEventMatches() {
        if (!isset($this->data->eventMatches)) {
            $this->data->eventMatches = $this->tba->getEventMatches($this->getKey());
        }

        return $this->data->eventMatches;
    }

    /**
     * @param $teamNumber
     * @return EventMatch[]
     */
    public function getEventMatchesForTeam($teamNumber) {
        return $this->getEventMatches()->getMatchesForTeam($teamNumber);
    }

    /**
     * @param $teamNumber
     * @return array of wins losses and ties
     */
    public function getEventRecordForTeam($teamNumber) {
        $matches = $this->getEventMatchesForTeam($teamNumber);
        $wins = 0;
        $losses = 0;
        $ties = 0;

        foreach ($matches as $match) {
            $winningAlliance = $match->getWinningAlliance();

            if ($winningAlliance == null) {
                $ties++;
            } else {
                $alliance = $match->getAlliances()->getAllianceForTeam($teamNumber);
                // Note, it can't be null since we're already in the match somewhere....

                if ($alliance == $winningAlliance) {
                    $wins++;
                } else {
                    $losses++;
                }
            }
        }

        return array('wins' => $wins, 'losses' => $losses, 'ties' => $ties);
    }

    public static function compareByStartDate(Event $a, Event $b) {
        return $a->getStartDateTimestamp() - $b->getStartDateTimestamp();
    }
}