# The Blue Alliance Slack Bot

This is the design reference / documentation for the TBA Slack bot, or `tbabot`.

It is coded for PHP 5.6, later versions may have library incompatibilities.

## General Architecture

The code is split into several sections. The `slack` section deals with all Slack authorization (in the `oauth`
subsection), as well as hooks/callbacks (in the `hooks` subsection). The `tba` section handles all The Blue Alliance
API calls and callbacks, while the `daemon` section is a PHP background process to manage the inbound events from
both TBA and Slack and respond as needed. 

In general terms, the app slurps the TBA fire hose, posting those inbound TBA events to a message queue for the daemon to
process. The daemon processes those events to determine if any of them need to be posted to slack, and the work to do so
is done in the daemon thread. Conversely, the Slack events are sent to another message queue for processing in the
daemon thread.

### Slack

The general application process from the Slack side is this: A Team Admin (or User w/ permission) adds the Slack App to
their team. As a result an OAuth challenge is started against the `oauth` subsection that, once completed, results in a
long-term `Access-Token`. The `oauth` subsection then stores the team and token information for use by the other
components.

After some welcome messages are sent to the user, the Slack `Event API` will call back to the `hooks` subsection with
messages from the users. Those are posted to the Slack message queue by the `hooks` subsection, and a quick HTTP/200 is
returned to Slack.

The `daemon` section listens for Slack message queue events, and processes the incoming messages and slash commands.
It is responsible for any look ups or other processing to be done, including sending requests through the TBA APIs.

### TBA

From the TBA side, the `hooks` subsection receives all callbacks from the TBA fire hose, and places those in a message
queue. The `daemon` section then is responsible for taking those messages, determining if they are relevant to any bot
subscriptions, and sending messages back to Slack as needed.

The `TBA` section also provides for the API calls for information lookup, and caching, as needed.

### Database

Stored in the database are several tables.

#### Slack OAuth Tokens

| Column             | Purpose                                          |
| ------------------ | ------------------------------------------------ |
| teamId             | The Slack teamId                                 |
| accessToken        | The Slack application Access-Token               |
| scope              | The Slack OAuth scope granted                    |
| teamName           | The human-readable team name                     |
| botUserId          | The Slack UserId for the bot                     |
| botAccessToken     | The Slack access token for the bot               |
| addedByUserId      | The Slack UserId that added the application      |

MySQL DDL:
```
CREATE TABLE slackOAuth
(
    teamId VARCHAR(32) PRIMARY KEY NOT NULL,
    accessToken VARCHAR(255) NOT NULL,
    scope VARCHAR(255),
    teamName VARCHAR(255),
    botUserId VARCHAR(32) NOT NULL,
    botAccessToken VARCHAR(255) NOT NULL,
    addedByUserId VARCHAR(32)
);
CREATE INDEX botUserId__index ON slackOAuth (botUserId);
```

#### Slack Channel Cache

| Column             | Purpose                                                     |
| ------------------ | ----------------------------------------------------------- |
| teamId             | The Slack teamId                                            |
| channelId          | The Slack channelId                                         |
| lastAccess         | The date/time the channel cache was last updated            |
| channelName        | The Slack channel name                                      |
| channelType        | The Slack Channel Type, `channel`, `im`, `mpim`, or `group` |
| joined             | True if bot is invited to the channel                       |

MySQL DDL:
```
CREATE TABLE slackChannelCache
(
    teamId VARCHAR(32),
    channelId VARCHAR(32) PRIMARY KEY NOT NULL,
    lastAccess DATETIME NOT NULL,
    channelName VARCHAR(255),
    channelType ENUM('channel', 'im', 'mpim', 'group') NOT NULL,
    joined TINYINT(1)
);
CREATE INDEX teamId__index ON slackChannelCache (teamId);
```

#### Slack User Cache

| Column             | Purpose                                          |
| ------------------ | ------------------------------------------------ |
| teamId             | The Slack teamId                                 |
| userId             | The Slack userId                                 |
| lastAccess         | The date/time the user cache was last updated    |
| userName           | The Slack user name                              |

