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
 * Slack User management and utilities.
 * @author Brian Rozmierski
 */

namespace TBASlackbot\slack;


use React\EventLoop\Factory;
use Slack\ApiClient;
use Slack\User;
use TBASlackbot\utils\DB;

class Users
{
    private static $MAX_AGE = 2 * 24 * 26 * 60; // 2 days in seconds

    public static function getUserCache($teamId, $userId) {
        $db = new DB();

        $cached = $db->getSlackUserCache($userId);

        if ($cached && $cached['lastAccess'] + Users::$MAX_AGE >= time()) {
            return $cached;
        }

        $oauth = $db->getSlackTeamOAuth($teamId);

        $loop = Factory::create();
        $client = new ApiClient($loop);
        $client->setToken($oauth['accessToken']);

        $client->getUserById($userId)->then(function (User $user) use ($db, $teamId) {
            $db->setSlackUserCache($teamId, $user->getId(), $user->getUsername());
        });

        $loop->run();

        return $db->getSlackUserCache($userId);
    }
}