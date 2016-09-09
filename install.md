---
layout: page
title: Installation
permalink: /install/
---

# The Unofficial Blue Alliance Slack Bot Setup #

These instructions provide _essential_ information for Slack Admins and authorized users to install our hosted
TBASlackBot for their Slack Team. If you are looking to build and run your own instance, please refer to the 
source code for requirements and installation instructions.

__Please read these instructions thoroughly before installing!__

Currently, the bot is in a __beta__ state, with most query functions working properly. That said,
iteration in development has been on features and functions, not extensive testing. In other words, you may find
bad output, or even ways to break the bot.

## How to Use the Bot - and some Warnings ##

After setting up the bot, the bot will message the user that set it up with some basic information. By default,
the bot does not join any channels (including #general) on your team. Team members can open a DM with the bot
and issue commands. Team members with appropriate permissions can __/invite__ the bot into other channels.

__WARNING:__ The Slack Event API (which the bot uses to receive messages) does not allow the bot, or you, to filter
what messages are received. The bot will receive a copy of __every message sent and received__ in the channels the
bot is invited to. Currently all messages the bot receives are shown on console temporarily to aid with debugging,
and are not permanently saved, or written to disk. Therefore nothing said in a channel the bot has been invited to 
is private. The message logging will continue to be reduced as development moves on, as we have no interest in your
channel chat.

__If this concerns you, simply don't invite the bot to any channels.__

### Information We Keep, and Why ###

| What we Keep                          | Why we Keep it                                                        |
| ------------------------------------- | --------------------------------------------------------------------- |
| Commands, parameters, and who sent it | Usage statistics, troubleshooting                                     |
| Any __feedback__ sent                 | Troubleshooting and improving the bot                                 |
| Slack Team OAuth Tokens               | To authenticate the bot to Slack                                      |
| Slack Team Name                       | For future use in bot replies                                         |
| Slack User Id and Short Name          | For use in troubleshooting, and for the bot to reply to users by name |
| Slack ChannelId, Name, and Type       | Id and Type are used to send replies, name is used in troubleshooting |

__We keep and store the least amount of information possible to troubleshoot and efficiently do the work the bot 
 was designed for.__

### Interacting with the Bot ###

Please refer to our [Documentation](/docs) for how to work with TBABot, or send __help__ to the bot at any time.

### About Slack Permissions ###
The bot requests a number of permissions when you set it up. Many are used today, some are added for
soon-to-be-added features, and some are for future-proofing. Here's a list of the permissions we request and why
we request them (as shown in the authorization screen):

| Permission                                                 | Reason                                                                                   |
| ---------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| Confirm your identity                                      | Added by Slack, not us                                                                   |
| Access information about your channels and direct messages | This is used to keep a list of channels the bot can access                               |
| Access information about your team                         | _Future:_ Contains only team name, Slack domain, and icon                                |
| Access your team's Do Not Disturb settings                 | _Future:_ Allows the bot to see if a user is in do not disturb mode, and act accordingly |
| Access your profile and your team's profile fields         | To reference the user by name in replies                                                 |
| Send messages as The Blue Alliance Slack Bot               | Self explanatory                                                                         |
| Add a bot user with the username @tbabot                   | Self explanatory                                                                         |
| Access your team's profile information                     | See the profile access description above                                                 |

## Updates and Improvements ##
The bot is currently being updated almost daily with improvements and updates. We strongly encourage users to
use the __help changelog__ command to keep on top of changes and new features.

## Bugs, Problems, Feature Requests ##
There is the __feedback__ command in the bot itself, or feel free to post an issue on GitHub.

### Gracious Professionalism&reg; ###

The TVHS Dragons offers this service to the _FIRST&reg;_ Robotics Competition community with the understanding that
users will honor the ideals of Gracious Professionalism in how the bot is used. This would include, but is not 
limited to, excessive or abusive usage (please don't ask the bot about _every_ team), or attempting to __follow__
every FRC team. TBABot is currently hosted by one of our mentors at their personal expense.

## Ready to add the bot? ##

<p align="center">Just click here:</p>
<div align="center">
    <a href="https://slack.com/oauth/authorize?scope=bot,channels:read,chat:write:bot,dnd:read,groups:read,im:read,mpim:read,team:read,users.profile:read,users:read&client_id=74043304640.74011404435">
        <img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png"
             srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x"/>
    </a>
</div>
