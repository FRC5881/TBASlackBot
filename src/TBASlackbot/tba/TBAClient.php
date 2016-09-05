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
 * Client for The Blue Alliance
 * @author Brian Rozmierski
 */

namespace TBASlackbot\tba;


use GuzzleHttp\Client;
use TBASlackbot\tba\objects\Award;
use TBASlackbot\tba\objects\District;
use TBASlackbot\tba\objects\Event;
use TBASlackbot\tba\objects\EventMatches;
use TBASlackbot\tba\objects\EventRankings;
use TBASlackbot\tba\objects\Status;
use TBASlackbot\tba\objects\Team;
use TBASlackbot\utils\DB;

class TBAClient
{
    private static $URLBASE = "https://www.thebluealliance.com/api/v2/";

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var DB
     */
    private $db;

    /**
     * TBAClient constructor.
     * @param $appId string The Blue Alliance X-TBA-App-Id element
     * @link https://www.thebluealliance.com/apidocs
     */
    public function __construct($appId)
    {
        $this->httpClient = new Client(['headers' => ['X-TBA-App-Id' => $appId]]);
        $this->db = new DB();
    }

    /**
     * @return null|Status
     */
    public function getTBAStatus() {
        $status = $this->callApi('status', 600);

        if ($status === false) {
            error_log("Error retrieving status");
            return null;
        }

        $status = json_decode($status);

        if ($status === false) {
            error_log("Error decoding status: $status");
            return null;
        }

        return new Status($status);
    }

    /**
     * @param $teamId
     * @return null|Team
     */
    public function getTeam($teamId) {
        $team = $this->callApi("team/$teamId");

        if ($team === false) {
            error_log("Error retrieving team $teamId");
            return null;
        }

        $team = json_decode($team);

        if ($team === false) {
            error_log("Error decoding team $teamId: $team");
            return null;
        }

        return new Team($this, $team);
    }

    /**
     * @param $teamId
     * @param $year
     * @return Event[]
     */
    public function getTeamEvents($teamId, $year) {
        $events = $this->callApi("team/$teamId/$year/events");

        if ($events === false) {
            error_log("Error retrieving team events $teamId/$year");
            return null;
        }

        $events = json_decode($events);

        if ($events === false) {
            error_log("Error decoding team events $teamId/$year: $events");
            return null;
        }

        $objEvents = array();
        foreach ($events as $eventJson) {
            $objEvents[] = new Event($this, $eventJson);
        }

        usort($objEvents, array(Event::class, 'compareByStartDate'));

        return $objEvents;
    }

    /**
     * @param $teamId
     * @param $eventCode
     * @return Award[]|null
     */
    public function getTeamEventAwards($teamId, $eventCode) {
        $awards = $this->callApi("team/$teamId/event/$eventCode/awards");

        if ($awards === false) {
            error_log("Error retrieving team event awards $teamId/$eventCode");
            return null;
        }

        $awards = json_decode($awards);

        if ($awards === false) {
            error_log("Error decoding team event awards $teamId/$eventCode: $awards");
            return null;
        }

        $objAwards = array();
        foreach ($awards as $awardJson) {
            $objAwards[] = new Award($awardJson);
        }

        return $objAwards;
    }

    /**
     * @param $teamId
     * @return null|array index array with season year and district code
     */
    public function getTeamHistoryDistricts($teamId) {
        $districts = $this->callApi("team/$teamId/history/districts");

        if ($districts === false) {
            error_log("Error retrieving team districts $teamId");
            return null;
        }

        $districts = json_decode($districts, true);

        if ($districts === false) {
            error_log("Error decoding team districts $teamId: $districts");
            return null;
        }

        return $districts;
    }

    /**
     * @param $eventId
     * @return null|Event
     */
    public function getEvent($eventId) {
        $event = $this->callApi("event/$eventId");

        if ($event === false) {
            error_log("Error retrieving event $eventId");
            return null;
        }

        $event = json_decode($event);

        if ($event === false) {
            error_log("Error decoding event $eventId: $event");
            return null;
        }

        return new Event($this, $event);
    }

    /**
     * @param $eventId
     * @return null|EventMatches
     */
    public function getEventMatches($eventId) {
        $matches = $this->callApi("event/$eventId/matches");

        if ($matches === false) {
            error_log("Error retrieving event matches for $eventId");
            return null;
        }

        $matches = json_decode($matches);

        if ($matches === false) {
            error_log("Error decoding event $eventId matches: $matches");
            return null;
        }

        return new EventMatches($matches);
    }

    /**
     * @param $eventId
     * @return null|EventRankings
     */
    public function getEventRankings($eventId) {
        $rankings = $this->callApi("event/$eventId/rankings");

        if ($rankings === false) {
            error_log("Error retrieving event rankings for $eventId");
            return null;
        }

        $rankings = json_decode($rankings);

        if ($rankings === false) {
            error_log("Error decoding event $eventId rankings: $rankings");
            return null;
        }

        return new EventRankings($rankings);
    }

    /**
     * @param $year
     * @return null|District[]
     */
    public function getDistricts($year) {
        $districts = $this->callApi("districts/$year");

        if ($districts === false) {
            error_log("Error retrieving districts for $year");
            return null;
        }

        $districts = json_decode($districts);

        if ($districts === false) {
            error_log("Error decoding districts: $districts");
            return null;
        }

        $objDistricts = array();
        foreach ($districts as $districtJson) {
            $objDistricts[] = new District($districtJson, $year);
        }

        return $objDistricts;
    }

    /**
     * Calls the TBA API for that stub URL, checking the cache first, and using the If-Modified-Since header to
     * be a nice TBA API user.
     * @param $urlStub string The URL stub from the API to call eg 'team/frc5881'
     * @param int $minCacheTime int Optional minimum amount of time to use a cache object exclusively for. If the
     * cached object is not at least this number of seconds old, no API call will be made to TBA, and the cached
     * object will be returned outright.
     * @return bool|string False on error, or JSON string, potentially cached
     */
    private function callApi($urlStub, $minCacheTime = 120) {
        //error_log("Calling $urlStub");
        $opts = array('http_errors' => false);

        $cached = $this->db->getTBAApiCache($urlStub);

        $lastModified = null;

        if ($cached) {
            $lastModified = $cached['lastModified'];

            if ($cached['lastModifiedUnix'] + $minCacheTime >= time()
                || $cached['lastRetrieval'] + $minCacheTime >= time()) {
                return $cached['apiJsonString'];
            }
        }

        if ($lastModified != null) {
            $opts['headers'] = ['If-Modified-Since' => $lastModified];
        }

        $response = $this->httpClient->get(self::$URLBASE . $urlStub, $opts);

        if ($response->getStatusCode() == 304 && $cached) {
            $this->db->setTBAApiCacheChecked($urlStub);
            return $cached['apiJsonString'];
        } elseif ($response->getStatusCode() != 200) {
            return false;
        }

        if ($response->getHeaderLine('Last-Modified')) {
            $this->db->setTBAApiCache($urlStub, $response->getHeaderLine('Last-Modified'), $response->getBody());
        }

        return $response->getBody();
    }
}