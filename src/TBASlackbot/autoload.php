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
 * Loads all TBASlackbot classes.
 * @author Brian Rozmierski
 */

require_once('slack/Channels.php');
require_once('slack/ProcessEvent.php');
require_once('slack/ProcessMessage.php');
require_once('slack/SlackSetup.php');
require_once('slack/Users.php');
require_once('slack/commands/Help.php');
require_once('slack/commands/Subscription.php');
require_once('slack/commands/TeamInformation.php');
require_once('tba/ProcessEvent.php');
require_once('tba/TBAClient.php');
require_once('tba/objects/Alliances.php');
require_once('tba/objects/Award.php');
require_once('tba/objects/District.php');
require_once('tba/objects/Event.php');
require_once('tba/objects/EventAlliance.php');
require_once('tba/objects/EventAlliances.php');
require_once('tba/objects/EventMatch.php');
require_once('tba/objects/EventMatches.php');
require_once('tba/objects/EventRanking.php');
require_once('tba/objects/EventRankings.php');
require_once('tba/objects/Status.php');
require_once('tba/objects/Team.php');
require_once('tba/objects/webhooks/CompetitionLevelStarting.php');
require_once('tba/objects/webhooks/UpcomingMatch.php');
require_once('tba/objects/yearspecific/ScoreBreakdown2016.php');
require_once('utils/Analytics.php');
require_once('utils/DB.php');
require_once('utils/Random.php');

if (!defined('PHP_INT_MIN')) {
    define('PHP_INT_MIN', -9223372036854775808);
}