MySQL DDL:
```
CREATE TABLE slackUserCache
(
    teamId VARCHAR(32) NOT NULL,
    userId VARCHAR(32) NOT NULL,
    lastAccess DATETIME NOT NULL,
    userName VARCHAR(32),
    CONSTRAINT `PRIMARY` PRIMARY KEY (userId, teamId)
);
```

#### Slack Team Subscriptions

| Column             | Purpose                                          |
| ------------------ | ------------------------------------------------ |
| teamId             | The Slack teamId                                 |
| channelId          | The Slack ChannelId                              |
| frcTeam            | FRC team number subscribed to                    |
| subscriptionType   | One of `full`, or `summary`                      |
| subscribedByUserId | The Slack userId that requested the subscription |

MySQL DDL:
```
CREATE TABLE slackTeamSubscriptions
(
    teamId VARCHAR(32) NOT NULL,
    channelId VARCHAR(32) NOT NULL,
    frcTeam INT(11) NOT NULL,
    subscriptionType ENUM('all', 'result', 'summary') NOT NULL,
    subscribedByUserId VARCHAR(32),
    CONSTRAINT `PRIMARY` PRIMARY KEY (frcTeam, channelId, teamId)
);
CREATE INDEX frcTeam__index ON slackTeamSubscriptions (frcTeam);
```

#### TBA Cache
The TBA Cache is stored as a monolithic table keyed on the API call that was made. Cache is implemented two fold in
the code. First, The `Last-Modified` header is honored, and stored with each reply. On subsequent calls that value
is sent as `If-Modified-Since` allowing the API to return a 304 code that the cached copy is still valid. The second
layer stores the date/time the last request to the API was made, including requests resulting in 304 codes. Thus the
code can implement a minimum amount of time the cached entry we checked is considered valid, no matter when TBA
last updated it, before attempting the API call again for an update.

| Column             | Purpose                                                    |
| ------------------ | ---------------------------------------------------------- |
| apiCall            | The API URL endpoint that was called                       |
| lastModified       | The X-Last-Modified header from the API result             |
| apiJsonString      | The Raw JSON string returned by the API                    |
| lastRetrieval      | The date/time the DB cache entry was last updated          |
| expires            | The date/time the cached entry expires, as provided by TBA |

MySQL DDL:
```
CREATE TABLE tbaApiCache
(
    apiCall VARCHAR(255) PRIMARY KEY NOT NULL,
    lastModified DATETIME NOT NULL,
    apiJsonString LONGTEXT,
    lastRetrieval DATETIME NOT NULL,
    expires DATETIME NOT NULL
);
```

#### Feedback Log

The feedback log records any __feedback__ sent by any of the bot users.

| Column      | Purpose                                          |
| ----------- | ------------------------------------------------ |
| id          | Auto-incrementing identifier                     |
| messageTime | Date/Time the feedback was received              |
| teamId      | Slack TeamId for the user that sent the feedback |
| channelId   | Slack ChannelId the feedback was received on     |
| userId      | Slack UserId that sent the feedback              |
| feedback    | Feedback sent by the user                        |

MySQL DDL:
```
CREATE TABLE botFeedbackLog
(
    id BIGINT(20) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    messageTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    teamId VARCHAR(32) NOT NULL,
    channelId VARCHAR(32) NOT NULL,
    userId VARCHAR(32) NOT NULL,
    feedback LONGTEXT NOT NULL
);
```

#### Message Log

The message log logs all commands received by the bot.

| Column        | Purpose                                         |
| ------------- | ----------------------------------------------- |
| id            | Auto-incrementing identifier                    |
| messageTime   | Date/Time the command was received              |
| teamId        | Slack TeamId for the user that sent the command |
| channelId     | Slack ChannelId the command was received on     |
| sendingUserId | Slack UserId that sent the command              |
| commandString | Command sent by the user                        |

MySQL DDL:
```
CREATE TABLE botMessageLog
(
    id BIGINT(20) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    messageTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    teamId VARCHAR(32) NOT NULL,
    channelId VARCHAR(32) NOT NULL,
    sendingUserId VARCHAR(32) NOT NULL,
    commandString VARCHAR(64)
);
```

## License
This document, and the accompanying software is licensed under the terms of the AGPL v3 or later.
