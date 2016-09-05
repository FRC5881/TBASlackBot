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
 * Database Helper Class
 * @author Brian Rozmierski
 */

namespace TBASlackbot\utils;

class DB
{

    private $mysqli;

    /**
     * DB constructor. Inherits values from the settings.php file
     */
    public function __construct()
    {
        $this->mysqli = new \mysqli(TBASLACKBOT_MYSQL_HOST, TBASLACKBOT_MYSQL_USER, TBASLACKBOT_MYSQL_PASS,
            TBASLACKBOT_MYSQL_DB);
        if ($this->mysqli->connect_errno) {
            die('Failed to connect to MySQL: ' . $this->mysqli->connect_errno . ' ' . $this->mysqli->connect_error);
        }

        $this->tableCheck();
    }

    /**
     * DB constructor.
     * @param $host String MySQL hostname
     * @param $user String MySQL Username
     * @param $password String MySQL password
     * @param $dbName String MySQL database name
     */
    public function __construct1($host, $user, $password, $dbName)
    {
        $this->mysqli = new \mysqli($host, $user, $password, $dbName);
        if ($this->mysqli->connect_errno) {
            die('Failed to connect to MySQL: ' . $this->mysqli->connect_errno . ' ' . $this->mysqli->connect_error);
        }

        $this->tableCheck();
    }

    private function tableCheck() {
        $expectedTables = array('slackChannelCache', 'slackOAuth', 'slackTeamSubscriptions', 'slackUserCache');

        $stmt = $this->mysqli->prepare("SHOW TABLES");

        $stmt->execute();

        $res = $stmt->get_result();

        for ($row_no = ($res->num_rows - 1); $row_no >= 0; $row_no--) {
            $res->data_seek($row_no);

            $row = $res->fetch_array();

            if ($row && $row[0]) {
                $key = array_search($row[0], $expectedTables);

                if (false !== $key) {
                    unset($expectedTables[$key]);
                }
            }
        }

        count($expectedTables) == 0 || die('Missing Tables in DB: ' . implode(', ', $expectedTables));
    }

    /**
     * Gets the OAuth record for a given team ID.
     * @param $teamId String Slack team ID
     * @return array with fields named the same as the setter's parameters
     */
    public function getSlackTeamOAuth($teamId) {
        $stmt = $this->mysqli->prepare("SELECT * FROM slackOAuth WHERE teamId = ?");
        $stmt->bind_param("s", $teamId);

        $stmt->execute();

        $res = $stmt->get_result();

        return $res->fetch_assoc();
    }

