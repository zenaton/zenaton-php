<?php

namespace Zenaton\Worker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Zenaton\Worker\Configuration;

class StartCommand extends Command
{
    const AUTOLOAD = 'autoload';
    const DOTENV = '.env';
    protected function configure()
    {
        $this
            ->setName('start')
            ->addArgument(self::DOTENV, InputArgument::REQUIRED, 'The location of your .env file')
            ->addArgument(self::AUTOLOAD, InputArgument::REQUIRED, 'The location of your autoload file')
            ->setDescription('Start and set the Zenaton executable')
            ->setHelp('This command allows you to start and set a Zenaton executable');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $autoload = $input->getArgument(self::AUTOLOAD);
        $dotenv = $input->getArgument(self::DOTENV);

        if (! file_exists($dotenv)) {

            return $output->writeln('Error! Unabled to find '.$dotenv.' file.'.PHP_EOL);


        }
        // just in case autoload file has been not entered correctly
        if ( ! file_exists($autoload)) {

            return $output->writeln('Error! Unabled to find '.$autoload.' file.'.PHP_EOL);
        }

        $feedback = (new Configuration($dotenv, $autoload))->startMicroserver();

        return $output->writeln($feedback);
    }
}
