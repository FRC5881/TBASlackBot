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
use TBASlackbot\slack\Channels;
use TBASlackbot\slack\ProcessMessage;
use TBASlackbot\utils\DB;

/**
 * Feedback and response commands
 * @package TBASlackbot\slack\commands
 */
class Feedback
{
    /**
     * Process and store feedback from a user.
     *
     * @param string $teamId Slack TeamId
     * @param array $channelCache Channel Cache as stored by the DB
     * @param string $messageFrom Slack User that sent the feedback
     * @param string[] $messageWordArray The array of words sent as feedback
     * @param string|null $replyTo User to @ mention in the reply, or null to not mention user
     */
    public static function processFeedbackRequest($teamId, $channelCache, $messageFrom, $messageWordArray, $replyTo) {
        $db = new DB();

        $db->logFeedback($teamId, $channelCache['channelId'], $messageFrom, implode(' ', $messageWordArray));

        ProcessMessage::sendReply($teamId, $channelCache, "I'll forward your feedback right away. Thank you!",
            $replyTo);
    }

    /**
     * Checks for any pending feedback replies for the user and sends them.
     *
     * @param string $teamId Slack TeamId
     * @param string $userId Slack UserId
     */
    public static function checkForFeedbackReply($teamId, $userId) {
        $db = new DB();

        $replies = $db->getQueuedFeedbackReplies($teamId, $userId);

        if (!$replies) {
            return;
        }

        $channelCache = Channels::getUserDmChannelCache($teamId, $userId);

        if ($channelCache) {
            foreach ($replies as $reply) {
                $message = "Pardon the interruption, I have a reply to some *_feedback_* you left: ";

                $attachments[] = new Attachment("Feedback Reply - " . $reply['replyEnteredAt'], $reply['replyText']);
                $attachments[] = new Attachment("Original Feedback - " . $reply['messageTime'], $reply['feedback']);

                ProcessMessage::sendReply($teamId, $channelCache, $message, null, $attachments);
                $db->setFeedbackReplySent($reply['id']);
            }
        } else {
            error_log("\nUnable to get ChannelCache for queued replies for UserId $userId on $teamId\n");
        }
    }
}