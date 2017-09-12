<?php

namespace Zenaton\Worker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Zenaton\Worker\Configuration;

class StartCommand extends Command
{
    const LARAVEL = 'laravel';
    const SYMFONY = 'symfony';
    const DOTENV = 'env';
    const DOTENV_DEFAULT = '/.env';
    const AUTOLOAD = 'autoload';
    const AUTOLOAD_DEFAULT = '/autoload.php';

    protected $dir;

    protected function configure()
    {
        $this
            ->setName('start')
            ->setDescription('Start Zenaton worker')
            ->addOption(self::LARAVEL, null, InputOption::VALUE_NONE, 'Use this option if using Laravel')
            // ->addOption(self::SYMFONY, null, InputOption::VALUE_NONE, 'Use this option if using Symfony')
            ->addOption(self::DOTENV, null, InputOption::VALUE_OPTIONAL, 'Define location of your .env file', getcwd().self::DOTENV_DEFAULT)
            ->addOption(self::AUTOLOAD, null, InputOption::VALUE_OPTIONAL, 'Define location of your autoload file', getcwd().self::AUTOLOAD_DEFAULT)
            ->setHelp('Start Zenaton worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // --laravel option
        if ($input->getOption(self::LARAVEL)) {
            $file = getcwd().'/bootstrap/autoload.php';
            if (!file_exists($file)) {
                return $output->writeln('<error>Error in Laravel configuration (Unable to find '.$file.' file).</error>'.PHP_EOL);
            }
            $file = getcwd().'/bootstrap/app.php';
            if (!file_exists($file)) {
                return $output->writeln('<error>Error in Laravel configuration (Unable to find '.$file.' file).</error>'.PHP_EOL);
            }
            $dotenv = getcwd().'/.env';
            if (!file_exists($file)) {
                return $output->writeln('<error>Error in Laravel configuration (Unable to find '.$dotenv.' file).</error>'.PHP_EOL);
            }
            $autoload = getcwd().'/vendor/zenaton/zenaton-php/bootstrap/laravel.php';
        }

        // --symphony option
        // if ($input->getOption(self::SYMFONY)) {
        //     $file = getcwd().'/bootstrap/autoload.php';
        //     if (! file_exists($file)) {
        //         return $output->writeln('<error>Error in Symfony configuration (Unable to find '.$file.' file).</error>'.PHP_EOL);
        //     }

        //     $dotenv = getcwd().'/.env';
        //     $autoload = getcwd()."/vendor/zenaton/zenaton-php/bootstrap/symfony.php";
        // }

        // --env option
        if ($input->getOption(self::DOTENV)) {
            $dotenv = $input->getOption(self::DOTENV);
        }

        if (!$dotenv || (!file_exists($dotenv) && $dotenv === self::DOTENV_DEFAULT)) {
            return $output->writeln('<info>Please locate your env file with'
                .' --'.self::LARAVEL
                // . ', --'.self::SYMFONY
                .', or --'.self::DOTENV.' option.</info>'
                .PHP_EOL
            );
        }

        if (!file_exists($dotenv)) {
            return $output->writeln('<error>Unabled to find '.$dotenv.' file.</error>'.PHP_EOL);
        }

        // --autoload option
        if ($input->getOption(self::AUTOLOAD)) {
            $autoload = $input->getOption(self::AUTOLOAD);
        }

        if (!$autoload || (!file_exists($autoload) && $autoload === self::AUTOLOAD_DEFAULT)) {
            return $output->writeln('<info>Please locate your autoload file with'
                .' --'.self::LARAVEL
                // . ', --'.self::SYMFONY
                .', or --'.self::AUTOLOAD.' option.</info>'
                .PHP_EOL
            );
        }

        if (!file_exists($autoload)) {
            return $output->writeln('<error>Unabled to find '.$autoload.' file.</error>'.PHP_EOL);
        }

        // start worker
        $feedback = (new Configuration($dotenv, $autoload))->startMicroserver();

        return $output->writeln($feedback);
    }
}
