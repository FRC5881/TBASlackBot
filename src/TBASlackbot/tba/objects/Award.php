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
 * TBA Award Object
 * @author Brian Rozmierski
 */

namespace TBASlackbot\tba\objects;


class Award
{
    /**
     * @var array
     */
    public $data;

    /**
     * Award constructor.
     * @param $data array returned from award API
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getName() {
        return $this->data->name;
    }

    public function getAwardType() {
        return $this->data->award_type;
    }

    public function getEventKey() {
        return $this->data->event_key;
    }

    public function getRecipientTeam() {
        return $this->data->recipiennt_list->team_number;
    }

    public function getRecipientAwardee() {
        return $this->data->recipiennt_list->awardee;
    }

    public function getYear() {
        return $this->data->year;
    }
}