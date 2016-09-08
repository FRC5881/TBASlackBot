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


use TBASlackbot\tba\TBAClient;

/**
 * TBA Event Object.
 * @author Brian Rozmierski
 */
class Event
{
    /**
     * @var \stdClass
     */
    public $data;

    /**
     * @var TBAClient
     */
    private $tba;

    /**
     * Event constructor.
     *
     * @param TBAClient $TBAClient
     * @param \stdClass $data returned from API containing a single event record
     */
    public function __construct(TBAClient $TBAClient, $data)
    {
        $this->tba = $TBAClient;
        $this->data = $data;
    }

    /**
     * Gets the event key.
     *
     * @return string TBA event key
     */
    public function getKey() {
        return $this->data->key;
    }

    /**
     * Gets the team website URL.
     *
     * @return string|null Website URL
     */
    public function getWebsite() {
        return $this->data->website;
    }

    /**
     * Denotes if this is an official in-season FIRST event.
     *
     * @return bool true if an official event
     */
    public function isOfficial() {
        // Yeah, this is broken. It's if it uses official FMS, not off-season. See TBA GitHub #1607
        //return $this->data->official;

        // https://github.com/the-blue-alliance/the-blue-alliance/blob/master/consts/event_type.py#L38
        // 10 is an arbitrary number in case additional types are added. All off-season/preseason is >90
        return $this->getEventType() < 10;
    }

    /**
     * Get event ending date string.
     *
     * @return string Event end date as yyyy-mm-dd
     */
    public function getEndDate() {
        return $this->data->end_date;
    }

    /**
     * Get event ending date as timestamp.
     *
     * @return int Event end date as UNIX Epoch
     */
    public function getEndDateTimestamp() {
        return date_create_from_format("Y-m-d", $this->getEndDate())->getTimestamp();
    }

    /**
     * Gets the event name.
     *
     * @return string Event name
     */
    public function getName() {
        return $this->data->name;
    }

    /**
     * Gets the event short name.
     *
     * @return string|null Event short name
     */
    public function getShortName() {
        return $this->data->short_name;
    }

    /**
     * Gets the name of the district the event is in.
     *
     * @return string|null If a district event, name of the district, otherwise null
     */
    public function getDistrictEventString() {
        return $this->data->event_district_string;
    }

    /**
     * Gets the event venue address.
     *
     * @return string Event venue address, may include newline escape strings \n
     */
    public function getVenueAddress() {
        return $this->data->venue_address;
    }

    /**
     * Gets the TBA District ID for the event.
     *
     * @return int|null District Id
     * @link https://github.com/the-blue-alliance/the-blue-alliance/blob/master/consts/district_type.py#L6
     */
    public function getEventDistrict() {
        return $this->data->event_district;
    }

    /**
     * Gets the location of the event.
     *
     * @return string Location, city, state, country
     */
    public function getLocation() {
        return $this->data->location;
    }

    /**
     * Gets the event code.
     *
     * @return string Event code
     */
    public function getEventCode() {
        return $this->data->event_code;
    }

    /**
     * Gets the year the event is to be held.
     *
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
     * Gets the fully-formed timezone for the event.
     *
     * @return string Timezone of the event (eg America/New_York)
     */
    public function getTimeZone() {
        return $this->data->timezone;
    }

    /**
     * Gets the alliances for the elimination rounds of the event.
     *
     * @return EventAlliances Elimination Alliances
     */
    public function getAlliances() {
        return new EventAlliances($this->data->alliances);
    }

    /**
     * Gets the event type.
     *
     * @return string Event Type
     */
    public function getEventTypeString() {
        return $this->data->event_type_string;
    }

    /**
     * Get event starting date string.
     *
     * @return string Event start date as yyyy-mm-dd
     */
    public function getStartDate() {
        return $this->data->start_date;
    }

    /**
     * Get event string date as timestamp.
     *
     * @return int Event start date as UNIX Epoch
     */
    public function getStartDateTimestamp() {
        $existing = date_default_timezone_get();
        date_default_timezone_set('GMT');

        $ret = date_create_from_format("Y-m-d", $this->getStartDate())->getTimestamp();

        date_default_timezone_set($existing);

        return $ret;
    }

