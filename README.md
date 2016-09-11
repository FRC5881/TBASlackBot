# TBA Slack Bot #

These are the instructions to download and run your own, private, TBA Slack Bot. If you are just looking to add
the _tbabot_ hosted and provided by team 5881, check our [GitHub Pages Site](https://frc5881.github.io/TBASlackBot/)
for assistance.

## Hosting Requirements ##

The following software is required:

* PHP 5.6 - Web Server and CLI
* MySQL 5.7 or later
* System V Message Queues [For this PHP function](https://secure.php.net/manual/en/function.msg-get-queue.php)
* Composer
* SSL-Enabled Website (For Slack API)
* Method to keep the `daemon.php` script running on your host

## License ##

The source code for _tbabot_ is licensed under the AGPL 3.0 license. Under the terms of this license if you host
_tbabot_ you may be obligated to provide the source code to any additions or modifications.

It is our intention to iterate and improve _tbabot_ for the entire _FIRST&reg;_ Robotics Competition community, and
custom private and secret versions do not align with that goal. 

## Basic Setup ##

Clone the GitHub repo into a work directory, and run composer to download the dependencies.

Once the dependencies are loaded, if your web server path is `/var/www/html/tbabot/`, copy the contents of
`/src/TBASlackBot/` to `/var/www/html/tbabot/`. Copy the `vendor` directory created by Composer to the same directory.

Protect your web server from being able to execute the `daemon.php` script. On Apache you can use an `.htaccess` file.

Edit the `index.php` file to provide setup information and instructions to Slack Team Admins.

## MySQL Setup ##

Configure a MySQL database and user for the TBA Slack Bot. Create the database as outlined in the `docs/design.md`
file, using the provided DDL.

## Slack Setup ##

You must setup a Slack App for your bot. How to create an app is outside the scope of this document, excepting
the settings required to operate the bot.

#### Slack App OAuth Settings ####

In your Slack App, your `Redirect URI` must be set to the URL that serves `slack/oauth/callback.php`.

#### Slack Event Subscriptions ####

Set the `Request URL` to the URL that serves `slack/hooks/event.php` and enable the following bot events:

* message.channels
* message.groups
* message.im
* message.pim 

## settings.php ##

__The `settings.php` file must be modified prior to operating your bot__.

Refer to the descriptions in the file for what each setting is for.

## Running the daemon ##

The daemon is responsible for actually processing messages and events from Slack and TBA. The web server components 
only accept the messages. The bot won't work without the daemon running.

During initial setup it is encouraged to run `daemon.php` interactively on a shell/window. This is the only way to see
the verification string from TBA.

Once initial setup is complete, this process can be run as a background task with
it's output redirected to null. It is recommended that a wrapper or other tool be used to ensure the
daemon is restarted if it dies.

## TBA Setup ##

Once you have the daemon running to monitor teams and events you must setup the TBA Firehose / webhook. Set up a
TBA webhook pointed to the `tba/hooks/event.php` script. The TBA website will send a verification message
which will be output on the console of the daemon process. Once that is complete, subscribe to all events for the
current season via the TBA website.