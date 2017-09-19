<?php
// FRC5881 Unofficial TBA Slack Bot
// Copyright (c) 2017.
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


namespace TBASlackbot\tba\objects;

/**
 * TBA Award Object.
 * @author Brian Rozmierski
 */
class Award
{
    /**
     * @var \stdClass
     */
    public $data;

    /**
     * Award constructor.
     *
     * @param \stdClass $data returned from award API
     */
    public function __construct(\stdClass $data)
    {
        $this->data = $data;
    }

    /**
     * Gets the name of the award.
     *
     * @return string award name
     */
    public function getName() {
        return $this->data->name;
    }

    /**
     * Gets the TBA code for the award type
     *
     * @return int TBA award type code
     */
    public function getAwardType() {
        //TODO Process this to class/enum
        return $this->data->award_type;
    }

    /**
     * Gets the event key for this award.
     * @return string event key
     */
    public function getEventKey() {
        return $this->data->event_key;
    }

    /**
     * Gets the team number of the winning team.
     *
     * @return int|null recipient team number or null if an individual non-team award
     */
    public function getRecipientTeam() { // TODO Not used, but deans list is broken here, eg http://www.thebluealliance.com/api/v3/team/frc250/event/2017nytr/awards
        return $this->data->recipient_list[0]->team_number;
    }

    /**
     * Gets the name of the award recipient.
     *
     * @return string|null Award recipient name, or null if not an individual award
     */
    public function getRecipientAwardee() {
        return $this->data->recipient_list[0]->awardee;
    }

    /**
     * Gets the year this award was given.
     *
     * @return int Year of the award
     */
    public function getYear() {
        return $this->data->year;
    }
}