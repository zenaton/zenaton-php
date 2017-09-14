<?php

namespace Zenaton\Worker\Command;

use Dotenv\Dotenv;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Zenaton\Common\Traits\IsImplementationOfTrait;
use Zenaton\Common\Interfaces\TaskInterface;
use Zenaton\Common\Interfaces\WorkflowInterface;
use Zenaton\Worker\MicroServer;

class Command extends SymfonyCommand
{
    const OPTION_LARAVEL = 'laravel';
    const OPTION_SYMFONY = 'symfony';
    const OPTION_DOTENV = 'env';
    const OPTION_AUTOLOAD = 'autoload';

    const ENV_API_TOKEN = 'ZENATON_API_TOKEN';
    const ENV_APP_ID = 'ZENATON_APP_ID';
    const ENV_APP_ENV = 'ZENATON_APP_ENV';
    const ENV_HANDLE_ONLY = 'ZENATON_HANDLE_ONLY';
    const ENV_HANDLE_EXCEPT = 'ZENATON_HANDLE_EXCEPT';
    const ENV_CONCURRENT_MAX = 'ZENATON_CONCURRENT_MAX';

    const MS_APP_ID = 'app_id';
    const MS_API_TOKEN = 'api_token';
    const MS_APP_ENV = 'app_env';
    const PROGRAMMING_LANGUAGE = 'programming_language';
    const PHP = 'PHP';

    use IsImplementationOfTrait;

    protected $microserver;

    public function __construct()
    {
        $this->microserver = MicroServer::getInstance();

        parent::__construct();
    }

    protected function checkLaravel($input, $output)
    {
        $file = './bootstrap/autoload.php';
        if (!file_exists($file)) {
            $output->writeln('<error>Does not look like a Laravel project (Unable to find '.$file.' file).</error>');

            return false;
        }
        $file = './bootstrap/app.php';
        if (!file_exists($file)) {
            $output->writeln('<error>Does not look like a Laravel project (Unable to find '.$file.' file).</error>');

            return false;
        }

        return true;
    }

    protected function getLaravelDefault()
    {
        return ['.env', 'vendor/zenaton/zenaton-php/bootstrap/laravel.php'];
    }

    protected function getEnvOption($input, $output, $envFile = null)
    {
        $usingDefault = false;

        if (is_null($envFile)) {
            $envFile = $input->getOption(self::OPTION_DOTENV);
            if (!$envFile) {
                $envFile = '.env';
                $usingDefault = true;
            }
        }

        // enforce absolute path
        if (!$this->isAbsolutePath($envFile)) {
            $envFile = getcwd().'/'.$envFile;
        }

        // check env file exist
        if (!file_exists($envFile)) {
            if ($usingDefault) {
                $output->writeln('<info>Please locate your env file with'
                    .' --'.self::OPTION_LARAVEL
                    .', or --'.self::OPTION_DOTENV.' option.</info>'
                );
            } else {
                $output->writeln('<error>Unable to find '.$envFile.' file.</error>');
            }

            return false;
        }

        return $envFile;
    }

    protected function getAutoloadOption($input, $output, $autoload = null)
    {
        $usingDefault = false;

        if (is_null($autoload)) {
            $autoload = $input->getOption(self::OPTION_AUTOLOAD);
            if (!$autoload) {
                $autoload = 'autoload.php';
                $usingDefault = true;
            }
        }

        // enforce absolute path
        if (!$this->isAbsolutePath($autoload)) {
            $autoload = getcwd().'/'.$autoload;
        }

        // check autoload file exist
        if (!file_exists($autoload)) {
            if ($usingDefault) {
                $output->writeln('<info>Please locate your autoload file with'
                    .' --'.self::OPTION_LARAVEL
                    .', or --'.self::OPTION_AUTOLOAD.' option.</info>'
                );
            } else {
                $output->writeln('<error>Unable to find '.$autoload.' file.</error>');
            }

            return false;
        }

        return $autoload;
    }

    protected function loadEnvFile($envFile)
    {
        (new Dotenv(dirname($envFile), basename($envFile)))->load();
    }

    protected function loadBootFile($bootFile)
    {
        require $bootFile;
    }

    protected function checkEnvAppParameters($output)
    {
        if (getenv(self::ENV_APP_ID) === false) {
            $output->writeln('<error>Error! Environment variable '.self::ENV_APP_ID.' not set.</error>');

            return false;
        }

        if (getenv(self::ENV_API_TOKEN) === false) {
            $output->writeln('<error>Error! Environment variable '.self::ENV_API_TOKEN.' not set.</error>');

            return false;
        }

        if (getenv(self::ENV_APP_ENV) === false) {
            $output->writeln('<error>Error! Environment variable '.self::ENV_APP_ENV.' not set.</error>');

            return false;
        }

        return true;
    }

    protected function checkEnvHandleParameters($output)
    {
        $max = getenv(self::ENV_HANDLE_ONLY);
        if ($max !== false) {
            if (!$this->checkClassTypeFromEnv($output, self::ENV_HANDLE_ONLY, [WorkflowInterface::class, TaskInterface::class])) {
                return false;
            }
        }

        $except = getenv(self::ENV_HANDLE_EXCEPT);
        if ($except !== false) {
            if (!$this->checkClassTypeFromEnv($output, self::ENV_HANDLE_EXCEPT, [WorkflowInterface::class, TaskInterface::class])) {
                return false;
            }
        }

        return true;
    }

    protected function checkConcurrentMaxParameter($output)
    {
        $max = getenv(self::ENV_CONCURRENT_MAX);
        if ($max !== false) {
            if (!preg_match('/^\d+$/', $max)) {
                $output->writeln('<error>Error! Invalid value in '.self::ENV_CONCURRENT_MAX.' env variable - must be an integer.</error>');

                return false;
            }

            if ((int) $max <= 0) {
                $output->writeln('<error>Error! Invalid value in '.self::ENV_CONCURRENT_MAX.' env variable - must be an integer > 0.</error>');

                return false;
            }
        }

        return true;
    }

    protected function getConcurrentMaxParameter()
    {
        $max = getenv(self::ENV_CONCURRENT_MAX);
        if ($max !== false) {
            return (int) $max;
        }

        return 100;
    }

    /**
     * Check that classes provided in env files are implementing TaskInterface or WorkflowInterface.
     *
     * @param string $key  key in env file
     * @param string $type target class
     *
     * @return bool
     */
    protected function checkClassTypeFromEnv($output, $key, $types)
    {
        $classes = array_map('trim', explode(',', getenv($key)));

        foreach ($classes as $class) {
            $that = $this;
            $checkType = function ($type) use ($that, $class) {
                return $that->isImplementationOf($class, $type);
            };
            if (!in_array(true, array_map($checkType, $types))) {
                $output->writeln('<error>Error! Invalid class name '.$class.' in '.$key.' env variable - must implement '.implode(' or ', $types).'</error>');

                return false;
            }
        }

        return true;
    }

    /**
     * Get classes provided in env files that are implementating provided type.
     *
     * @param string $key  key in env file
     * @param string $type target class
     *
     * @return [string]
     */
    protected function getClassNamesByTypeFromEnv($key, $type)
    {
        $names = [];
        $classes = getenv($key) === false ? [] : array_map('trim', explode(',', getenv($key)));

        foreach ($classes as $class) {
            if ($this->isImplementationOf($class, $type)) {
                $names[] = $class;
            }
        }

        return $names;
    }

     /**
      * Returns whether the file path is an absolute path.
      *
      * @param string $file A file path
      *
      * @return bool
      */
     protected function isAbsolutePath($file)
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
