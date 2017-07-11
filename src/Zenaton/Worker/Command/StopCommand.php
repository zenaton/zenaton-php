<?php

namespace Zenaton\Worker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Dotenv\Dotenv;

use Zenaton\Worker\Configuration;

class StopCommand extends Command
{
    const DOTENV = '.env';

    protected function configure()
    {
        $this
            ->setName('stop')
            ->addArgument(self::DOTENV, InputArgument::REQUIRED, 'The path of your .env file')
            ->setDescription('Stop the listening of Zenaton executable for this app/environment/language')
            ->setHelp('This command allows you to stop the zenaton executable to listen the app/environment in your .env file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $envPath = $input->getArgument(self::DOTENV);

        if (! file_exists($envPath)) {
            return $output->writeln('Error! Unabled to find '.$envPath.' file.'.PHP_EOL);
        }

        $feedback = (new Configuration($envPath))->stopMicroserver();

        return $output->writeln($feedback);
    }
}
