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
use Slack\DirectMessageChannel;
use Slack\User;
use TBASlackbot\utils\DB;

/**
 * Responsible for app setup and welcome to the installing user.
 * @author Brian Rozmierski
 */
class SlackSetup
{

    /**
     * @var string Slack TeamId
     */
    private $teamId;

    /**
     * @var DB Initialized DB Object
     */
    private $db;

    /**
     * @var array In the form matching the slackOAuth MySQL table
     */
    private $oauth;

    /**
     * @var User Slack User object representing the User who installed the app
     */
    private $setupUser;

    /**
     * @var User Slack User object representing the Bot user
     */
    private $botUser;

    /**
     * Create a SlackSetup object for a given Slack Team and perform the initial setup, including creating OAuth cache
     *records, channel cache records, and sending the bot welcome message.
     *
     * @param string $teamId Slack team ID to setup
     */
    public function __construct($teamId)
    {
        $this->teamId = $teamId;

        $this->db = new DB();

        $this->oauth = $this->db->getSlackTeamOAuth($teamId);

        if ($this->oauth['addedByUserId'])
            return; // If this is set, presume the setup is complete.

        $this->getSetupUser();
        $this->getBotUser();

        if ($this->setupUser && $this->botUser) {
            $this->setupPrivateChannel();
        } else {
            error_log("Error getting setup and/or bot users");
        }
    }

    /**
     * Calls the Slack API as the application to get the User object who installed the application.
     */
    private function getSetupUser() {
        $loop = Factory::create();
        $client = new ApiClient($loop);
        $client->setToken($this->oauth['accessToken']);

        $client->getAuthedUser()->then(function (User $user) {
            $this->setupUser = $user;
        });

        $loop->run();
    }

    /**
     * Calls the Slack API as the bot to get the User object for the bot.
     */
    private function getBotUser() {
        $loop = Factory::create();
        $client = new ApiClient($loop);
        $client->setToken($this->oauth['botAccessToken']);

        $client->getAuthedUser()->then(function (User $user) {
            $this->botUser = $user;
        });

        $loop->run();
    }

    /**
     * Sets up the DM channel between the installing user and the bot, adds caches, sends the welcome, and updates the
     * OAuth record accordingly.
     */
    private function setupPrivateChannel() {
        $loop = Factory::create();
        $client = new ApiClient($loop);
        $client->setToken($this->oauth['botAccessToken']);
        $client->getDMByUserId($this->setupUser->getId())->then(function (DirectMessageChannel $dm) use ($client) {
            $this->db->setSlackChannelCache($this->teamId, $dm->getId(), '@'.$this->setupUser->getUsername(), 'im',
                true);
            $client->send("Hello! I'm The Blue Alliance Slackbot, <@" . $this->botUser->getId() . "|"
                . $this->botUser->getUsername() . ">! I can monitor your favorite FRC "
                . "teams either in private, or public channels. I can also lookup team information and competition "
                . "status, records, and rankings. Just ask me for *_help_* and I can tell you more.", $dm);
            $this->db->setSlackTeamOAuthAddedByUser($this->teamId, $this->setupUser->getId());
        });

        $loop->run();
    }
}