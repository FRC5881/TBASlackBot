---
layout: post
title:  "Offseason Update"
date:   2016-09-26 15:00:00 -0400
categories: jekyll update
---

TBASlackbot has two main features, the ability to query team information on request, and the ability to
follow a team and receive updates during a competition. While we're fairly happy with the current status
of the former, the latter has been relatively untested.

The issue is, simply, that it's hard to test following a team in competitions, if the FMS at the competition
isn't online and posting results. We had a flicker of hope a few weeks ago, but the real test came this
weekend with [Chezy Champs](https://chezychamps.com).

Chezy Champs, aside from using their custom FMS, is statistically a popular event to be followed by 
TBASlackBot. More than half the teams using the bot were following at least one team at the event.
Unfortunately, as the first match came and went, no updates were flowing.

As most teams who were following teams at Chezy Champs know, they eventually got it fixed, late
Saturday, as they bulk-posted updates for the first 58 qualification matches. The teams know this because
@tbabot went to town sending all the updates to everyone who was following those teams. So many, in fact,
it appears as though we got throttled!

For Sunday, things were generally running well, from the data side of things. Matches were being reported
promptly, and we fixed a few bugs as the morning went on. (No, you aren't following 254, 254, and 254.)

We did get some feedback we wanted to address. First, __yes__ you can reduce the amount of messages
@tbabot will send you when you follow a team. By default, you'll get any and all updates. The _follow_
command takes the team number and an optional argument that reduces the output to just competition summaries,
or to match results and summary. Ask @tbabot for _help subscribe_ for more information.

The second was with the upcoming match notifications. The timing of those notifications is determined
by TBA based on the scheduled match time. In several cases upcoming alerts went out for matches 1 or 2
ahead of what was really coming up next. This was most obvious after the back-to-back timeouts used
in eliminations when Finals 3 was called as upcoming. If you want to suppress these, when you _follow_
the team, select _result_ as the option.

Penultimately, Chezy Champs was the first offseason to post awards to TBA, allowing us to test our end-of-competition
summaries. Teams may notice they received two or three of these. This is because the awards were posted
in 3 stages through TBA, and generated 3 notices to @tbabot. We do not send the summary unless all matches
at an event are complete, so this would not affect awards posted mid-competition. We will be watching
future events, and especially the first official events for _FIRST_ STEAMWORKS, to see if this multiple-posting
remains to be an issue.

And finally, we did receive a few pieces of feedback this weekend that we really wanted to reply to and
say, "it already does that!" So, we'll be working in a way for @tbabot to forward a reply to feedback sent
in via the bot. When we send the reply we won't @mention you, but we will send it to the same channel the
feedback was sent from.