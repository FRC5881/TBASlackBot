---
layout: page
title: FAQ
permalink: /faq/
---

Some Frequently Asked Questions about TBABot:

## How do I lookup a team's results for a different season? ##

Short answer: You can't. 

Long answer: Because the TBA API does not provide information on winning vs losing alliances, and the match breakdown
varies from year to year, if provided at all, we have to code W-L-T detection on a season-by-season basis. In addition,
if you are looking to research a team across multiple seasons, we don't believe a chat bot is the best method. As such,
you'll find links to TBA pages for many of the detailed responses from _tbabot_ and we encourage their use.
   
We may reevaluate looking up the "last season" results once TBA officially flips to the 2017 season.

## _tbabot_ __status__ for my team doesn't list our upcoming off season event. ##

The data we get is only as good as what is available at [The Blue Alliance](https://www.thebluealliance.com). In
many cases, if you look at the team list for upcoming off season events, there are none listed. If there is an
official listing of registered teams, contact the TBA devs to see about getting them added.

## I'm following a team at an off season event, but not getting any updates. ##

This usually happens at some off season events, to get real time updates, the FMS at the event must be online,
connected the the FIRST FMS, and the TBA website must be getting updates. Notifications to _tbabot_ are
generated from TBA by this data. If the event FMS is offline these events will not trigger.

## I'm following a team at an off season event and I got a whole ton of notifications all at once. ##

This can happen when an event FMS is offline during the event, but is put online later and all the data gets
sent to FIRST in one batch. TBA will then receive all the updates at once and send notifications, which
trigger the messages to you.

## I have a great idea or feature, how do I get it added? ##

If it's a simple idea, or suggestion, you're welcome to use the __feedback__ command and let us know about it. If it's
more detailed, or you'd like to discuss it, please use our
[GitHub Issue Tracker](https://github.com/FRC5881/TBASlackBot/issues). If you'd like to implement it, feel free to
fork the code and send us a Pull Request.