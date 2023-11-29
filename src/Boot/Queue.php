<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Queue\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Functions;
use Tobento\App\Boot\Config;
use Tobento\App\Migration\Boot\Migration;
use Tobento\App\Console\Boot\Console;
use Tobento\App\Logging\Boot\Logging;
use Tobento\App\Queue\LogFailedJobHandler;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\LazyQueues;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\SyncQueue;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\FailedJobHandlerInterface;
use Tobento\Service\Console\ConsoleInterface;

/**
 * Queue
 */
class Queue extends Boot
{
    public const INFO = [
        'boot' => [
            'installs and loads queue config file',
            'implements queue interfaces',
        ],
    ];

    public const BOOT = [
        Functions::class,
        Config::class,
        Migration::class,
        Logging::class,
        Console::class,
    ];

    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @param Config $config
     * @return void
     */
    public function boot(Migration $migration, Config $config): void
    {
        // install migration:
        $migration->install(\Tobento\App\Queue\Migration\Queue::class);
        
        // interfaces:
        $this->app->set(JobProcessorInterface::class, JobProcessor::class);
        
        $this->app->set(FailedJobHandlerInterface::class, LogFailedJobHandler::class);

        $this->app->set(QueuesInterface::class, function() use ($config): QueuesInterface {
            
            $config = $config->load(file: 'queue.php');
            
            return new LazyQueues(
                container: $this->app->container(),
                queues: $config['queues'] ?? [],
            );
        });
        
        $this->app->set(QueueInterface::class, function (): QueueInterface {
            $queues = $this->app->get(QueuesInterface::class);
            
            if ($queues instanceof QueueInterface) {
                return $queues;
            }
            
            $name = $queues->names()[0] ?? '';
            
            if ($queues->has($name)) {
                return $queues->get($name);
            }
            
            return new SyncQueue(
                name: 'sync',
                jobProcessor: $this->app->get(JobProcessorInterface::class),
            );
        });

        // console commands:
        $this->app->on(ConsoleInterface::class, function(ConsoleInterface $console): void {
            $console->addCommand(\Tobento\Service\Queue\Console\WorkCommand::class);
            $console->addCommand(\Tobento\Service\Queue\Console\ClearCommand::class);
        });
    }
}