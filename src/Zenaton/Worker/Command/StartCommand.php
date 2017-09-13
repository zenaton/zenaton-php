<?php

namespace Zenaton\Worker\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Zenaton\Worker\Configuration;
use Zenaton\Common\Exceptions\EnvironmentNotSetException;

class StartCommand extends Command
{
    const LARAVEL = 'laravel';
    const SYMFONY = 'symfony';
    const DOTENV = 'env';
    const AUTOLOAD = 'autoload';

    protected $dir;

    protected function configure()
    {
        $this
            ->setName('start')
            ->setDescription('Start Zenaton worker')
            ->addOption(self::LARAVEL, null, InputOption::VALUE_NONE, 'Use this option if using Laravel')
            // ->addOption(self::SYMFONY, null, InputOption::VALUE_NONE, 'Use this option if using Symfony')
            ->addOption(self::DOTENV, null, InputOption::VALUE_OPTIONAL, 'Define location of your .env file', $this->getDefaultEnv())
            ->addOption(self::AUTOLOAD, null, InputOption::VALUE_OPTIONAL, 'Define location of your autoload file', $this->getDefaultAutoload())
            ->setHelp('Start Zenaton worker');
    }

    protected function getDefaultEnv()
    {
        return getcwd().'/.env';
    }

    protected function getDefaultAutoload()
    {
        return getcwd().'/autoload.php';
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // --laravel option
        if ($input->getOption(self::LARAVEL)) {
            $file = getcwd().'/bootstrap/autoload.php';
            if (!file_exists($file)) {
                return $output->writeln('<error>Error in Laravel configuration (Unable to find '.$file.' file).</error>');
            }
            $file = getcwd().'/bootstrap/app.php';
            if (!file_exists($file)) {
                return $output->writeln('<error>Error in Laravel configuration (Unable to find '.$file.' file).</error>');
            }
            $dotenv = getcwd().'/.env';
            if (!file_exists($file)) {
                return $output->writeln('<error>Error in Laravel configuration (Unable to find '.$dotenv.' file).</error>');
            }
            $autoload = getcwd().'/vendor/zenaton/zenaton-php/bootstrap/laravel.php';
        }

        // --symphony option
        // if ($input->getOption(self::SYMFONY)) {
        //     $file = getcwd().'/bootstrap/autoload.php';
        //     if (! file_exists($file)) {
        //         return $output->writeln('<error>Error in Symfony configuration (Unable to find '.$file.' file).</error>');
        //     }

        //     $dotenv = getcwd().'/.env';
        //     $autoload = getcwd()."/vendor/zenaton/zenaton-php/bootstrap/symfony.php";
        // }

        // --env option
        if (!isset($dotenv)) {
            $dotenv = $input->getOption(self::DOTENV);
            if (!$this->isAbsolutePath($dotenv)) {
                $dotenv = getcwd().'/'.$dotenv;
            }
        }

        if (!file_exists($dotenv)) {
            if ($dotenv === $this->getDefaultEnv()) {
                return $output->writeln('<info>Please locate your env file with'
                    .' --'.self::LARAVEL
                    // . ', --'.self::SYMFONY
                    .', or --'.self::DOTENV.' option.</info>'
                );
            }

            return $output->writeln('<error>Unabled to find '.$dotenv.' file.</error>');
        }

        // --autoload option
        if (!isset($autoload)) {
            $autoload = $input->getOption(self::AUTOLOAD);
            if (!$this->isAbsolutePath($autoload)) {
                $autoload = getcwd().'/'.$autoload;
            }
        }

        if (!file_exists($autoload)) {
            if ($autoload === $this->getDefaultAutoload()) {
                return $output->writeln('<info>Please locate your autoload file with'
                    .' --'.self::LARAVEL
                    // . ', --'.self::SYMFONY
                    .', or --'.self::AUTOLOAD.' option.</info>'
                );
            }

            return $output->writeln('<error>Unabled to find '.$autoload.' file.</error>');
        }

        // start worker
        try {
            $feedback = (new Configuration($dotenv, $autoload))->startMicroserver();

            return $output->writeln('<info>'.$feedback.'</info>');
        } catch (EnvironmentNotSetException $e) {
            return $output->writeln('<error>'.$e->getMessage().'</error>');
        }
    }

     /**
      * Returns whether the file path is an absolute path.
      *
      * @param string $file A file path
      *
      * @return bool
      */
     private function isAbsolutePath($file)
     {
         if ($file[0] === '/' || $file[0] === '\\'
             || (strlen($file) > 3 && ctype_alpha($file[0])
                 && $file[1] === ':'
                 && ($file[2] === '\\' || $file[2] === '/')
             )
             || null !== parse_url($file, PHP_URL_SCHEME)
         ) {
             return true;
         }

         return false;
     }
}
