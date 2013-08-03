gh-notifier
===========

Tracks github projects and let you know when new releases are available.

Notifications modes:
* mail (mode: "swiftMailer")
* desktop notification (mode: "gnu")

Prerequisites
-------------

* notify-send for gnu mode

Installation
------------

    $ composer install

Configuration
-------------

    $ src/wooshell/ghnotifier/Resources/config/notify.yml

Usage
-----

Use this command to send notifications:

    $ bin/gh-notifier send gnu,mail
    $ bin/gh-notifier send gnu
    $ ..

In the background, an history file is stored for each github project and at each run, the script detects new releases via Github API.

Notification modes values are comma separated

Running automatically
---------------------

Lock each run of gh-notifier if you want to cronify the script. The lock file is removed at the end of a run.

    $ bin/gh-notifier send gnu --lock-file=/tmp/gh-notifier.lock

You can build a PHAR if you need an easy way to deploy (needs box.phar installation from https://github.com/kherge/Box ):

    $ build box
    $ chmod +x gh-notifier.phar
    $ gh-notifier.phar send [...]

