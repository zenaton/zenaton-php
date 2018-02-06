# Zenaton library for PHP

This Zenaton library for PHP lets you code and launch workflows using Zenaton platform. You can sign up for an account at [https://zenaton.com](https://zenaton.com).

## Requirements
PHP 5.4 and later.

## Composer
To add this library to your project, run the following command:

```
composer require zenaton/zenaton-php
```

To use it, use Composer's autoload:

```
require_once('vendor/autoload.php');
```

## Client Initialisation
You should have a `.env` file with `ZENATON_APP_ID`, `ZENATON_API_TOKEN` and `ZENATON_APP_ENV` parameters. You'll find the [here](https://zenaton.com/app/api).

Then initialize your Zenaton client:
```
(new Dotenv\Dotenv(__DIR__))->load();
$app_id = getenv('ZENATON_APP_ID');
$api_token = getenv('ZENATON_API_TOKEN');
$app_env = getenv('ZENATON_APP_ENV');
Zenaton\Client::init($app_id, $api_token, $app_env);
```

## Writing Workflows and Tasks
Writing a workflow is as simple as:
 ```
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Traits\Zenatonable;

class MyWorkflow implements WorkflowInterface
{
    use Zenatonable;

    public function handle() {
        // workflow implementation
    }
}
```
Note that your workflow implementation should be idempotent.
see [documentation](https://zenaton.com/app/documentation#workflow-basics-implementation).

Writing a task is as simple as:
 ```
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Traits\Zenatonable;

class MyTask implements TaskInterface
{
    use Zenatonable;

    public function handle() {
        // task implementation
    }
}
```

## Launching a workflow
Once your Zenaton client is initialised, you can start a workflow with
```
(new MyWorkflow)->dispatch();
```

## Worker Installation
Your workflow's tasks will be executed on your worker servers. Please install a Zenaton worker on it:
```
curl https://install.zenaton.com | sh
```

that you configure with
```
zenaton listen --env=.env --boot=autoload.php
```
where `.env` is the env file containing your credentials, and `autoload.php` is a file that will be included before each task execution - this file should load all classes you need.


## Documentation
Please see https://zenaton.com/documentation for complete documentation.
