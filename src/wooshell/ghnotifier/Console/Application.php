<?php
namespace wooshell\ghnotifier\Console;

use Exception;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Yaml\Yaml;
use wooshell\ghnotifier\EventDispatcher\Listeners\Drivers\GnuListener;
use wooshell\ghnotifier\EventDispatcher\Listeners\Drivers\SwiftMailerListener;
use wooshell\storeNotifier\EventDispatcher\Events\SendEvent;
use wooshell\storeNotifier\StoreNotifier;
use wooshell\storeNotifier\EventDispatcher\Listeners\NotifyListenerInterface;

class Application extends BaseApplication
{
    private $store;

    private $eventRegistry;

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
     * @param $eventName
     * @param NotifyListenerInterface $listener
     */
    protected function addEventListener($eventName, NotifyListenerInterface $listener)
    {
        $this
            ->getStoreNotifier()
            ->getDispatcher()
            ->addListener($eventName, array($listener , 'send'));
        $this->eventRegistry[get_class($listener)] = array($eventName => $listener);
    }

    /**
     * @param $listenerClassname
     * @param  null       $eventName
     * @throws \Exception
     */
    public function unregisterEvent($listenerClassname, $eventName = null)
    {
        if (false === isset($this->eventRegistry[$listenerClassname])) {
            throw new \Exception(sprintf('Unknown event listener "%s"', $listenerClassname));
        }

        if (null !== $eventName) {
            if (false === isset($this->eventRegistry[$listenerClassname][$eventName])) {
                throw new \Exception(sprintf('Unknown event "%s"->"%s"', $listenerClassname, $eventName));
            }
            unset($this->eventRegistry[$listenerClassname][$eventName]);
        }
        unset($this->eventRegistry[$listenerClassname][$eventName]);
    }

    /**
     * @param $className
     */
    public function registerEventListener($className)
    {
        $fullClassName = sprintf('\wooshell\ghnotifier\EventDispatcher\Listeners\Drivers\%s', $className );

        if (false === is_subclass_of($fullClassName, '\\wooshell\\storeNotifier\\EventDispatcher\\Listeners\\NotifyListenerInterface')) {
            throw new \Exception(sprintf('Unkwown event listener "%s"', $className));
        }

        switch ($className) {
            case 'SwiftMailerListener':
                $listener = new SwiftMailerListener(
                    $this->getConfiguration('swift_mailer.smtp'),
                    $this->getConfiguration('swift_mailer.email_from'),
                    $this->getConfiguration('swift_mailer.email_from_password')
                );
                $this->addEventListener(SendEvent::EVENT_ERROR, $listener);
                break;
            case 'GnuListener':
                $listener = new GnuListener(
                    $this->getConfiguration('gnu_notify.bin')
                );
                $this->addEventListener(SendEvent::EVENT_SEND, $listener);
                $this->addEventListener(SendEvent::EVENT_ERROR, $listener);
                break;
            default:
                throw new Exception(sprintf('Unknown event listener "%s"', $className));
                break;
        }
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
