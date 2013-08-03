<?php

namespace wooshell\ghnotifier\EventDispatcher\Listeners\Drivers;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Symfony\Component\EventDispatcher\Event;
use wooshell\storeNotifier\EventDispatcher\Events\SendEvent;
use wooshell\storeNotifier\EventDispatcher\Listeners\NotifyListenerInterface;

class SwiftMailerListener implements NotifyListenerInterface
{

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    public function __construct($smtp, $username, $password)
    {
        if (false === function_exists('proc_open')) {
            throw new \Exception(sprintf('{%s} Sorry, that won\'t work', get_class($this)));
        }

        $transport = Swift_SmtpTransport::newInstance($smtp)
            ->setUsername($username)
            ->setPassword($password)
        ;

        $this->mailer = Swift_Mailer::newInstance($transport);
    }

    /**
     * @param Event $event
     */
    public function send(Event $event)
    {
        $body = '—' . PHP_EOL . 'This email has been sent automatically.';

        /** @var SendEvent $event */
        if (null !== $event->getBody()) {
            $body =
            'Check out this github repository update:' .
            PHP_EOL . PHP_EOL .
            $event->getBody() .
            PHP_EOL . PHP_EOL .
            $body;
        }

        $message = Swift_Message::newInstance($event->getSummary())
            ->setFrom(array('no-reply@notify.com' => 'wooshell notify'))
            ->setTo(array('php-notify@yopmail.com' => 'A name'))
            ->setBody($body);

        $this->mailer->send($message);
    }
}
