# HKAPI

I had a dream. I wanted to integrate my Harman Kardon AVR 370 in my SmartHome environment.
Many of the AV-Receivers by Harman Kardon supply network functionality for AirPlay and remote
control using the HK Remote for Android or iOS. But you can't script an App like that.

So I decided to reverse-engineer the requests sent by the App and write a PHP library having that
functionality. And here we are. Feel free to browse the example.php file for a real-life demonstration.

## Requirements

Well.. almost nothing is required to get the API running. Of course, you need an compatible AV-Receiver.
I don't know exactly which model is supported, so if you're running anything other than the AVR 370, let me know!

On the client side you just have to be able to connect to sockets using PHP. Minimum required version is 5.6.
Of course, to use the autoloader functionality, you have to use composer.

## Basic usage

Generate the autoloader using ``composer install``.

Everything else should be well-documented in the code itself, so just take a look at the example.php or use an IDE.

You'll start with the API object:

```php
$hk = new \HKAPI\API('your_ip', 10025, ['Main Zone', 'Zone 2']);
```

After that you can use any available action on every predefined zone. For example:

```php
$zone = $hk->zone('Main Zone');
$zone->on();
$zone->selectSource('Radio');
```

Before quitting now, please read the following section since it will save you a lot of time investigating.

## What you should know

Since Harman Kardon did not want this API to be public, accessing it is a bit hairy. There is no documentation
and most of the actions won't return any state at all. So you'll never know if something succeeded or failed.

Also, we don't know how long each request takes to send. Keep that in mind when sending multiple requests at once.

Another great "feature" of the AVRs is (at least of mine), that the server handling all requests running on your
AVR will shut down until next power on (using the button or the remote). This means you cannot control the AVR
at all after that period of time. A workaround for this is (I think), to send ``heartAlive()`` requests every
15 minutes or so. I'm still investigating this.

## Available Actions

I've gone through all actions available on the official App so this list should be complete. Nevertheless it's
possible that there are some hidden actions not supplied by the App. If you know or find some new, just let me
know or open a Pull request. I'd be very happy about it.

All the actions can be triggered using the Zone object, either using run() or the magic method. You can also
run your own requests by using API::generateRequest(), API::sendRequest() and API::readResponse().

* ``void on()`` - Turn AVR on.
* ``void off()`` - Turn AVR off.
* ``void play()`` - Play current track.
* ``void pause()`` - Pause current track.
* ``void forward()`` - Go forward in current track.
* ``void reverse()`` - Go reverse in current track.
* ``void next()`` - Select next track.
* ``void previous()`` - Select previous track.
* ``void volumeDown()`` - Adjust volume by +1. (looking for a better way to do this...)
* ``void volumeUp()`` - Adjust volume by -1. (same here...)
* ``void muteToggle()`` - (Un-)Toggle the volume.
* ``void up()`` - Go up.
* ``void right()`` - Go right.
* ``void down()`` - Go down.
* ``void left()`` - Go left.
* ``void ok()`` - Confirm selection.
* ``void back()`` - Go back.
* ``void home()`` - Go home.
* ``void info()`` - Show info-menu.
* ``void options()`` - Show options-menu.
* ``string heartAlive()`` - Send heart alive request and get back some garbage (yay!).
* ``void selectSource($source)`` - Select the source. A list of (my) sources is shown below.

### Sources

The sources are exactly as shown on the on-screen menu, so in my case:

* Cable Sat
* Disc
* DVR
* Radio
* TV
* USB
* Game
* Media Server
* Home Network
* AUX
* Source A
* Source B
* Source C
* Source D

## Disclaimer

This project is not affiliated with, or endorsed by Harman Kardon in any way. Harman Kardon is a registered
trademark. If you think this project is harming you or your company, please contact me.