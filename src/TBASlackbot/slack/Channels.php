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
use Slack\Channel;
use Slack\DirectMessageChannel;
use Slack\Group;
use TBASlackbot\utils\DB;

/**
 * Channel Management and Utils.
 * @author Brian Rozmierski
 */
class Channels
{

    /**
     * @var int Maximum amount of time, in seconds, to use the cached Slack channel information.
     */
    private static $MAX_AGE = 2 * 24 * 26 * 60; // 2 days in seconds

    /**
     * Gets the cached Slack channel info, or if not previously cached (or out of date), refreshes it.
     *
     * @param String $teamId Slack team id
     * @param String $channelId Slack channel id
     * @return array cached channel information, array fields are named per the MySQL slackChannelCache table
     */
    public static function getChannelCache($teamId, $channelId) {
        $db = new DB();

        $cached = $db->getSlackChannelCache($channelId);

        if ($cached && ($cached['lastAccess'] + Channels::$MAX_AGE) >= time()) {
            return $cached;
        }

        $oauth = $db->getSlackTeamOAuth($teamId);

        $loop = Factory::create();
        $client = new ApiClient($loop);
        $client->setToken($oauth['botAccessToken']);

        switch (substr($channelId, 0, 1)) {
            case "C": // Standard channel
                $client->getChannelById($channelId)->then(function (Channel $channel) use ($db, $oauth) {
                    $db->setSlackChannelCache($oauth['teamId'], $channel->getId(), $channel->getName(), 'channel',
                        $channel->data['is_member']);
                });
                break;
            case "D": // IM channel
                $client->getDMById($channelId)->then(function (DirectMessageChannel $channel) use ($db, $oauth) {
                    $username = Users::getUserCache($oauth['teamId'], $channel->data['user'])['userName'];
                    $db->setSlackChannelCache($oauth['teamId'], $channel->getId(), '@'.$username, 'im', true);
                });
                break;
            case "G": // Group OR mpim channel
                $client->getGroupById($channelId)->then(function (Group $channel) use ($db, $oauth) {
                    $db->setSlackChannelCache($oauth['teamId'], $channel->getId(), $channel->getName(),
                        $channel->data['is_mpim'] === true ? 'mpim' : 'group', true);
                });
                break;
            default:
                // WTF are we doing here
                error_log("Unknown channel type detection for $channelId");
        }

        $success = false;

        try {
            $loop->run();
            $success = true;
        } catch (\Exception $e) {
            error_log("\nException in getChannelCache: " . $e->getMessage() . "\n");
        }

        if (!$success) {
            error_log("\nRetrying Last Channel Request\n");

            try {
                $loop->run();
            } catch (\Exception $e) {
                error_log("\nException in retry getChannelCache: " . $e->getMessage() . "\n");
            }
        }

        return $db->getSlackChannelCache($channelId);
    }

    /**
     * Creates a ChannelInterface-type object suitable for use in the Slack API library.
     *
     * @param array $channelCache Array matching output from the slackChannelCache MySQL table
     * @param ApiClient $client Initialized Slack ApiClient object
     * @return null|Channel|DirectMessageChannel|Group Null returned for null ChannelCache or a mismatched
     * channelType, otherwise appropriate Channel object is returned to match
     */
    public static function getChannelInterfaceFromCache($channelCache, ApiClient $client) {
        if ($channelCache) {
            switch ($channelCache['channelType']) {
                case "channel": // Standard channel
                    return new Channel($client, array('id' => $channelCache['channelId']));
                case "im": // IM channel
                    return new DirectMessageChannel($client, array('id' => $channelCache['channelId']));
                case "mpim": // Group OR mpim channel
                case "group":
                    return new Group($client, array('id' => $channelCache['channelId']));
            }
        }

        return null;
    }
}