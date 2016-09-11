<?php

        include_once('settings.php');

?><!DOCTYPE html>
<!-- FRC5881 Unofficial TBA Slack Bot -->
<!-- Copyright (c) 2016. -->
<!-- -->
<!-- This program is free software: you can redistribute it and/or modify it under the terms of the GNU -->
<!-- Affero General Public License as published by the Free Software Foundation, either version 3 of -->
<!-- the License, or any later version. -->
<!-- -->
<!-- This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; -->
<!-- without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. -->
<!-- See the GNU Affero General Public License for more details. -->
<!-- -->
<!-- You should have received a copy of the GNU Affero General Public License along with this -->
<!-- program.  If not, see <http://www.gnu.org/licenses/>. -->

<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Slack Bot Setup</title>
</head>
<body>

<!-- Add information or setup instructions here -->

<h2>Ready to add the bot?</h2>
<p align="center">Just click here:</p>
<div align="center">
    <a href="https://slack.com/oauth/authorize?scope=bot,channels:read,chat:write:bot,dnd:read,groups:read,im:read,mpim:read,team:read,users.profile:read,users:read&client_id=<?php echo TBASLACKBOT_SLACK_CLIENT_ID; ?>">
        <img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png"
             srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x"/>
    </a>
</div>
</body>
</html>