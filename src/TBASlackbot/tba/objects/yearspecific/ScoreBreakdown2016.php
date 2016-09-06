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


namespace TBASlackbot\tba\objects\yearspecific;

/**
 * Score Breakdown as used in Match object for 2016 Season
 * @author Brian Rozmierski
 */
class ScoreBreakdown2016
{
    /**
     * @var \stdClass
     */
    public $data;

    /**
     * ScoreBreakdown2016 constructor.
     *
     * @param \stdClass $data returned from event match API
     */
    public function __construct(\stdClass $data)
    {
        $this->data = $data;
    }

    /**
     * Gets the number of points adjusted in the score for the requested alliance.
     * 
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Points adjusted for the requested alliance
     */
    public function getAdjustPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->adjustPoints : $this->data->red->adjustPoints;
    }

    /**
     * Gets the number of points scored with boulders in auto for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Autonomous boulder points for the requested alliance
     */
    public function getAutoBoulderPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->autoBoulderPoints : $this->data->red->autoBoulderPoints;
    }

    /**
     * Gets the number of points scored in the high goal during autonomous for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Points scored in high goal during autonomous for the requested alliance
     */
    public function getAutoBouldersHigh($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->autoBouldersHigh : $this->data->red->autoBouldersHigh;
    }

    /**
     * Gets the number of points score in the low goal during autonomous for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Points scored in the low goal during autonomous for the requested alliance
     */
    public function getAutoBouldersLow($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->autoBouldersLow : $this->data->red->autoBouldersLow;
    }

    /**
     * Gets the number of points scored for crossing during autonomous for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Crossing points scored during autonomous for the requested alliance
     */
    public function getAutoCrossingPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->autoCrossingPoints : $this->data->red->autoCrossingPoints;
    }

    /**
     * Gets the total number of autonomous points for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Total autonomous points for the requested alliance
     */
    public function getAutoPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->autoPoints : $this->data->red->autoPoints;
    }

    /**
     * Gets the number of points scored for reaching during autonomous for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Points scored for reaching during autonomous for the requested alliance
     */
    public function getAutoReachPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->autoReachPoints : $this->data->red->autoReachPoints;
    }

    /**
     * Gets the number of breech points for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Breech points for the requested alliance
     */
    public function getBreachPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->breachPoints : $this->data->red->breachPoints;
    }

    /**
     * Gets the number of capture points for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Capture points for the requested alliance
     */
    public function getCapturePoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->capturePoints : $this->data->red->capturePoints;
    }

    /**
     * Gets the number, not points, of fouls for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Number of fouls for the requested alliance
     */
    public function getFoulCount($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->foulCount : $this->data->red->foulCount;
    }

    /**
     * Gets the number of foul points for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Foul points for the requested alliance
     */
    public function getFoulPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->foulPoints : $this->data->red->foulPoints;
    }

    /**
     * Gets the number of crossings for the defense in position 1 for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Number of crossings of position 1 for the requested alliance
     */
    public function getPosition1crossings($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position1crossings : $this->data->red->position1crossings;
    }

    /**
     * Gets the defence in position 2 for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return string|null Defence in position 2 for the requested alliance
     */
    public function getPosition2($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position2 : $this->data->red->position2;
    }

    /**
     * Gets the number of crossings for the defense in position 2 for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Number of crossings of position 2 for the requested alliance
     */
    public function getPosition2crossings($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position2crossings : $this->data->red->position2crossings;
    }

    /**
     * Gets the defence in position 3 for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return string|null Defence in position 3 for the requested alliance
     */
    public function getPosition3($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position3 : $this->data->red->position3;
    }

    /**
     * Gets the number of crossings for the defense in position 3 for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Number of crossings of position 3 for the requested alliance
     */
    public function getPosition3crossings($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position3crossings : $this->data->red->position3crossings;
    }

    /**
     * Defence in position 4 for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return string|null Defence in position 4 for the requested alliance
     */
    public function getPosition4($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position4 : $this->data->red->position4;
    }

    /**
     * Gets the number of crossings for the defense in position 4 for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Number of crossings of position 4 for the requested alliance
     */
    public function getPosition4crossings($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position4crossings : $this->data->red->position4crossings;
    }

    /**
     * Defence in position 5 for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return string|null Defence in position 5 for the requested alliance
     */
    public function getPosition5($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position5 : $this->data->red->position5;
    }

    /**
     * Gets the number of crossings for the defense in position 5 for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Number of crossings of position 5 for the requested alliance
     */
    public function getPosition5crossings($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->position5crossings : $this->data->red->position5crossings;
    }

    /**
     * Returns if the 1st robot reached, crossed, or neither during auto.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return string|null 'None' for neither, 'Reached' if the robot reached the defences, or 'Crossed' if crossed
     */
    public function getRobot1Auto($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->robot1Auto : $this->data->red->robot1Auto;
    }

    /**
     * Returns if the 2nd robot reached, crossed, or neither during auto.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return string|null 'None' for neither, 'Reached' if the robot reached the defences, or 'Crossed' if crossed
     */
    public function getRobot2Auto($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->robot2Auto : $this->data->red->robot2Auto;
    }

    /**
     * Returns if the 3rd robot reached, crossed, or neither during auto.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return string|null 'None' for neither, 'Reached' if the robot reached the defences, or 'Crossed' if crossed
     */
    public function getRobot3Auto($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->robot3Auto : $this->data->red->robot3Auto;
    }

    /**
     * Gets the number of tech fouls given for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Number of tech fouls for the requested alliance
     */
    public function getTechFoulCount($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->techFoulCount : $this->data->red->techFoulCount;
    }

    /**
     * Gets the number of points scored with boulders during teleop for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Points for boulders during teleop for the requested alliance
     */
    public function getTeleopBoulderPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopBoulderPoints
            : $this->data->red->teleopBoulderPoints;
    }

    /**
     * Gets the number of points scored with boulders in the high goal during teleop for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Points for high goal boulders during teleop for the requested alliance
     */
    public function getTeleopBouldersHigh($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopBouldersHigh : $this->data->red->teleopBouldersHigh;
    }

    /**
     * Gets the number of points scored with boulders in the low goal during teleop for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Points for low goal boulders during teleop for the requested alliance
     */
    public function getTeleopBouldersLow($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopBouldersLow : $this->data->red->teleopBouldersLow;
    }

    /**
     * Gets the number of challenge points during teleop for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Challenge points during teleop for the requested alliance
     */
    public function getTeleopChallengePoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopChallengePoints
            : $this->data->red->teleopChallengePoints;
    }

    /**
     * Gets the number of crossing points during teleop for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Crossing points during teleop for the requested alliance
     */
    public function getTeleopCrossingPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopCrossingPoints
            : $this->data->red->teleopCrossingPoints;
    }

    /**
     * Notes if the defences were breached in teleop for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return bool|null true if defences breached during teleop for the requested alliance
     */
    public function isTeleopDefensesBreached($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopDefensesBreached
            : $this->data->red->teleopDefensesBreached;
    }

    /**
     * Gets the total number of points during teleop for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Total points during teleop for the requested alliance
     */
    public function getTeleopPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopPoints : $this->data->red->teleopPoints;
    }

    /**
     * Gets the number of points for scaling during teleop for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Scale points during teleop for the requested alliance
     */
    public function getTeleopScalePoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopScalePoints : $this->data->red->teleopScalePoints;
    }

    /**
     * Notes if the tower was captured during teleop for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return bool|null true if the tower was captures during teleop for the requested alliance
     */
    public function isTeleopTowerCaptured($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->teleopTowerCaptured : $this->data->red->teleopTowerCaptured;
    }

    /**
     * Gets the total points for the match for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Total match points for the requested alliance
     */
    public function getTotalPoints($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->totalPoints : $this->data->red->totalPoints;
    }

    /**
     * Gets the tower ending strength for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null Tower ending strength for the requested alliance.
     */
    public function getTowerEndStrength($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->towerEndStrength : $this->data->red->towerEndStrength;
    }

    /**
     * Denotes if there was no robot on face A, or if the robot challenged or scaled for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return string|null 'Unknown' for no robot present, 'Challenged' or 'Scaled' for the requested alliance
     */
    public function getTowerFaceA($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->towerFaceA : $this->data->red->towerFaceA;
    }

    /**
     * Denotes if there was no robot on face B, or if the robot challenged or scaled for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null 'Unknown' for no robot present, 'Challenged' or 'Scaled' for the requested alliance
     */
    public function getTowerFaceB($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->towerFaceB : $this->data->red->towerFaceB;
    }

    /**
     * Denotes if there was no robot on face C, or if the robot challenged or scaled for the requested alliance.
     *
     * @param string $allianceColor 'red' or 'blue' only, any other value will give red result
     * @return int|null 'Unknown' for no robot present, 'Challenged' or 'Scaled' for the requested alliance
     */
    public function getTowerFaceC($allianceColor) {
        return $allianceColor === 'blue' ? $this->data->blue->towerFaceC : $this->data->red->towerFaceC;
    }
}