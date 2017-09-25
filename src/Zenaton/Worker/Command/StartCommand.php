<?php

namespace Zenaton\Worker\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class StartCommand extends Command
{
    const MS_WORKFLOWS_NAME_ONLY = 'workflows_name_only';
    const MS_TASKS_NAME_ONLY = 'tasks_name_only';

    const MS_WORKFLOWS_NAME_EXCEPT = 'workflows_name_except';
    const MS_TASKS_NAME_EXCEPT = 'tasks_name_except';

    const MS_CONCURRENT_MAX = 'concurrent_max';
    const WORKER_SCRIPT = 'worker_script';
    const AUTOLOAD_PATH = 'autoload_path';

    const ZENATON_API_URL = 'https://zenaton.com/api';

    protected function configure()
    {
        $this
            ->setName('start')
            ->setDescription('Start Zenaton worker')
            ->setHelp('Start Zenaton worker')
            ->addOption(self::OPTION_LARAVEL, null, InputOption::VALUE_NONE, 'Use this option if using Laravel')
            ->addOption(self::OPTION_DOTENV, null, InputOption::VALUE_REQUIRED, 'Define location of your .env file')
            ->addOption(self::OPTION_AUTOLOAD, null, InputOption::VALUE_REQUIRED, 'Define location of your autoload file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $envFile = null;
        $bootFile = null;

        // default value for --laravel option
        if ($input->getOption(self::OPTION_LARAVEL)) {
            if ($this->checkLaravel($input, $output) === false) {
                return;
            }
            list($envFile, $bootFile) = $this->getLaravelDefault();
        }

        // get and check --env option
        $envFile = $this->getEnvOption($input, $output, $envFile);
        if ($envFile === false) {
            return;
        }

        // get and check --autoload option
        $bootFile = $this->getBootOption($input, $output, $bootFile);
        if ($bootFile === false) {
            return;
        }

        // load env file and check app_id, app_env and app_token parameters
        $this->loadEnvFile($envFile);
        if ($this->checkEnvAppParameters($output) === false) {
            return;
        }

        // load boot file and check ZENATON_HANDLE_EXCEPT and ZENATON_HANDLE_ONLY parameters
        $this->loadBootFile($bootFile);
        if ($this->checkEnvHandleParameters($output) === false) {
            return;
        }

        if ($this->checkConcurrentMaxParameter($output) === false) {
            return;
        }

        // start worker
        $feedback = $this->start($bootFile);

        return isset($feedback->error) ? $output->writeln('<error>'.$feedback->error.'</error>') : $output->writeln('<info>'.$feedback->msg.'</info>');
    }

    public function start($bootFile)
    {
        $body = [
            self::MS_APP_ID => getenv(self::ENV_APP_ID),
            self::MS_API_TOKEN => getenv(self::ENV_API_TOKEN),
            self::MS_APP_ENV => getenv(self::ENV_APP_ENV),
            self::MS_API_URL => getenv(self::ENV_API_URL) ? : self::ZENATON_API_URL,
            self::MS_CONCURRENT_MAX => $this->getConcurrentMaxParameter(),
            self::MS_WORKFLOWS_NAME_ONLY => $this->getClassNamesByTypeFromEnv(self::ENV_HANDLE_ONLY, WorkflowInterface::class),
            self::MS_TASKS_NAME_ONLY => $this->getClassNamesByTypeFromEnv(self::ENV_HANDLE_ONLY, TaskInterface::class),
            self::MS_WORKFLOWS_NAME_EXCEPT => $this->getClassNamesByTypeFromEnv(self::ENV_HANDLE_EXCEPT, WorkflowInterface::class),
            self::MS_TASKS_NAME_EXCEPT => $this->getClassNamesByTypeFromEnv(self::ENV_HANDLE_EXCEPT, TaskInterface::class),
            self::WORKER_SCRIPT => getcwd().'/vendor/zenaton/zenaton-php/scripts/slave.php',
            self::AUTOLOAD_PATH => $bootFile,
            self::PROGRAMMING_LANGUAGE => self::PHP,
        ];

        return $this->microserver->sendEnv($body);
    }
}
