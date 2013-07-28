<?php

namespace wooshell\ghnotifier\EventDispatcher\Listeners\Drivers;

use Symfony\Component\EventDispatcher\Event;
use wooshell\storeNotifier\EventDispatcher\Events\SendEvent;
use wooshell\storeNotifier\EventDispatcher\Listeners\ListenerInterface;

class GnuRemoteNotifyListener implements ListenerInterface
{
    private $binary;

    public function __construct($binaryPath, $users)
    {
        $this->binary = $binaryPath;
        $this->users = $users;
    }

    /**
     * @param Event $event
     */
    public function send(Event $event)
    {
        foreach ($this->users as $remotes) {
            $remoteUsername = current(array_keys($remotes));
            $remoteHost = current(array_values($remotes));
            exec(sprintf('ssh -X %s@%s \'DISPLAY=:0 %s "%s" "%s"\'', $remoteUsername, $remoteHost, $this->binary, $event->getSummary(), $event->getBody()));
        }
    }
    /** @var SendEvent $event */
}
