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


namespace TBASlackbot\slack;


use React\EventLoop\Factory;
use Slack\ApiClient;
use Slack\User;
use TBASlackbot\utils\DB;

/**
 * Slack User management and utilities.
 * @author Brian Rozmierski
 */
class Users
{
    /**
     * @var int Maximum amount of time, in seconds, to use the cached Slack user information.
     */
    private static $MAX_AGE = 2 * 24 * 26 * 60; // 2 days in seconds

    /**
     * Gets a cached user object for the given user on the given team. Result is the cached object, but if the existing
     * cached object has expired, a call the the Slack API will be made to refresh.
     *
     * @param String $teamId Slack TeamId
     * @param String $userId Slack UserId
     * @return array Array fields match the columns of the slackUserCache table
     */
    public static function getUserCache($teamId, $userId) {
        $db = new DB();

        $cached = $db->getSlackUserCache($userId);

        if ($cached && ($cached['lastAccess'] + Users::$MAX_AGE) >= time()) {
            return $cached;
        }

        $oauth = $db->getSlackTeamOAuth($teamId);

        $loop = Factory::create();
        $client = new ApiClient($loop);
        $client->setToken($oauth['accessToken']);

        $client->getUserById($userId)->then(function (User $user) use ($db, $teamId) {
            $db->setSlackUserCache($teamId, $user->getId(), $user->getUsername());
        });

        $success = false;

        try {
            $loop->run();
            $success = true;
        } catch (\Exception $e) {
            error_log("\nException in getUserCache: " . $e->getMessage() . "\n");
        }

        if (!$success) {
            error_log("\nRetrying Last User Request\n");

            try {
                $loop->run();
            } catch (\Exception $e) {
                error_log("\nException in retry getUserCache: " . $e->getMessage() . "\n");
            }
        }

        return $db->getSlackUserCache($userId);
    }
}