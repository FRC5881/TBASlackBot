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
 * OAuth callback endpoint
 * @author Brian Rozmierski
 */

require_once('../../settings.php');

$redirect_uri = TBASLACKBOT_URLBASE . 'slack/oauth/callback.php';

$provider = new \AdamPaterson\OAuth2\Client\Provider\Slack([
    'clientId'          => TBASLACKBOT_SLACK_CLIENT_ID,
    'clientSecret'      => TBASLACKBOT_SLACK_CLIENT_SECRET,
    'redirectUri'       => $redirect_uri,
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

} else {

    $db = new \TBASlackbot\utils\DB();

    $loop = \React\EventLoop\Factory::create();

    $client = new \Slack\ApiClient($loop);

    $client->apiCall('oauth.access', ['client_id' => TBASLACKBOT_SLACK_CLIENT_ID,
        'client_secret' => TBASLACKBOT_SLACK_CLIENT_SECRET, 'code' => $_GET['code'],
        'redirect_uri' => $redirect_uri]) -> then(function (\Slack\Payload $response) use ($db) {
            $data = $response->getData();
            if ($data['team_id']) {
                $db->setSlackTeamOAuth($data['team_id'], $data['access_token'], $data['scope'],
                    $data['team_name'], $data['bot']['bot_user_id'], $data['bot']['bot_access_token']);
                //echo "Db Updated...<br/>\n";
                new \TBASlackbot\slack\SlackSetup($data['team_id']);
            } else {
                //echo "Something's wrong....<br/>";
                //var_dump($data);
            }
    });

    //echo "Running loop....<br/>\n";

    $loop->run();

    //echo "Loop done...<br/>\n";



    header('Location: https://frc5881.github.io/TBASlackBot/complete/');
}