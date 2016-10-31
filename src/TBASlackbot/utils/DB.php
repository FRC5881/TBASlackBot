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


namespace TBASlackbot\utils;

/**
 * Database Interface class. All database interactions should be done through this class.
 * @author Brian Rozmierski
 */
class DB
{

    private $mysqli;

    /**
     * DB constructor. Inherits values from the settings.php file.
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
     *
     * @param String $host MySQL hostname
     * @param String $user MySQL Username
     * @param String $password MySQL password
     * @param String $dbName MySQL database name
     */
    public function __construct1($host, $user, $password, $dbName)
    {
        $this->mysqli = new \mysqli($host, $user, $password, $dbName);
        if ($this->mysqli->connect_errno) {
            die('Failed to connect to MySQL: ' . $this->mysqli->connect_errno . ' ' . $this->mysqli->connect_error);
        }

        $this->tableCheck();
    }

    /**
     * Check to make sure the required tables are present in the database, does not check fields, however.
     */
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
     *
     * @param String $teamId Slack team ID
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
     *
     * @param String $teamId Slack TeamId
     * @param String $accessToken Slack App access token
     * @param String $scope OAuth scope granted for the app token
     * @param String $teamName Slack team name
     * @param String $botUserId Slack bot UserId
     * @param String $botAccessToken Bot Slack access token
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
            error_log("setSlackTeamOAuth DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Adds the UserId of the user who authorized the app installation to the slackOAuth table.
     *
     * @param String $teamId Slack TeamId
     * @param String $addedByUserId Slack UserId that added the application
     * @return bool true on successful update
     */
    public function setSlackTeamOAuthAddedByUser($teamId, $addedByUserId) {
        $stmt = $this->mysqli->prepare("UPDATE slackOAuth SET addedByUserId = ? WHERE teamId = ?");
        $stmt->bind_param('ss', $addedByUserId, $teamId);

        $stmt->execute();

        if ($stmt->error) {
            error_log("setSlackTeamOAuthAddedByUser DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Gets the cached Slack Channel info for the given ID.
     *
     * @param String $channelId Slack channel ID
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
     * Gets the cached Slack Channel info for the given team, channel name, and type.
     *
     * @param String $teamId Slack team ID
     * @param String $name Slack Channel Name
     * @param String $type Slack Channel Type
     * @return array with fields named the same as the setter's parameters
     */
    public function getSlackChannelCacheByNameAndType($teamId, $name, $type) {
        $stmt = $this->mysqli
            ->prepare("SELECT * FROM slackChannelCache WHERE teamId = ? AND channelName = ? AND channelType = ?");
        $stmt->bind_param("sss", $teamId, $name, $type);

        $stmt->execute();

        $res = $stmt->get_result();

        return $res->fetch_assoc();
    }

    /**
     * Insert or update the Slack channel cache.
     *
     * @param String $teamId Slack TeamId
     * @param String $channelId Slack ChannelId
     * @param String $channelName Slack channel name
     * @param String $channelType Channel type matching enum on the channelType field on the slackChannelCache table
     * @param bool $joined true if the bot user is joined to the channel
     * @return bool true on successful insert/update
     */
    public function setSlackChannelCache($teamId, $channelId, $channelName, $channelType, $joined) {
        $stmt = $this->mysqli->prepare("INSERT INTO slackChannelCache (teamId, channelId, lastAccess, channelName, "
            . "channelType, joined) VALUES (?, ?, now(), ?, ?, ?) "
            . "ON DUPLICATE KEY UPDATE teamId = VALUES(teamId), channelId = VALUES(channelId), lastAccess = now(), "
            . "channelName = VALUES(channelName), channelType = VALUES(channelType), joined = VALUES(joined)");
        $stmt->bind_param('ssssi', $teamId, $channelId, $channelName, $channelType, $joined);

        $stmt->execute();

        if ($stmt->error) {
            error_log("setSlackChannelCache DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Gets the cached Slack User information for the given Id
     * @param string $userId Slack UserId
     * @return array with fields named the same as the setter's parameters as well as 'lastAccess'
     */
    public function getSlackUserCache($userId) {
        $stmt = $this->mysqli->prepare("SELECT * FROM slackUserCache WHERE userId = ?");
        $stmt->bind_param("s", $userId);

        $stmt->execute();

        $res = $stmt->get_result();

        return $res->fetch_assoc();
    }

    /**
     * Insert or update the Slack user cache.
     *
     * @param string $teamId Slack TeamId
     * @param string $userId Slack UserId
     * @param string $userName User name
     * @return bool true on successful insert/update
     */
    public function setSlackUserCache($teamId, $userId, $userName) {
        $stmt = $this->mysqli->prepare("INSERT INTO slackUserCache (teamId, userId, lastAccess, userName) "
            . "VALUES (?, ?, now(), ?) "
            . "ON DUPLICATE KEY UPDATE teamId = VALUES(teamId), userId = VALUES(userId), lastAccess = now(), "
            . "userName = VALUES(userName)");
        $stmt->bind_param('sss', $teamId, $userId, $userName);

        $stmt->execute();

        if ($stmt->error) {
            error_log("setSlackUserCache DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Gets a cached TBA API call.
     *
     * @param string $apiCall TBA API URL stub containing the API call
     * @return array|bool false if no records returned, or an array with the 'apiCall', 'lastModified' formatted for
     * the HTTP header, 'lastModifiedUnix' as a UNIX timestamp, 'apiJsonString', and 'lastRetrieval'
     */
    public function getTBAApiCache($apiCall) {
        $stmt = $this->mysqli->prepare("SELECT apiCall, "
            . "DATE_FORMAT(lastModified, '%a, %e %b %Y %H:%i:%s GMT') as lastModified, "
            . "UNIX_TIMESTAMP(CONVERT_TZ(lastModified, '+00:00', @@global.time_zone)) as lastModifiedUnix, "
            . "apiJsonString, UNIX_TIMESTAMP(lastRetrieval) as lastRetrieval, UNIX_TIMESTAMP(expires) as expires "
            . "FROM tbaApiCache WHERE apiCall = ?");
        $stmt->bind_param("s", $apiCall);

        $stmt->execute();

        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            return $res->fetch_assoc();
        } else {
            return false;
        }
    }

    /**
     * Insert or update the TBA API cache.
     *
     * @param string $apiCall TBA API URL stub containing the API call
     * @param string $lastModified HTTP RFC formatted date string of last modification by the TBA server
     * @param string $json raw JSON string returned by the API
     * @param int $expires number of seconds from now the cached entry expires
     * @return bool true on successful insert/update
     */
    public function setTBAApiCache($apiCall, $lastModified, $json, $expires) {
        $stmt = $this->mysqli->prepare("INSERT INTO tbaApiCache "
            . "(apiCall, lastModified, apiJsonString, lastRetrieval, expires) "
            . "VALUES (?, STR_TO_DATE(?, '%a, %e %b %Y %H:%i:%s GMT'), ?, now(), "
                . "FROM_UNIXTIME(UNIX_TIMESTAMP(NOW()) + ?)) "
            . "ON DUPLICATE KEY UPDATE apiCall = VALUES(apiCall), lastModified = VALUES(lastModified), "
            . "apiJsonString = VALUES(apiJsonString), lastRetrieval = now(), expires = VALUES(expires)");
        $stmt->bind_param('sssi', $apiCall, $lastModified, $json, $expires);

        $stmt->execute();

        if ($stmt->error) {
            error_log("setTBAApiCache DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Updates the lastRetrieval time for a given API call, used when the cache expired but the TBA version hasn't
     * changed.
     *
     * @param string $apiCall TBA API URL stub containing the API call
     * @param int $expires number of seconds from now the cached entry expires
     * @return bool true on successful update
     */
    public function setTBAApiCacheChecked($apiCall, $expires) {
        $stmt = $this->mysqli->prepare("UPDATE tbaApiCache SET lastRetrieval = now(), "
            . "expires = FROM_UNIXTIME(UNIX_TIMESTAMP(NOW()) + ?) WHERE apiCall = ?");
        $stmt->bind_param('is', $expires, $apiCall);

        $stmt->execute();

        if ($stmt->error) {
            error_log("setTBAApiCacheChecked DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Gets an individual team subscription record.
     *
     * @param string $teamId Slack TeamId
     * @param string $channelId Slack ChannelId
     * @param int $frcTeam FRC team number
     * @return array|bool false if no subscription found, or an array with 'teamId', 'channelId', 'frcTeam',
     * 'subscriptionType' and 'subscribedByUserId'
     */
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

    /**
     * Gets a list of FRC teams subscribed to by a channel.
     *
     * @param string $teamId Slack TeamId
     * @param string $channelId Slack ChannelId
     * @return array[]|bool false if no subscription found, or an array of arrays with 'teamId', 'channelId', 'frcTeam',
     * 'subscriptionType' and 'subscribedByUserId'
     */
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

    /**
     * Gets a list of FRC teams subscribed to by a channel.
     *
     * @param int $frcTeamNumber FRC Team Number
     * @return array[]|bool false if no subscription found, or an array of arrays with 'teamId', 'channelId', 'frcTeam',
     * 'subscriptionType' and 'subscribedByUserId'
     */
    public function getSlackFRCTeamSubscriptions($frcTeamNumber) {
        $stmt = $this->mysqli->prepare("SELECT * FROM slackTeamSubscriptions WHERE frcTeam = ?");
        $stmt->bind_param("i", $frcTeamNumber);

        $stmt->execute();

        $res = $stmt->get_result();

        $subs = null;

        for ($row_no = ($res->num_rows - 1); $row_no >= 0; $row_no--) {
            $res->data_seek($row_no);

            $subs[] = $res->fetch_assoc();
        }

        return $subs;
    }

    /**
     * Insert or update a subscription.
     *
     * @param string $teamId Slack TeamId
     * @param string $channelId Slack ChannelId
     * @param int $frcTeam FRC team number
     * @param string $subscriptionType Subscription type, 'all', 'result' or 'summary'
     * @param string $userId Slack UserId who requested or updated the subscription
     * @return bool true on successful insert/update
     */
    public function setSlackTeamSubscription($teamId, $channelId, $frcTeam, $subscriptionType, $userId) {
        $stmt = $this->mysqli->prepare("INSERT INTO slackTeamSubscriptions (teamId, channelId, frcTeam, "
            . "subscriptionType, subscribedByUserId) VALUES (?, ?, ?, ?, ?) "
            . "ON DUPLICATE KEY UPDATE subscriptionType = VALUES(subscriptionType), "
            . "subscribedByUserId = VALUES(subscribedByUserId)");
        $stmt->bind_param('ssiss', $teamId, $channelId, $frcTeam, $subscriptionType, $userId);

        $stmt->execute();

        if ($stmt->error) {
            error_log("setSlackTeamSubscription DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Delete a subscription to an FRC team.
     *
     * @param string $teamId Slack TeamId
     * @param string $channelId Slack ChannelId
     * @param int $frcTeam FRC team number
     * @return bool false on error
     */
    public function deleteSlackTeamSubscription($teamId, $channelId, $frcTeam) {
        $stmt = $this->mysqli->prepare("DELETE FROM slackTeamSubscriptions WHERE teamId = ? AND channelId = ? "
            . "AND frcTeam = ?");
        $stmt->bind_param('ssi', $teamId, $channelId, $frcTeam);

        $stmt->execute();

        if ($stmt->error) {
            error_log("deleteSlackTeamSubscription DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Log feedback from a user received by the bot.
     *
     * @param string $teamId Slack TeamId
     * @param string $channelId Slack ChannelId
     * @param string $sendingUserId Slack UserId who sent the command
     * @param string $feedback Feedback provided by the user
     * @return bool false on error
     */
    public function logFeedback($teamId, $channelId, $sendingUserId, $feedback) {
        $stmt = $this->mysqli->prepare("INSERT INTO botFeedbackLog (teamId, channelId, userId, feedback) "
            . "VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $teamId, $channelId, $sendingUserId, $feedback);

        $stmt->execute();

        if ($stmt->error) {
            error_log("logFeedback DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Gets any queued feedback replies
     *
     * @param string $teamId Slack TeamId
     * @param string $userId Slack UserId
     * @return array[]|bool false if no pending replies found, or an array of arrays with 'teamId', 'userId',
     * 'feedback', 'messageTime', 'replyText', and 'id' (of the reply)
     */
    public function getQueuedFeedbackReplies($teamId, $userId) {
        $stmt = $this->mysqli->prepare("SELECT l.teamId, l.userId, l.feedback, l.messageTime, r.replyEnteredAt, "
            . "r.replyText, r.id FROM botFeedbackReply r JOIN botFeedbackLog l ON r.feedbackId = l.id "
            . "WHERE r.isSent = 0 AND l.teamId = ? AND l.userId = ?");
        $stmt->bind_param("ss", $teamId, $userId);

        $stmt->execute();

        $res = $stmt->get_result();

        $replies = null;

        for ($row_no = ($res->num_rows - 1); $row_no >= 0; $row_no--) {
            $res->data_seek($row_no);

            $replies[] = $res->fetch_assoc();
        }

        return $replies;
    }

    /**
     * Marks a given feedback reply as sent
     *
     * @param int $feedbackReplyId Feedback Reply ID to mark sent
     * @return bool false if an error occurred, or true if set
     */
    public function setFeedbackReplySent($feedbackReplyId) {
        $stmt = $this->mysqli->prepare("UPDATE botFeedbackReply SET replySentAt = now(), isSent = 1 WHERE id = ?");
        $stmt->bind_param('i', $feedbackReplyId);

        $stmt->execute();

        if ($stmt->error) {
            error_log("setFeedbackReplySent DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }

    /**
     * Log a message received by the bot.
     *
     * @param string $teamId Slack TeamId
     * @param string $channelId Slack ChannelId
     * @param string $sendingUserId Slack UserId who sent the command
     * @param string $commandString In a DM, the entire message, or in another channel words past the trigger word
     * interpreted as a command
     * @return bool false on error
     */
    public function logMessage($teamId, $channelId, $sendingUserId, $commandString) {
        $stmt = $this->mysqli->prepare("INSERT INTO botMessageLog (teamId, channelId, sendingUserId, commandString) "
            . "VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $teamId, $channelId, $sendingUserId, $commandString);

        $stmt->execute();

        if ($stmt->error) {
            error_log("logMessage DB Error: " . $stmt->error);
            return false;
        }

        return true;
    }
}