    /**
     * Sets an OAuth record for a team, can also be called to update a record.
     * @param $teamId
     * @param $accessToken
     * @param $scope
     * @param $teamName
     * @param $botUserId
     * @param $botAccessToken
     * @return bool true on successful insert/update
     */
    public function setSlackTeamOAuth($teamId, $accessToken, $scope, $teamName, $botUserId, $botAccessToken) {
        $stmt = $this->mysqli->prepare("INSERT INTO slackOAuth (teamId, accessToken, scope, teamName, "
            . "botUserId, botAccessToken) VALUES (?, ?, ?, ?, ?, ?) "
            . "ON DUPLICATE KEY UPDATE accessToken = VALUES(accessToken), scope = VALUES(scope), "
            . "teamName = VALUES(teamName), botUserId = VALUES(botUserId), botAccessToken = VALUES(botAccessToken)");
        $stmt->bind_param('ssssss', $teamId, $accessToken, $scope, $teamName, $botUserId, $botAccessToken);

        $stmt->execute();

        if ($stmt->error) {
            error_log("DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    public function setSlackTeamOAuthAddedByUser($teamId, $addedByUserId) {
        $stmt = $this->mysqli->prepare("UPDATE slackOAuth SET addedByUserId = ? WHERE teamId = ?");
        $stmt->bind_param('ss', $addedByUserId, $teamId);

        $stmt->execute();

        if ($stmt->error) {
            error_log("DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Gets the cached Slack Channel info for the given ID.
     * @param $channelId String Slack channel ID
     * @return array with fields named the same as the setter's parameters
     */
    public function getSlackChannelCache($channelId) {
        $stmt = $this->mysqli->prepare("SELECT * FROM slackChannelCache WHERE channelId = ?");
        $stmt->bind_param("s", $channelId);

        $stmt->execute();

        $res = $stmt->get_result();

        return $res->fetch_assoc();
    }

    /**
     * @param $teamId
     * @param $channelId
     * @param $channelName
     * @param $channelType
     * @param $joined
     * @return bool
     */
    public function setSlackChannelCache($teamId, $channelId, $channelName, $channelType, $joined) {
        $stmt = $this->mysqli->prepare("INSERT INTO slackChannelCache (teamId, channelId, lastAccess, channelName, "
            . "channelType, joined) VALUES (?, ?, now(), ?, ?, ?) "
            . "ON DUPLICATE KEY UPDATE teamId = VALUES(teamId), channelId = VALUES(channelId), lastAccess = now(), "
            . "channelName = VALUES(channelName), channelType = VALUES(channelType), joined = VALUES(joined)");
        $stmt->bind_param('ssssi', $teamId, $channelId, $channelName, $channelType, $joined);

        $stmt->execute();

        if ($stmt->error) {
            error_log("DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    public function getSlackUserCache($userId) {
        $stmt = $this->mysqli->prepare("SELECT * FROM slackUserCache WHERE userId = ?");
        $stmt->bind_param("s", $userId);

        $stmt->execute();

        $res = $stmt->get_result();

        return $res->fetch_assoc();
    }

    public function setSlackUserCache($teamId, $userId, $userName) {
        $stmt = $this->mysqli->prepare("INSERT INTO slackUserCache (teamId, userId, lastAccess, userName) "
            . "VALUES (?, ?, now(), ?) "
            . "ON DUPLICATE KEY UPDATE teamId = VALUES(teamId), userId = VALUES(userId), lastAccess = now(), "
            . "userName = VALUES(userName)");
        $stmt->bind_param('sss', $teamId, $userId, $userName);

        $stmt->execute();

        if ($stmt->error) {
            error_log("DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    public function getTBAApiCache($apiCall) {
        $stmt = $this->mysqli->prepare("SELECT apiCall, "
            . "DATE_FORMAT(lastModified, '%a, %e %b %Y %H:%i:%s GMT') as lastModified, "
            . "UNIX_TIMESTAMP(CONVERT_TZ(lastModified, '+00:00', @@global.time_zone)) as lastModifiedUnix, "
            . "apiJsonString, UNIX_TIMESTAMP(lastRetrieval) as lastRetrieval FROM tbaApiCache WHERE apiCall = ?");
        $stmt->bind_param("s", $apiCall);

        $stmt->execute();

        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            return $res->fetch_assoc();
        } else {
            return false;
        }
    }

    public function setTBAApiCache($apiCall, $lastModified, $json) {
        $stmt = $this->mysqli->prepare("INSERT INTO tbaApiCache (apiCall, lastModified, apiJsonString, lastRetrieval) "
            . "VALUES (?, STR_TO_DATE(?, '%a, %e %b %Y %H:%i:%s GMT'), ?, now()) "
            . "ON DUPLICATE KEY UPDATE apiCall = VALUES(apiCall), lastModified = VALUES(lastModified), "
            . "apiJsonString = VALUES(apiJsonString), lastRetrieval = now()");
        $stmt->bind_param('sss', $apiCall, $lastModified, $json);

        $stmt->execute();

        if ($stmt->error) {
            error_log("DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    public function setTBAApiCacheChecked($apiCall) {
        $stmt = $this->mysqli->prepare("UPDATE tbaApiCache SET lastRetrieval = now() WHERE apiCall = ?");
        $stmt->bind_param('s', $apiCall);

        $stmt->execute();

        if ($stmt->error) {
            error_log("DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    public function getSlackTeamSubscription($teamId, $channelId, $frcTeam) {
        $stmt = $this->mysqli->prepare("SELECT * FROM slackTeamSubscriptions WHERE teamId = ? AND channelId = ? "
            . "AND frcTeam = ?");
        $stmt->bind_param("ssi", $teamId, $channelId, $frcTeam);

        $stmt->execute();

        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            return $res->fetch_assoc();
        } else {
            return false;
        }
    }

    public function getSlackTeamSubscriptions($teamId, $channelId) {
        $stmt = $this->mysqli->prepare("SELECT * FROM slackTeamSubscriptions WHERE teamId = ? AND channelId = ?");
        $stmt->bind_param("ss", $teamId, $channelId);

        $stmt->execute();

        $res = $stmt->get_result();

        $subs = null;

        for ($row_no = ($res->num_rows - 1); $row_no >= 0; $row_no--) {
            $res->data_seek($row_no);

            $subs[] = $res->fetch_assoc();
        }

        return $subs;
    }

    public function setSlackTeamSubscription($teamId, $channelId, $frcTeam, $subscriptionType, $userId) {
        $stmt = $this->mysqli->prepare("INSERT INTO slackTeamSubscriptions (teamId, channelId, frcTeam, "
            . "subscriptionType, subscribedByUserId) VALUES (?, ?, ?, ?, ?) "
            . "ON DUPLICATE KEY UPDATE subscriptionType = VALUES(subscriptionType), "
            . "subscribedByUserId = VALUES(subscribedByUserId)");
        $stmt->bind_param('ssiss', $teamId, $channelId, $frcTeam, $subscriptionType, $userId);

        $stmt->execute();

        if ($stmt->error) {
            error_log("DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    public function deleteSlackTeamSubscription($teamId, $channelId, $frcTeam) {
        $stmt = $this->mysqli->prepare("DELETE FROM slackTeamSubscriptions WHERE teamId = ? AND channelId = ? "
            . "AND frcTeam = ?");
        $stmt->bind_param('ssi', $teamId, $channelId, $frcTeam);

        $stmt->execute();

        if ($stmt->error) {
            error_log("DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    public function logMessage($teamId, $channelId, $sendingUserId, $commandString) {
        $stmt = $this->mysqli->prepare("INSERT INTO botMessageLog (teamId, channelId, sendingUserId, commandString) "
            . "VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $teamId, $channelId, $sendingUserId, $commandString);

        $stmt->execute();

        if ($stmt->error) {
            error_log("DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }
}