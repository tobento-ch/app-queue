# App Queue

Queue support for the app using the [Queue Service](https://github.com/tobento-ch/service-queue).

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Queue Boot](#queue-boot)
        - [Queue Config](#queue-config)
        - [Creating Jobs](#creating-jobs)
        - [Dispatching Jobs](#dispatching-jobs)
        - [Running Queues](#running-queues)
        - [Failed Jobs](#failed-jobs)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app queue project running this command.

```
composer require tobento/app-queue
```

## Requirements

- PHP 8.0 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Queue Boot

The queue boot does the following:

* installs and loads queue config file
* implements queue interfaces

```php
use Tobento\App\AppFactory;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\FailedJobHandlerInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots
$app->boot(\Tobento\App\Queue\Boot\Queue::class);
$app->booting();

// Implemented interfaces:
$queue = $app->get(QueueInterface::class);
$queues = $app->get(QueuesInterface::class);
$jobProcessor = $app->get(JobProcessorInterface::class);
$failedJobHandler = $app->get(FailedJobHandlerInterface::class);

// Run the app
$app->run();
```

**Console Note**

The queue boot automatically boots the [App Console Boot](https://github.com/tobento-ch/app-console#console-boot) as to run the queue worker using commands.

If you are not using the [App Skeleton](https://github.com/tobento-ch/app-skeleton) you may adjust the ```app``` file in the root directory with the path to your app:

```php
// Get and run the application.
// (require __DIR__.'/app/app.php')->run();
(require __DIR__.'/path/to/app.php')->run();
```

### Queue Config

The configuration for the queue is located in the ```app/config/queue.php``` file at the default [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) config location where you can specify the queues for your application.

### Creating Jobs

Check out the [Queue Service - Creating Jobs](https://github.com/tobento-ch/service-queue#creating-jobs) section to learn more about creating jobs.

### Dispatching Jobs

Check out the [Queue Service - Dispatching Jobs](https://github.com/tobento-ch/service-queue#dispatching-jobs) section to learn more about creating jobs.

### Running Queues

To run queues you may run the [Queue Worker](https://github.com/tobento-ch/service-queue#worker) using the ```queue:work``` console command. 

```
php app queue:work
```

Check out the [Queue Service - Work Command](https://github.com/tobento-ch/service-queue#work-command) section to learn more about the command.

To keep the ```queue:work``` process running permanently in the background, you should use a process monitor such as Supervisor to ensure that the queue worker does not stop running.

**Alternatives**

Alternatively, you may run the ```queue:work``` command using the [App Schedule](https://github.com/tobento-ch/app-schedule) by using the [Command Task](https://github.com/tobento-ch/service-schedule#command-task) running every minute:

```
use Tobento\Service\Schedule\Task\CommandTask;

$task = (new CommandTask(
    command: 'queue:work',
    input: [
        // you may stop the queue to work when it is empty:
        '--stop-when-empty' => null,
        
        // you may run only a specific queue instead of all:
        '--queue' => 'secondary',
        
        // it is advised to define the timeout in seconds
        // before your server times out, otherwise
        // failed jobs might not be handled.
        '--timeout' => 60,
    ],
))->cron('* * * * *');
```

### Failed Jobs

By default, failed jobs will be handled by the implemented ```\Tobento\App\Queue\LogFailedJobHandler::class```. Any failed job will be repushed to the queue until it reaches the maximum retries. Once it has reached the maximum retries the job will not be queued anymore and will be sent into the app log.

On the [App Logging Config](https://github.com/tobento-ch/app-logging#logging-config) file you may define a specific logger:

```
/*
|--------------------------------------------------------------------------
| Aliases
|--------------------------------------------------------------------------
*/

'aliases' => [
    \Tobento\App\Queue\LogFailedJobHandler::class => 'error',
],
```

**Custom Handler**

You can change the default behavior by creating a custom failed job handler and using the app ```on``` method to replace the implemented handler:

```
use Tobento\Service\Queue\FailedJobHandlerInterface;

$app->on(FailedJobHandlerInterface::class, function (): FailedJobHandlerInterface {
    return new CustomFailedJobHandler();
});
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)