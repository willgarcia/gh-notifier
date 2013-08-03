<?php
namespace wooshell\ghnotifier\Console\Command;

use Github\Client;
use Github\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Send extends Command
{
    /**
     * @param null $name
     */
    public function __construct($name = null)
    {
        parent::__construct('send');
        $this->addArgument('modes', InputArgument::REQUIRED);
        $this->addOption('lock-file', 'lf', InputOption::VALUE_REQUIRED);
    }

    /**
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null !== $input->getOption('lock-file')) {
            $this->getStoreNotifier()->lock($input->getOption('lock-file'));
        }
        $this->initApplication($input->getArgument('modes'));

        // github api
        $github = new Client();
        $github->authenticate(
            $this->getApplication()->getConfiguration('github.auth-token'),
            null,
            Client::AUTH_HTTP_TOKEN
        );

        // github repositories
        $repos = file($this->getApplication()->getConfiguration('github.repos'), FILE_IGNORE_NEW_LINES);
        sort($repos);

        // notifications
        foreach ($repos as $repo) {

            list($user, $reponame) = explode('/', $repo, 2);

            try {
                $tags = $github->api('git_data')->tags()->all($user, $reponame);
            } catch (RuntimeException $e) {
                $this->getStoreNotifier()->error(sprintf('No releases for project: %s', $repo));
                continue;
            }

            $repoKey = $user . '-' . $reponame;

            // local event dispatcher
            $this->getStoreNotifier()->store($repoKey);

            foreach ($tags as $tag) {
                $tagName = basename($tag['ref']);
                if (false === $this->getStoreNotifier()->exists($repoKey, $tagName)) {
                    $this->getStoreNotifier()->log($tagName);

                    $this->getStoreNotifier()->send(
                        sprintf('[%s] New release %s !', $repo, $tagName),
                        sprintf('<a href="https://github.com/%1$s/releases">https://github.com/%1$s</a>: %2$s', $repo, $tagName)
                    );
                }
            }
        }

        if (null !== $input->getOption('lock-file')) {
            $this->getStoreNotifier()->unlock($input->getOption('lock-file'));
        }

    }

    /**
     * @return mixed
     */
    public function getStoreNotifier()
    {
        return $this->getApplication()->getStoreNotifier();
    }

    /**
     * @param  array                                  $modes
     * @return \Symfony\Component\Console\Application
     * @throws \Exception
     */
    public function initApplication($modes)
    {
        if (null === $modes) {
            throw new \Exception('A notification mode must be defined');
        }
        $app = parent::getApplication();
        foreach (explode(',', $modes) as $mode) {
            $app->registerEventListener(ucfirst($mode . 'Listener'));
        }

        $this->setApplication($app);
    }
}
