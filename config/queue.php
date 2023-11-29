<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\QueueFactoryInterface;
use Tobento\Service\Queue\QueueFactory;
use Tobento\Service\Queue\Storage\QueueFactory as StorageQueueFactory;
use Tobento\Service\Queue\SyncQueue;
use Tobento\Service\Queue\NullQueue;
use Tobento\Service\Storage\JsonFileStorage;
use Psr\Container\ContainerInterface;
use function Tobento\App\{directory};

return [
    
    /*
    |--------------------------------------------------------------------------
    | Queues
    |--------------------------------------------------------------------------
    |
    | Configure any queues needed for your application. The first queue
    | is the default queue used if none specific defined on jobs.
    |
    | see: https://github.com/tobento-ch/service-queue#queue-factories
    |
    */
    
    'queues' => [
        // using a factory:
        'sync' => [
            // factory must implement QueueFactoryInterface
            'factory' => QueueFactory::class,
            'config' => [
                'queue' => SyncQueue::class,
            ],
        ],
        
        'file' => [
            // factory must implement QueueFactoryInterface
            'factory' => StorageQueueFactory::class,
            'config' => [
                // specify the table storage:
                'table' => 'jobs',

                // specify the storage:
                'storage' => JsonFileStorage::class,
                'dir' => directory('app').'storage/queue/',

                // you may specify a priority,
                // higher queue jobs gets first processed.
                'priority' => 100, // 100 is default
            ],
        ],
        
        // or you may sometimes just create the queue (not lazy):
        'null' => new NullQueue(name: 'null'),
        
        // example using a closure:
        /*'name' => static function (string $name, ContainerInterface $c): QueueInterface {
            // create queue ...
            return $queue;
        },*/
    ],
    
];