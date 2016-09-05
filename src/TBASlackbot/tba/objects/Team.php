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
 * TBA Team Object
 * @author Brian Rozmierski
 */

namespace TBASlackbot\tba\objects;


use TBASlackbot\tba\TBAClient;

class Team
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
     * Team constructor.
     * @param $TBAClient TBAClient
     * @param $data array returned from team API
     */
    public function __construct(TBAClient $TBAClient, $data)
    {
        $this->tba = $TBAClient;
        $this->data = $data;

        $this->data->teamEvents = array();
    }

    /**
     * @return string Website URL
     */
    public function getWebsite() {
        if ($this->data->website === "http://www.firstinspires.org/") {
            return null;
        } else {
            return $this->data->website;
        }
    }

    /**
     * @return string Full (long, sponsor-filled) team name
     */
    public function getName() {
        return $this->data->name;
    }

    /**
     * @return string Locality/City
     */
    public function getLocality() {
        return $this->data->locality;
    }

    /**
     * @return int Year the team was a rookie
     */
    public function getRookieYear() {
        return $this->data->rookie_year;
    }

    /**
     * @return string Region/State
     */
    public function getRegion() {
        return $this->data->region;
    }

    /**
     * @return int team number
     */
    public function getTeamNumber() {
        return $this->data->team_number;
    }

    /**
     * @return string Location (city/state/country)
     */
    public function getLocation() {
        return $this->data->location;
    }

    /**
     * @return string TBA team key (frc9999)
     */
    public function getKey() {
        return $this->data->key;
    }

    /**
     * @return string Country name
     */
    public function getCountryName() {
        return $this->data->country_name;
    }

    /**
     * @return string Optional team motto
     */
    public function getMotto() {
        return $this->data->motto;
    }

    /**
     * @return string Team nickname (common short name)
     */
    public function getNickname() {
        return $this->data->nickname;
    }

    /**
     * @param $year int
     * @return null|District
     */
    public function getDistrict($year) {
        $teamDistricts = $this->tba->getTeamHistoryDistricts('frc' . $this->getTeamNumber());
        if ($teamDistricts != null) {
            $code = $teamDistricts[$year];
            if ($code) {
                $districts = $this->tba->getDistricts($year);
                foreach($districts as $district) {
                    if ($code === $district->getYearKey()) {
                        return $district;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param $year int
     * @return Event[]
     */
    public function getTeamEvents($year) {
        if (isset($this->data->teamEvents[$year])) {
            return $this->data->teamEvents[$year];
        }

        $events = $this->tba->getTeamEvents($this->getKey(), $year);
        $this->data->teamEvents[$year] = $events;

        return $events;
    }

    /**
     * Gets the "next" event based on today, but does need the season year to search from.
     * @param $year int
     * @return null|Event
     */
    public function getTeamNextEvent($year) {
        $nextEvent = null;
        $nextEventTimestamp = PHP_INT_MAX;

        foreach($this->getTeamEvents($year) as $event) {
            if ($event->isFuture() && $event->getStartDateTimestamp() < $nextEventTimestamp) {
                $nextEvent = $event;
                $nextEventTimestamp = $event->getStartDateTimestamp();
            }
        }

        return $nextEvent;
    }

    /**
     * Gets the "last" event based on today, but does need the season year to search from.
     * @param $year int
     * @return null|Event
     */
    public function getTeamLastEvent($year) {
        $lastEvent = null;
        $lastEventTimestamp = PHP_INT_MIN;

        foreach($this->getTeamEvents($year) as $event) {
            if ($event->isPast() && $event->getEndDateTimestamp() > $lastEventTimestamp) {
                $lastEvent = $event;
                $lastEventTimestamp = $event->getEndDateTimestamp();
            }
        }

        return $lastEvent;
    }

    /**
     * Gets the "active" event based on today, but does need the season year to search from.
     * @param $year int
     * @return null|Event
     */
    public function getTeamActiveEvent($year) {
        foreach($this->getTeamEvents($year) as $event) {
            if ($event->isUnderway()) {
                return $event;
            }
        }

        return null;
    }

    public function getTeamQualificationRecord($year) {
        $events = $this->getTeamEvents($year);

        $officialWins = 0;
        $officialLosses = 0;
        $officialTies = 0;
        $officialCompetitions = 0;

        $unofficialWins = 0;
        $unofficialLosses = 0;
        $unofficialTies = 0;
        $unofficialCompetitions = 0;

        foreach($events as $event) {
            $rankings = $event->getEventRankings();
            if ($rankings) {
                $ranking = $rankings->getRankingForTeam($this->getTeamNumber());
                if ($ranking && $ranking->isRecordAvailable()) {
                    if ($event->isOfficial()) {
                        $officialWins += $ranking->getWins();
                        $officialLosses += $ranking->getLosses();
                        $officialTies += $ranking->getTies();
                        $officialCompetitions++;
                    } else {
                        $unofficialWins += $ranking->getWins();
                        $unofficialLosses += $ranking->getLosses();
                        $unofficialTies += $ranking->getTies();
                        $unofficialCompetitions++;
                    }
                }
            }
        }

        if ($officialCompetitions == 0 && $unofficialCompetitions == 0) {
            return null;
        }

        return ['officialWins' => $officialWins, 'officialLosses' => $officialLosses, 'officialTies' => $officialTies,
            'officialCompetitions' => $officialCompetitions, 'unofficialWins' => $unofficialWins,
            'unofficialLosses' => $unofficialLosses, 'unofficialTies' => $unofficialTies,
            'unofficialCompetitions' => $unofficialCompetitions, 'wins' => $officialWins + $unofficialWins,
            'losses' => $officialLosses + $unofficialLosses, 'ties' => $officialTies + $unofficialTies,
            'competitions' => $officialCompetitions + $unofficialCompetitions];
    }

    public function getTeamRecord($year) {
        $events = $this->getTeamEvents($year);

        $officialWins = 0;
        $officialLosses = 0;
        $officialTies = 0;
        $officialCompetitions = 0;

        $unofficialWins = 0;
        $unofficialLosses = 0;
        $unofficialTies = 0;
        $unofficialCompetitions = 0;

        foreach($events as $event) {
            $record = $event->getEventRecordForTeam($this->getTeamNumber());

            if ($event->isOfficial()) {
                $officialWins += $record['wins'];
                $officialLosses += $record['losses'];
                $officialTies += $record['ties'];
                $officialCompetitions++;
            } else {
                $unofficialWins += $record['wins'];
                $unofficialLosses += $record['losses'];
                $unofficialTies += $record['ties'];
                $unofficialCompetitions++;
            }
        }

        if ($officialCompetitions == 0 && $unofficialCompetitions == 0) {
            return null;
        }

        return ['officialWins' => $officialWins, 'officialLosses' => $officialLosses, 'officialTies' => $officialTies,
            'officialCompetitions' => $officialCompetitions, 'unofficialWins' => $unofficialWins,
            'unofficialLosses' => $unofficialLosses, 'unofficialTies' => $unofficialTies,
            'unofficialCompetitions' => $unofficialCompetitions, 'wins' => $officialWins + $unofficialWins,
            'losses' => $officialLosses + $unofficialLosses, 'ties' => $officialTies + $unofficialTies,
            'competitions' => $officialCompetitions + $unofficialCompetitions];
    }
}