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
use Ramsey\Uuid\Uuid;

/**
 * Analytics class for tracking bot usage.
 *
 * Event Categories: 'command' is a command from a user, interactively, 'notification' is a message sent as a result
 * of a TBA notification.
 * Event Action: Either the command name, or notification type.
 *
 * @package TBASlackbot\utils
 * @author Brian Rozmierski
 */
class Analytics
{
    /***
     * Track a Slack-User triggered command.
     *
     * @param string $teamId Slack TeamId
     * @param string $userId Slack UserId
     * @param array $channelCache Channel Cache for the Slack Channel
     * @param string $command tbabot command
     * @param int|null $team team referenced in command, must be null if eventKey specified
     * @param string|null $eventKey event key referenced in command, must be null if team specified
     */
    public static function trackSlackEvent($teamId, $userId, $channelCache, $command, $team, $eventKey) {
        if (!self::isEnabled()) {
            return;
        }

        $values['cid'] = self::generateUuid($teamId, $userId);
        $values['ec'] = 'command';
        $values['ea'] = $command;
        $values['el'] = $team ? 'team' : ($eventKey ? 'event' : null);
        $values['el'] = $team ? $team : ($eventKey ? $eventKey : null);
        $values['cd1'] = $teamId;
        $values['cd2'] = $userId;
        $values['cd3'] = $channelCache['channelType'];

        self::sendAnalytics($values);
    }

    /**
     * Track a TBA-Notification triggered message.
     *
     * @param string $teamId Slack TeamId
     * @param string $userId Slack UserId that created the subscription
     * @param array $channelCache Channel Cache for the Slack Channel
     * @param string $notificationType Type of notification sent
     * @param int|null $team team referenced in command, must be null if eventKey specified
     * @param string|null $eventKey event key referenced in command, must be null if team specified
     */
    public static function trackTBAEventNotification($teamId, $userId, $channelCache, $notificationType, $team,
                                                     $eventKey) {
        if (!self::isEnabled()) {
            return;
        }

        $values['cid'] = self::generateUuid($teamId, $userId);
        $values['ec'] = 'notification';
        $values['ea'] = $notificationType;
        $values['el'] = $team ? $team : ($eventKey ? $eventKey : null);
        $values['cd1'] = $teamId;
        $values['cd2'] = $userId;
        $values['cd3'] = $channelCache['channelType'];

        self::sendAnalytics($values);
    }

    /**
     * @return bool true if an Analytics ID is defined
     */
    private static function isEnabled() {
        return defined('TBASLACKBOT_GOOGLE_ANALYTICS_ID') && strlen(TBASLACKBOT_GOOGLE_ANALYTICS_ID) > 0;
    }

    /**
     * Generates a UUID based on the passed parameters.
     *
     * @param string|null $teamId Slack TeamId
     * @param string|null $userId Slack UserId
     * @return string UUID
     */
    private static function generateUuid($teamId, $userId) {
        $key = ($teamId ? $teamId : '') . ':' . ($userId ? $userId : '');

        return Uuid::uuid3(Uuid::NAMESPACE_X500, $key)->toString();
    }

    /**
     * @param array $values Array of string values with keys 'cid', 'ec', 'ea', 'el', 'cd1', 'cd2', and 'cd3'
     */
    private static function sendAnalytics($values) {
        $url = 'http://www.google-analytics.com/collect?v=1&tid=' . TBASLACKBOT_GOOGLE_ANALYTICS_ID . '&cid='
            . $values['cid'] . '&t=event&ec=' . $values['ec'] . '&ea=' . $values['ea'] . '&el=' . $values['el']
            . '&cd1=' . $values['cd1'] . '$cd2=' . $values['cd2'] . '&cd3=' . $values['cd3'] . '&ni=1&sc=end';

        $curl = "curl '". $url . "' > /dev/null 2>&1 &";

        exec($curl);
    }
}