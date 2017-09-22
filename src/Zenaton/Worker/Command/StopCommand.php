<?php

namespace Zenaton\Worker\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class StopCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('stop')
            ->setDescription('Stop the listening of Zenaton executable for this app/environment/language')
            ->setHelp('This command allows you to stop the zenaton executable to listen the app/environment in your .env file')
            ->addOption(self::OPTION_LARAVEL, null, InputOption::VALUE_NONE, 'Use this option if using Laravel')
            ->addOption(self::OPTION_DOTENV, null, InputOption::VALUE_REQUIRED, 'Define location of your .env file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $envFile = null;

        // default value for --laravel option
        if ($input->getOption(self::OPTION_LARAVEL)) {
            if ($this->checkLaravel($input, $output) === false) {
                return;
            }
            $envFile = ($this->getLaravelDefault())[0];
        }

        // get and check --env option
        $envFile = $this->getEnvOption($input, $output, $envFile);
        if ($envFile === false) {
            return;
        }

        $this->loadEnvFile($envFile);

        if ($this->checkEnvAppParameters($output) === false) {
            return;
        }

        $feedback = $this->stop();

        return $output->writeln('<info>'.$feedback.'</info>');
    }

    public function stop()
    {
        $body = [
            self::MS_APP_ID => getenv(self::ENV_APP_ID),
            self::MS_API_TOKEN => getenv(self::ENV_API_TOKEN),
            self::MS_APP_ENV => getenv(self::ENV_APP_ENV),
            self::PROGRAMMING_LANGUAGE => self::PHP,
        ];

        return $this->microserver->stop($body);
    }
}
