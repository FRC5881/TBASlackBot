---
layout: page
title: Documentation
permalink: /docs/
---

# Introduction to _TBABot_ #

The Unofficial TBA Slack Bot, or _@tbabot_ as you likely see it on your Slack Team, is a _conversational_ bot that
 can gather and parse information on _FIRST&reg;_ Robotics Competition teams and events. In addition to simple request 
 and response communication, it can monitor or __follow__ teams during live competitions, and update you as they 
 progress.

## Two Conversation Modes ##

_@tbabot_ has two basic modes of operation, depending on what kind of channel you are conversing with it in.

If you are in a Direct Message or DM channel, it's just you and _@tbabot_, and you just just send commands directly.
 _@tbabot_ will respond to you in the DM channel, and will not @mention your name. (Which may help with unwanted
 Slack notifications.)

If you are in any other kind of channel, where other users may be present, _@tbabot_ will patiently sit and listen for
 it's name to be mentioned before attempting to respond to a command.

For example, in  your _#general_ channel sending `Hello tbabot info 5881 please.` will have no effect. You must use the
 Slack _@username_ syntax to get the bot's attention. Instead try `Hello @tbabot info 5881 please` and _@tbabot_ will
 realize you want to run the __info__ command for team 5881. It will respond and @mention your username.
 
As you can see above, when in a multi-user channel, you can trigger and command _@tbabot_ from within the middle of 
 a message. _@tbabot_ will expect the next word after it's name to be a valid command.
 
_Important:_ some commands have optional parameters, like __follow__, that you can omit. Unless your command to 
 _@tbabot_ is at the end of your
 message, you _must_ include optional parameters.

For example, `Hello @tbabot follow 5881 please` will result in an error. Because follow requires 1, but allows 2
 parameters, and `please` is not a valid 2nd parameter, the command will fail. You can use 
 `Hello @tbabot follow 5881 all please` successfully, as well as `Hello @tbabot follow 5881` as the command ends the
 message.
 
# _@tbabot_ Commands #

In this document all commands you can give _@tbabot_ are listed in __bold__ type, and example messages and 
 replies `are noted like this`.

## Help, and Changes ##

_@tbabot_ has a built-in __help__ system. By sending the __help__ command _@tbabot_ will reply with a short list of
 common commands, as well as instructions on how to access additional help for other commands.
 
| Help Commands      | Result |
| ------------------ | ------------------------------------------------------------------------------------------------------------- |
| __help__           | Information on __info__, __detail__, __status__, and __feedback__ commands, links to other __help__ commands. |
| __help channels__  | Help on how _@tbabot_ listens and replies in the various types of Slack channels.                             |
| __help subscribe__ | Information on how to __follow__ or __unfollow__ a team, and list teams the channel is __following__.         |
| __help changelog__ | Documentation on recent changes and updates to _@tbabot_ including new features.                              |

<br/>

### Feedback, and Problem Reporting ###

If you have a non-cataclysmic problem with _@tbabot_ please use the __feedback__ command to let us know. _@tbabot_
 will send us anything you type after __follow__ along with your user and Slack team information so we can track down
 the problem.
 
Feel free to use __feedback__ for comments and suggestions as well. However, since the developers do not have access
 to your Slack team, we can't reply to messages left via __feedback__. If you want or need a reply, please use our
 [GitHub issue page](https://github.com/FRC5881/TBASlackBot/issues).

## Team Information Commands ##

There are 3 team information commands available from _@tbabot_, and one team competition information command.

### info ###

You can ask the bot for basic team information by sending the __info__ command followed by the team number you are
 looking for. The bot will reply with the team name, a link to the website, location, district, when they were
 founded, how they did this season, and a link to the team on The Blue Alliance.
 
A shorter version of this command is available by just sending the team number, without a command, to _@tbabot_ who
 will reply with the team name and location.
 
### detail ###

When you need more detail on a team, and their progress this season, use the __detail__ command followed by the
 team number. You'll get the same information as __info__ will give you, but in addition it will list each event
 the team participated at, how they ranked, what their record at the event was, any elimination progress, and awards.
 
### status ###

When you're looking for information on the current competition the team is at, use __status__ and the team number. If 
 the team is currently competing, you'll get information on their current rank, last, and next matches, if any.

If they are not currently at a competition, you'll get information on their last and next competitions, if any.

It's a great way to check on a team as they compete.

## Team Subscription Commands ##

One of the powerful aspects of _@tbabot_ is it's ability to allow you to subscribe to team updates during competitions.
 Subscriptions are managed on a per-channel basis. That means your teams _#stradegy_ channel can __follow__ potential
 alliance selections, while your _#outreach_ channel can __follow__ that rookie team you are mentoring. All the while
 you can __follow__ your favorite personal teams in your Direct Message channel with _@tbabot_.
 
### Types of Notifications ###

_@tbabot_ will send several types of notices throughout a competition, depending on the request to subscribe to the
 team. Those notifications include:
 
| Notification     | Description                                                                           |
| ---------------- | ------------------------------------------------------------------------------------- |
| Schedule Updated | Will send a notice that the schedule has been updated, and a link to the event on TBA |
| Upcoming Match   | Sent when a team has a match soon to start                                            |
| Score Posted     | Sent at the conclusion of a match with the match score                                |
| Event Summary    | Sent at the end of an event, after awards post, and summarizes the event for the team |

 
### Subscription Management ###

To start following a team you send the __follow__ command and the team number of the team you wish to follow. By 
 default _@tbabot_ will keep you up to date with any news about a team during competition. The __follow__ command has
 an optional 2nd parameter, after the team number, that allows you to reduce the amount of updates _@tbabot_ will send.
 To reduce the notifications to match score updates, and end of competition summaries, add _result_ after the team
 number. Or, you can add _summary_ instead to just get a summary of the results at the end of competition.
 
To stop following a team send __unfollow__ and the team number, and the bot will stop sending any updates.

To list the teams currently being followed in the channel, send __following__ and _@tbabot_ will list each team being
followed in that channel.