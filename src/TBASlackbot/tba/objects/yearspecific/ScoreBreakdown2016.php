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
 * Score Breakdown as used in Match object for 2016 Season
 * @author Brian Rozmierski
 */

namespace TBASlackbot\tba\objects\yearspecific;


class ScoreBreakdown2016
{
    /**
     * @var array
     */
    public $data;

    /**
     * ScoreBreakdown2016 constructor.
     * @param $data array returned from event match API
     */
    public function __construct($data)
    {
        $this->data = $data;
    }


    public function getAdjustPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->adjustPoints : $this->data->red->adjustPoints;
    }

    public function getAutoBoulderPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->autoBoulderPoints : $this->data->red->autoBoulderPoints;
    }

    public function getAutoBouldersHigh($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->autoBouldersHigh : $this->data->red->autoBouldersHigh;
    }

    public function getAutoBouldersLow($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->autoBouldersLow : $this->data->red->autoBouldersLow;
    }

    public function getAutoCrossingPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->autoCrossingPoints : $this->data->red->autoCrossingPoints;
    }

    public function getAutoPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->autoPoints : $this->data->red->autoPoints;
    }

    public function getAutoReachPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->autoReachPoints : $this->data->red->autoReachPoints;
    }

    public function getBreachPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->breachPoints : $this->data->red->breachPoints;
    }

    public function getCapturePoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->capturePoints : $this->data->red->capturePoints;
    }

    public function getFoulCount($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->foulCount : $this->data->red->foulCount;
    }

    public function getFoulPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->foulPoints : $this->data->red->foulPoints;
    }

    public function getPosition1crossings($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position1crossings : $this->data->red->position1crossings;
    }

    public function getPosition2($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position2 : $this->data->red->position2;
    }

    public function getPosition2crossings($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position2crossings : $this->data->red->position2crossings;
    }

    public function getPosition3($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position3 : $this->data->red->position3;
    }

    public function getPosition3crossings($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position3crossings : $this->data->red->position3crossings;
    }

    public function getPosition4($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position4 : $this->data->red->position4;
    }

    public function getPosition4crossings($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position4crossings : $this->data->red->position4crossings;
    }

    public function getPosition5($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position5 : $this->data->red->position5;
    }

    public function getPosition5crossings($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position5crossings : $this->data->red->position5crossings;
    }

    public function getRobot1Auto($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->robot1Auto : $this->data->red->robot1Auto;
    }

    public function getRobot2Auto($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->robot2Auto : $this->data->red->robot2Auto;
    }

    public function getRobot3Auto($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->robot3Auto : $this->data->red->robot3Auto;
    }

    public function getTechFoulCount($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->techFoulCount : $this->data->red->techFoulCount;
    }

    public function getTeleopBoulderPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopBoulderPoints : $this->data->red->teleopBoulderPoints;
    }

    public function getTeleopBouldersHigh($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopBouldersHigh : $this->data->red->teleopBouldersHigh;
    }

    public function getTeleopBouldersLow($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopBouldersLow : $this->data->red->teleopBouldersLow;
    }

    public function getTeleopChallengePoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopChallengePoints : $this->data->red->teleopChallengePoints;
    }

    public function getTeleopCrossingPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopCrossingPoints : $this->data->red->teleopCrossingPoints;
    }

    public function getTeleopDefensesBreached($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopDefensesBreached : $this->data->red->teleopDefensesBreached;
    }

    public function getTeleopPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopPoints : $this->data->red->teleopPoints;
    }

    public function getTeleopScalePoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopScalePoints : $this->data->red->teleopScalePoints;
    }

    public function getTeleopTowerCaptured($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopTowerCaptured : $this->data->red->teleopTowerCaptured;
    }

    public function getTotalPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->totalPoints : $this->data->red->totalPoints;
    }

    public function getTowerEndStrength($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->towerEndStrength : $this->data->red->towerEndStrength;
    }

    public function getTowerFaceA($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->towerFaceA : $this->data->red->towerFaceA;
    }

    public function getTowerFaceB($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->towerFaceB : $this->data->red->towerFaceB;
    }

    public function getTowerFaceC($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->towerFaceC : $this->data->red->towerFaceC;
    }
}