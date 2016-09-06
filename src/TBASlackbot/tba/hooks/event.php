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
 * Handles intake and responses for the TBA Fire hose.
 * @author Brian Rozmierski
 */

require_once('../../settings.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    exit;
}

$json = file_get_contents('php://input');
$obj = json_decode($json);

if ($obj == null) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    exit;
}

if (!isset($obj->message_type)) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request', true, 400);
    exit;
}

$checksum = sha1(TBASLACKBOT_TBA_VERIFICATION_STRING . $json);
if ($checksum !== $_SERVER['HTTP_X_TBA_CHECKSUM']) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 406 Not Acceptable', true, 406);
    exit;
}

$queue = msg_get_queue(TBASLACKBOT_TBA_MQID);
$status = msg_send($queue, 1, $json, false, false);
if (!$status) {
    error_log("Error queueing TBA event");
}
