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
 * Checks the make sure the database is ready and configured.
 * @author Brian Rozmierski
 */

function checkDatabase() {
    if (!TBASLACKBOT_MYSQL_DB || !TBASLACKBOT_MYSQL_HOST || !TBASLACKBOT_MYSQL_PASS || !TBASLACKBOT_MYSQL_USER)
        return false;

    $mysqli = new mysqli(TBASLACKBOT_MYSQL_HOST, TBASLACKBOT_MYSQL_USER, TBASLACKBOT_MYSQL_PASS, TBASLACKBOT_MYSQL_DB);
    if ($mysqli->connect_errno) {
        die('Failed to connect to MySQL: ' . $mysqli->connect_errno . ' ' . $mysqli->connect_error);
    }

    return true;
}
