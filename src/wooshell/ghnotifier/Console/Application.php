<?php
namespace wooshell\ghnotifier\Console;

use Exception;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Yaml\Yaml;
use wooshell\ghnotifier\EventDispatcher\Listeners\Drivers\GnuNotifyListener;
use wooshell\ghnotifier\EventDispatcher\Listeners\Drivers\SwiftMailerListener;
use wooshell\storeNotifier\EventDispatcher\Events\SendEvent;
use wooshell\storeNotifier\StoreNotifier;

class Application extends BaseApplication
{
    private $store;

    /**
     * @param string $name
     * @param string $version
     */
    public function __construct($name = 'notify', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        $this->addCommands(
            array(
                new Command\Send(),
            )
        );
    }

    /**
     * @return \wooshell\storeNotifier\StoreEventDispatcher
     */
    public function addEvents()
    {
        $listener = new SwiftMailerListener(
            $this->getConfiguration('swift_mailer.smtp'),
            $this->getConfiguration('swift_mailer.email_from'),
            $this->getConfiguration('swift_mailer.email_from_password')
        );
        $this
            ->getStoreNotifier()
            ->getDispatcher()
            ->addListener(SendEvent::EVENT_SEND, array($listener , 'send'));
        $this
            ->getStoreNotifier()
            ->getDispatcher()
            ->addListener(SendEvent::EVENT_ERROR, array($listener , 'send'));

        $listener = new GnuNotifyListener(
            $this->getConfiguration('gnu_notify.bin')
        );
        $this
            ->getStoreNotifier()
            ->getDispatcher()
            ->addListener(SendEvent::EVENT_SEND, array($listener , 'send'));
        $this
            ->getStoreNotifier()
            ->getDispatcher()
            ->addListener(SendEvent::EVENT_ERROR, array($listener , 'send'));
    }

    /**
     * @return StoreNotifier
     * @throws \Exception
     */
    public function getStoreNotifier()
    {
        if (null === $this->store) {
            $storeDir = $this->getConfiguration('store_notifier.tracks_dir');
            if (false === is_dir($storeDir)) {
                throw new Exception(sprintf('dir "%s" not found', $storeDir));
            }
            $this->store = new StoreNotifier($storeDir);
        }

        return $this->store;
    }

    /**
     * @return string
     */
    private function getConfigurationPath()
    {
        return __DIR__ . '/../Resources/config/notify.yml';
    }

    /**
     * @param $key
     * @return mixed
     * @throws Exception
     */
    public function getConfiguration($key)
    {
        $configuration = Yaml::parse($this->getConfigurationPath());
        $items = explode('.', $key);

        foreach ($items as $item) {
            if (false === isset($configuration[$item])) {
                throw new Exception(sprintf('No key "%s" found in "%s"', $key, $this->getConfigurationPath()));
            }
            $configuration = $configuration[$item];
        }

        return $configuration;
    }
}
