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
 * Utility Functions
 * @author Brian Rozmierski
 */

include_once ('dbcheck.php');

function loadSettings() {
    if (!TBASLACKBOT && file_exists('../settings.php')) {
        include_once('../settings.php');
    } elseif (!TBASLACKBOT) {
        die("Please configure the bot by copying settings.php.dist to settings.php and update it for "
            . " your installation\n");
    }
}