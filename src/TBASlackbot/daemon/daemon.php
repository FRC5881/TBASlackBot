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
 * Starts the Bot Daemon process
 * @author Brian Rozmierski
 */

include_once ('../settings.php');

$slackQ = msg_get_queue(TBASLACKBOT_SLACK_MQID);
$tbaQ = msg_get_queue(TBASLACKBOT_TBA_MQID);

$maxMessageSize = 8 * 1024 * 1024; // 8MB

while (true) {

    // Slack first
    $gotSlackMsg = msg_receive($slackQ, 0, $msgType, $maxMessageSize, $message, false, MSG_IPC_NOWAIT);

    if ($gotSlackMsg) {
        echo "\n" . $message . "\n";
        \TBASlackbot\slack\ProcessEvent::processEvent($message);
    }


    // TBA Next
    $gotTBAMsg = msg_receive($tbaQ, 0, $msgType, $maxMessageSize, $message, false, MSG_IPC_NOWAIT);

    if ($gotTBAMsg) {
        echo "\n" . $message . "\n";
        \TBASlackbot\tba\ProcessEvent::processEvent($message);
    }

    if (!$gotSlackMsg && !$gotTBAMsg) {
        echo ".";
        sleep(1);
    }
}