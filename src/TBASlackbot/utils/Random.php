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


namespace TBASlackbot\utils;

/**
 * Provides several randomizing utility functions.
 * @package TBASlackbot\utils
 */
class Random
{
    /**
     * Given an array of reply option strings, rare reply option string, and a percentage, return a random string.
     *
     * @param string[] $replyOptions Array of strings of standard, common reply options
     * @param string[] $rareReplyOptions Array of string of rate reply options
     * @param int $percentRare A non-decimal percentage (eg 5% is 5) representing the likelihood of returning a
     * rare reply option
     * @param string[] $reallyRareOptions Array of strings, or empty array, of really rare reply options
     * @param int $percentReallyRare A non-decimal percentage for returning a really rare reply option
     * @return string
     */
    public static function replyRandomizer($replyOptions, $rareReplyOptions, $percentRare = 5,
                                           $reallyRareOptions = array(), $percentReallyRare = 1) {
        $randomValue = rand(0, 100);

        if (count($reallyRareOptions) > 0 && $randomValue <= $percentReallyRare) {
            return $reallyRareOptions[rand(0, count($reallyRareOptions) -1)];
        }

        if (count($rareReplyOptions) > 0 && $randomValue <= $percentRare) {
            return $rareReplyOptions[rand(0, count($rareReplyOptions) -1)];
        }

        return $replyOptions[rand(0, count($replyOptions) -1)];
    }
}