    /**
     * Gets the TBA event type code for this event.
     *
     * @return int Event Type
     * @link https://github.com/the-blue-alliance/the-blue-alliance/blob/master/consts/event_type.py#L2
     */
    public function getEventType() {
        return $this->data->event_type;
    }

    /**
     * Notes if this event is scheduled for the future.
     *
     * @return bool true if starting in the future
     */
    public function isFuture() {
        return $this->getStartDateTimestamp() > time();
    }

    /**
     * Notes if this event has ended.
     *
     * @return bool true if ending in the past
     */
    public function isPast() {
        return $this->getEndDateTimestamp() < time();
    }

    /**
     * Notes if this event is currently scheduled to be in progress.
     *
     * @return bool true if the event start but not end date has passed
     */
    public function isUnderway() {
        return $this->getStartDateTimestamp() < time() && time() < $this->getEndDateTimestamp();
    }

    /**
     * Gets a list of teams at the event.
     *
     * @return Team[]
     */
    public function getEventTeams() {
        if (!isset($this->data->eventTeams)) {
            $this->data->eventTeams = $this->tba->getEventTeams($this->getKey());
        }

        return $this->data->eventTeams;
    }

    /**
     * Gets the event rankings for this event.
     *
     * @return null|EventRankings
     */
    public function getEventRankings() {
        return $this->tba->getEventRankings($this->getKey());
    }

    /**
     * Gets the event matches for this event.
     *
     * @return null|EventMatches
     */
    public function getEventMatches() {
        if (!isset($this->data->eventMatches)) {
            $this->data->eventMatches = $this->tba->getEventMatches($this->getKey());
        }

        return $this->data->eventMatches;
    }

    /**
     * Gets the matches for the team at this event.
     *
     * @param int $team Team number
     * @return EventMatch[] Array of event matches for this team, may be empty
     */
    public function getEventMatchesForTeam($team) {
        return $this->getEventMatches()->getMatchesForTeam($team);
    }

    /**
     * Gets the W-L-T record for the team at this event across all matches.
     *
     * @param int $team Team number
     * @return array of 'wins', 'losses', and 'ties'
     */
    public function getEventRecordForTeam($team) {
        $matches = $this->getEventMatchesForTeam($team);
        $wins = 0;
        $losses = 0;
        $ties = 0;

        foreach ($matches as $match) {
            $winningAlliance = $match->getWinningAlliance();

            if ($winningAlliance == null) {
                $ties++;
            } else {
                $alliance = $match->getAlliances()->getAllianceForTeam($team);
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

    /**
     * Gets the highest competition level the team played in at the event
     *
     * @param int $team Team number
     * @return string 'qm', 'ef', 'qf', 'sf', 'f'
     */
    public function getHighestCompLevelForTeam($team) {
        $matches = $this->getEventMatchesForTeam($team);
        $highCompLevel = 'qm'; //qm, ef, qf, sf, f

        foreach($matches as $match) {
            $compLevel = $match->getCompetitionLevel();
            if ($compLevel == 'f') {
                // Nothing higher...
                return $compLevel;
            } else if ($compLevel == 'sf') {
                $highCompLevel = $compLevel;
            } else if ($compLevel == 'qf' && $highCompLevel !== 'sf') {
                $highCompLevel = $compLevel;
            } else if ($compLevel == 'ef' && $highCompLevel === 'qm') {
                $highCompLevel = $compLevel;
            }
        }

        return $highCompLevel;
    }

    /**
     * Comparator to compare two events by their start date.
     *
     * @param Event $a Event to compare
     * @param Event $b Event to compare
     * @return int <= -1 if $a earlier than $b, 0 if the same time, or >= 1 if $b earlier than $a
     */
    public static function compareByStartDate(Event $a, Event $b) {
        return $a->getStartDateTimestamp() - $b->getStartDateTimestamp();
    }
}