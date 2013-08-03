<?php

namespace wooshell\ghnotifier\EventDispatcher\Listeners\Drivers;

use Symfony\Component\EventDispatcher\Event;
use wooshell\storeNotifier\EventDispatcher\Events\SendEvent;
use wooshell\storeNotifier\EventDispatcher\Listeners\NotifyListenerInterface;

class GnuListener implements NotifyListenerInterface
{

    private $binary;

    public function __construct($binaryPath)
    {
        $this->binary = $binaryPath;
    }

    /**
     * @param Event $event
     */
    public function send(Event $event)
    {
        /** @var SendEvent $event */
        exec(sprintf('%s "%s" "%s"', $this->binary, $event->getSummary(), $event->getBody()));
    }
}
