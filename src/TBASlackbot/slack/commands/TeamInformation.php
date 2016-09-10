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


namespace TBASlackbot\slack\commands;

use Slack\Message\Attachment;
use TBASlackbot\slack\ProcessMessage;
use TBASlackbot\tba\TBAClient;

/**
 * Handles team information related commands and responses.
 * @package TBASlackbot\slack\commands
 */
class TeamInformation
{
    /**
     * Send the user basic info on a team.
     *
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param int $teamInfoRequestedFor FRC team number
     * @param string|null $replyTo User to @ mention in the reply, or null to not mention user
     */
    public static function processShortRequest($teamId, $channelCache, $teamInfoRequestedFor, $replyTo = null) {
        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);

        $teamInfoRequestedFor = 'frc' . $teamInfoRequestedFor;

        $team = $tba->getTeam($teamInfoRequestedFor);

        if ($team == null) {
            ProcessMessage::sendUnknownTeam($teamId, $channelCache);
            return;
        }

        $status = $tba->getTBAStatus();
        $district = $team->getDistrict($status->getCurrentSeason());

        ProcessMessage::sendReply($teamId, $channelCache, "Team " . $team->getTeamNumber() . ", " . $team->getNickname()
            . " out of " . $team->getLocation()
            . ($district == null ? '' : " in the " . $district->getName() . " region."), $replyTo);
    }

    /**
     * Send the user slightly more detailed info on a team.
     *
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param int $teamInfoRequestedFor FRC team number
     * @param string|null $replyTo User to @ mention in the reply, or null to not mention user
     */
    public static function processInfoRequest($teamId, $channelCache, $teamInfoRequestedFor, $replyTo = null) {
        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);

        $teamInfoRequestedFor = 'frc' . $teamInfoRequestedFor;

        $team = $tba->getTeam($teamInfoRequestedFor);

        if ($team == null) {
            ProcessMessage::sendUnknownTeam($teamId, $channelCache);
            return;
        }

        $status = $tba->getTBAStatus();
        $district = $team->getDistrict($status->getCurrentSeason());
        $record = $team->getTeamRecord($status->getCurrentSeason());

        ProcessMessage::sendReply($teamId, $channelCache, 'Team ' . $team->getTeamNumber() . ', '
            . ($team->getWebsite() == null ? '' : '<' . $team->getWebsite() . '|') . $team->getNickname()
            . ($team->getWebsite() == null ? '' : '>') . ' • From ' . $team->getLocation() . "\n"
            . ($team->getRookieYear() == null ? '' : 'Founded ' . $team->getRookieYear())
            . ($district == null ? '' : ' • ' . $district->getName() . ' District')
            . ($record == null ? '' : ' • ' . $record['wins'] . '-' . $record['losses']
                . '-' . $record['ties'] . ' in ' . $record['competitions'] . ' ' . $status->getCurrentSeason()
                . ' Events') . ' • ' . '<https://thebluealliance.com/team/' . $team->getTeamNumber() . '|View on TBA>',
            $replyTo);
    }

    /**
     * Send the user detailed information on a team.
     *
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param int $teamInfoRequestedFor FRC team number
     * @param string|null $replyTo User to @ mention in the reply, or null to not mention user
     */
    public static function processDetailRequest($teamId, $channelCache, $teamInfoRequestedFor, $replyTo = null) {
        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);

        $team = $tba->getTeam('frc' . $teamInfoRequestedFor);

        if ($team == null) {
            ProcessMessage::sendUnknownTeam($teamId, $channelCache);
            return;
        }

        $status = $tba->getTBAStatus();
        $district = $team->getDistrict($status->getCurrentSeason());
        $record = $team->getTeamRecord($status->getCurrentSeason());

        $events = $team->getTeamEvents($status->getCurrentSeason());

        $attachments = array();

        if ($events) {
            foreach($events as $event) {
                $rankings = $event->getEventRankings();
                $eventRecord = $event->getEventRecordForTeam($team->getTeamNumber());
                $highestCompLevel = $event->getHighestCompLevelForTeam($team->getTeamNumber());
                $rank = null;

                if ($rankings) {
                    $rank = $rankings->getRankingForTeam($team->getTeamNumber());
                }

                $eventText = "Team " . $team->getTeamNumber() . ($rank == null ? ' had' : " was ranked "
                        . $rank->getRank() . " of " . $rankings->getNumberOfRankedTeams())
                    . ($eventRecord ? ($rank == null ? '' : ' with') . " a record of " . $eventRecord['wins']
                        . '-' . $eventRecord['losses'] . '-' . $eventRecord['ties'] : '');

                $awards = $tba->getTeamEventAwards('frc' . $teamInfoRequestedFor,
                    $event->getYear() . $event->getEventCode());

                if ($awards && count($awards) > 0 || ($highestCompLevel !== 'f' && $highestCompLevel !== 'qm')) {
                    $eventText .= " and won the following awards:";

                    if ($highestCompLevel !== 'f' && $highestCompLevel !== 'qm') {
                        $eventText .= "\n• " . ($highestCompLevel === 'ef' ? 'Eighth-Finalist'
                                : ($highestCompLevel === 'qf' ? 'Quarterfinalist'
                                    : ($highestCompLevel === 'sf' ? 'Semifinalist' : '')));
                    }

                    foreach($awards as $award) {
                        $eventText .= "\n• " . $award->getName();
                        if ($award->getRecipientAwardee()) {
                            $eventText .= " • " . $award->getRecipientAwardee();
                        }
                    }
                } else if (strlen($eventText)) {
                    $eventText .= '.';
                }

                $attachment = new Attachment($event->getName() . ($event->isOfficial() ? '' : ' (Unofficial)'),
                    $eventText);

                $attachment->data['title_link'] = "https://www.thebluealliance.com/event/" . $event->getKey();
                $attachment->data['footer'] = $event->getLocation() . " on " . $event->getStartDate() . " to "
                    . $event->getEndDate();

                $attachments[] = $attachment;
            }
        }

        ProcessMessage::sendReply($teamId, $channelCache, 'Team ' . $team->getTeamNumber() . ', ' . $team->getNickname()
            . ' out of ' . $team->getLocation()
            . ($district == null ? '' : ' in the ' . $district->getName() . ' region') . ".\n"
            . ($team->getWebsite() == null ? '' : '<' . $team->getWebsite() . '> • ')
            . 'Founded in ' . $team->getRookieYear()
            . ($record == null ? '' : "\n" .$record['officialWins'] . '-' . $record['officialLosses']
                . '-' . $record['officialTies'] . ' in ' . $record['officialCompetitions']
                . " official events" . ($record['unofficialCompetitions'] == 0 ? '' : ' • ' . $record['wins']
                    . '-' . $record['losses'] . '-' . $record['ties']
                    . " in " . $record['competitions'] . ' overall events.')
                . ' • ' . '<https://thebluealliance.com/team/' . $team->getTeamNumber() . '|View on TBA>'), $replyTo,
            $attachments);
    }

    /**
     * Send the user competition status information on a team.
     *
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param int $teamStatusRequestedFor FRC team number
     * @param string|null $replyTo User to @ mention in the reply, or null to not mention user
     */
    public static function processStatusRequest($teamId, $channelCache, $teamStatusRequestedFor, $replyTo = null) {
        // First, check to see if the team is currently playing in any events, and report rank and last/next match
        // If not, check for any upcoming events, and report on their next event start date.
        // If not playing in any events, in addition to any upcoming events, report last event rank and W-L-T,
        //  and overall W-L-T across all (and report number of official/unofficial) events

        $tba = new TBAClient(TBASLACKBOT_TBA_APP_ID);
        $status = $tba->getTBAStatus();

        $team = $tba->getTeam('frc' . $teamStatusRequestedFor);

        if ($team == null) {
            ProcessMessage::sendUnknownTeam($teamId, $channelCache);
            return;
        }

        // First, load team/frcXXXX/YYYY/events, parse the start_date and end_date of each event to see if the event
        // is active.

        $activeEvent = $team->getTeamActiveEvent($status->getCurrentSeason());
        $nextEvent = $team->getTeamNextEvent($status->getCurrentSeason());
        $lastEvent = $team->getTeamLastEvent($status->getCurrentSeason());

        // Next load event/EVENTID/rankings to search for the team and their rank. Other data varies year to year.

        // If the event is active, load event/EVENTID/matches and check for null score_breakdown to denote an upcoming
        // match

        if ($activeEvent != null) {
            $activeEventRankings = $activeEvent->getEventRankings();
            $ranking = $activeEventRankings->getRankingForTeam($team->getTeamNumber());
            $activeEventRecord = $activeEvent->getEventRecordForTeam($team->getTeamNumber());

            $activeEventMatches = $activeEvent->getEventMatches();
            $lastMatch = $activeEventMatches->getLastMatchForTeam($team->getTeamNumber());
            $nextMatch = $activeEventMatches->getNextMatchForTeam($team->getTeamNumber());

            $output = "FRC Team " . $team->getTeamNumber() . " (" . $team->getNickname() . ") is currently at "
                . ($activeEvent->getShortName() == null ? $activeEvent->getName() : $activeEvent->getShortName())
                . " " . ($activeEvent->isOfficial() ? '' : '(Unofficial) ')
                . "and is ranked " . $ranking->getRank() . "/" . $activeEventRankings->getNumberOfRankedTeams()
                . " with a record of ". $activeEventRecord['wins'] . "-" . $activeEventRecord['losses'] . "-"
                . $activeEventRecord['ties'] . "."
                . ($lastMatch != null ? "\nLast Match: " . $lastMatch->getKey() : '')
                . ($nextMatch != null ? "\nNext Match: " . $nextMatch->getKey() : '');
            ProcessMessage::sendReply($teamId, $channelCache, $output, $replyTo);
            return;
        }

        // Upcoming events are already loaded in team/frcXXXX/YYYY/events

        if ($lastEvent != null) {
            $lastEventRankings = $lastEvent->getEventRankings();
            $rankingAtLastEvent = $lastEventRankings->getRankingForTeam($team->getTeamNumber());
            $lastEventRecord = $lastEvent->getEventRecordForTeam($team->getTeamNumber());
        }

        /** @noinspection PhpUndefinedVariableInspection */
        $output = "FRC Team " . $team->getTeamNumber() . " (" . $team->getNickname() . ") is currently not at "
            . "an active event. "
            . ($nextEvent == null ? 'I have no upcoming events for this team. '
                : "They are scheduled to attend "
                . ($nextEvent->getShortName() == null ? $nextEvent->getName() : $nextEvent->getShortName())
                . ($nextEvent->isOfficial() ? '' : ' (Unofficial)')
                . " starting on " . $nextEvent->getStartDate() . ". ")
            . ($lastEvent == null ? 'I have no completed events for this team. '
                : "They last participated in "
                . ($lastEvent->getShortName() == null ? $lastEvent->getName() : $lastEvent->getShortName())
                . ($lastEvent->isOfficial() ? '' : ' (Unofficial)')
                . ($rankingAtLastEvent == null ? '' : " and ranked " . $rankingAtLastEvent->getRank()
                    . "/" . $lastEventRankings->getNumberOfRankedTeams() . " with a record of "
                    . $lastEventRecord['wins'] . "-" . $lastEventRecord['losses'] . "-"
                    . $lastEventRecord['ties']) . ".");

        ProcessMessage::sendReply($teamId, $channelCache, $output, $replyTo);
    }

}