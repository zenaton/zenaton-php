> ⚠️ This repository is abandoned.

<p align="center">
  <a href="https://zenaton.com" target="_blank">
    <img src="https://user-images.githubusercontent.com/36400935/58254828-e5176880-7d6b-11e9-9094-3f46d91faeee.png" target="_blank" />
  </a><br>
  Easy Asynchronous Jobs Manager for Developers <br>
  <a href="https://zenaton.com/documentation/php/getting-started/" target="_blank">
    <strong> Explore the docs » </strong>
  </a> <br>
  <a href="https://zenaton.com" target="_blank"> Website </a>
    ·
  <a href="https://github.com/zenaton/examples-php" target="_blank"> Examples in PHP </a>
    ·
  <a href="https://app.zenaton.com/tutorial/php" target="_blank"> Tutorial in PHP </a>
</p>
<p align="center">
  <a href="https://packagist.org/packages/zenaton/zenaton-php" rel="nofollow" target="_blank"><img src="https://img.shields.io/packagist/v/zenaton/zenaton-php.svg" alt="Packagist" style="max-width:100%;"></a>
  <a href="https://circleci.com/gh/zenaton/zenaton-php/tree/master" rel="nofollow" target="_blank"><img src="https://img.shields.io/circleci/project/github/zenaton/zenaton-php/master.svg" alt="CircleCI" style="max-width:100%;"></a>
  <a href="/LICENSE" target="_blank"><img src="https://img.shields.io/badge/License-Apache%202.0-blue.svg" alt="License" style="max-width:100%;"></a>
</p>

# Zenaton library for PHP

[Zenaton](https://zenaton.com) helps developers to easily run, monitor and orchestrate background jobs on your workers without managing a queuing system. In addition to this, a monitoring dashboard shows you in real-time tasks executions and helps you to handle errors.

The Zenaton library for PHP lets you code and launch tasks using Zenaton platform, as well as write workflows as code. You can sign up for an account on [Zenaton](https://zenaton.com) and go through the [tutorial in PHP](https://app.zenaton.com/tutorial/php).

## PHP Documentation

You can find all details on [Zenaton's website](https://zenaton.com/documentation/php/getting-started#introduction).

<details>
  <summary><strong>Table of contents</strong></summary>

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

- [Getting started](#getting-started)
  - [Installation](#installation)
    - [Install the Zenaton Agent](#install-the-zenaton-agent)
    - [Install the library](#install-the-library)
    - [Framework integration](#framework-integration)
  - [Quick start](#quick-start)
    - [Client Initialization](#client-initialization)
    - [Executing a background job](#executing-a-background-job)
  - [Orchestrating background jobs](#orchestrating-background-jobs)
    - [Using workflows](#using-workflows)
- [Getting help](#getting-help)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

</details>

## Getting started

### Installation

#### Install the Zenaton Agent

To install the Zenaton agent, run the following command:

```sh
curl https://install.zenaton.com/ | sh
```

Then, you need your agent to listen to your application.
To do this, you need your **Application ID** and **API Token**.
You can find both on [your Zenaton account](https://app.zenaton.com/api).

```sh
zenaton listen --app_id=YourApplicationId --api_token=YourApiToken --app_env=YourApplicationEnv
```

#### Install the library

To add the latest version of the library to your project, run the following command:

```sh
composer require zenaton/zenaton-php
```

#### Framework integration

If you are using **Laravel** or **Symfony**, please refer to our dedicated documentation to get started:

- [Getting started with Laravel](https://zenaton.com/documentation/php/agents#laravel)
- [Getting started with Symfony](https://zenaton.com/documentation/php/agents#symfony)

### Quick start

#### Client Initialization

To start, you need to initialize the client. To do this, you need your **Application ID** and **API Token**.
You can find both on [your Zenaton account](https://app.zenaton.com/api).

Then, initialize your Zenaton client:

```php
Zenaton\Client::init('YourApplicationId', 'YourApiToken', 'YourApplicationEnv');
```

#### Executing a background job

A background job in Zenaton is a class implementing the `Zenaton\Interfaces\TaskInterface` interface.

Let's start by implementing a first task printing something, and returning a value:

```php
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Traits\Zenatonable;

class HelloWorldTask implements TaskInterface
{
    public function handle()
    {
        echo "Hello World\n";

        return mt_rand(0, 1);
    }
}
```

Now, when you want to run this task as a background job, you need to do the following:

```php
(new HelloWorldTask())->dispatch();
```

That's all you need to get started. With this, you can run many background jobs.
However, the real power of Zenaton is to be able to orchestrate these jobs. The next section will introduce you to job orchestration.

### Orchestrating background jobs

Job orchestration is what allows you to write complex business workflows in a simple way.
You can execute jobs sequentially, in parallel, conditionally based on the result of a previous job,
and you can even use loops to repeat some tasks.

We wrote about some use-cases of job orchestration, you can take a look at [these articles](https://medium.com/zenaton/tagged/php)
to see how people use job orchestration.

#### Using workflows

A workflow in Zenaton is a class implementing the `Zenaton\Interfaces\WorkflowInterface` interface.

We will implement a very simple workflow:

First, it will execute the `HelloWorld` task.
The result of the first task will be used to make a condition using an `if` statement.
When the returned value will be greater than `0`, we will execute a second task named `FinalTask`.
Otherwise, we won't do anything else.

One important thing to remember is that your workflow implementation **must** be idempotent.
You can read more about that in our [documentation](https://zenaton.com/documentation/php/workflow-basics/#implementation).

The implementation looks like this:

```php
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Traits\Zenatonable;

class MyFirstWorkflow implements WorkflowInterface
{
    use Zenatonable;

    public function handle()
    {
        $n = (new HelloWorldTask())->execute();
        if ($n > 0) {
            (new FinalTask())->execute();
        }
    }
}
```

Now that your workflow is implemented, you can execute it by calling the `dispatch` method:

```php
(new MyFirstWorkflow())->dispatch();
```

If you really want to run this example, you will need to implement the `FinalTask` task.

There are many more features usable in workflows in order to get the orchestration done right. You can learn more
in our [documentation](https://zenaton.com/documentation/php/workflow-basics/#implementation).

## Getting help

**Need help**? Feel free to contact us by chat on [Zenaton](https://zenaton.com/).

**Found a bug?** You can open a [GitHub issue](https://github.com/zenaton/zenaton-php/issues).
