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
 * TBA Team Object.
 * @author Brian Rozmierski
 */
class Team
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
     * Team constructor.
     * @param TBAClient $TBAClient
     * @param \stdClass $data returned from team API
     */
    public function __construct(TBAClient $TBAClient, \stdClass $data)
    {
        $this->tba = $TBAClient;
        $this->data = $data;

        $this->data->teamEvents = array();
    }

    /**
     * Gets the team website, if not the default.
     *
     * @return string|null Website URL or null if the default value is present
     */
    public function getWebsite() {
        if ($this->data->website === "http://www.firstinspires.org/") {
            return null;
        } else if (strpos($this->data->website, ':///') > 0) {
            return str_replace(':///', '://', $this->data->website);
        } else {
            return $this->data->website;
        }
    }

    /**
     * Gets the full team name.
     *
     * @return string Full (long, sponsor-filled) team name
     */
    public function getName() {
        return $this->data->name;
    }

    /**
     * Gets the city the team is in.
     *
     * @return string Locality/City
     */
    public function getLocality() {
        return $this->data->locality;
    }

    /**
     * Gets the team's rookie year.
     *
     * @return int Year the team was a rookie
     */
    public function getRookieYear() {
        return $this->data->rookie_year;
    }

    /**
     * Gets the team's region/state.
     *
     * @return string Region/State
     */
    public function getRegion() {
        return $this->data->region;
    }

    /**
     * Gets the team number.
     *
     * @return int team number
     */
    public function getTeamNumber() {
        return $this->data->team_number;
    }

    /**
     * Gets the team's location, city, state, and country.
     *
     * @return string Location (city/state/country)
     */
    public function getLocation() {
        return $this->data->location;
    }

    /**
     * Gets the team key.
     *
     * @return string TBA team key (frc9999)
     */
    public function getKey() {
        return $this->data->key;
    }

    /**
     * Gets the country name the team is in.
     *
     * @return string Country name
     */
    public function getCountryName() {
        return $this->data->country_name;
    }

    /**
     * Gets the team motto.
     *
     * @return string|null Optional team motto
     */
    public function getMotto() {
        return $this->data->motto;
    }

    /**
     * Gets the team nickname.
     *
     * @return string Team nickname (common short name)
     */
    public function getNickname() {
        return $this->data->nickname;
    }

    /**
     * Gets the district the team is/was in in a given year.
     *
     * @param int $year
     * @return null|District
     */
    public function getDistrict($year) {
        $teamDistricts = $this->tba->getTeamHistoryDistricts('frc' . $this->getTeamNumber());
        if ($teamDistricts != null) {
            $code = isset($teamDistricts[$year]) ? $teamDistricts[$year] : null;
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
     * Gets the events for the team for hte given year.
     *
     * @param int $year
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
     *
     * @param int $year
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
     *
     * @param int $year
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
     *
     * @param int $year
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

    /**
     * Gets the team's record during qualification matches for the given year.
     *
     * @param int $year
     * @return array|null null if no matches complete, otherwise array with official and unofficial wins losses and
     * ties, eg 'unofficialWins' or 'officialTies', as well as 'wins', 'losses', and 'ties' overall. Also includes
     * 'officialCompetitions' and 'unofficialCompetitions'.
     */
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

    /**
     * Gets the team's record across all matches for the given year.
     *
     * @param int $year
     * @return array|null null if no matches complete, otherwise array with official and unofficial wins losses and
     * ties, eg 'unofficialWins' or 'officialTies', as well as 'wins', 'losses', and 'ties' overall. Also includes
     * 'officialCompetitions' and 'unofficialCompetitions'.
     */
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

    /**
     * Strips the 'frc' prefix from an array of teams.
     *
     * @param string[] $teams Teams with 'frc' prefix
     * @return int[] array of team w/o 'frc' prefix
     */
    public static function stripTagFromTeams($teams) {
        $newTeams = array();
        for ($i = 0; $i < count($teams); $i++) {
            $newTeams[] = substr($teams[$i], 3);
        }
        return $newTeams;
    }
}