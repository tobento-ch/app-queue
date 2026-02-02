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

namespace Tobento\App\Queue\Migration;

use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\Database\Migration\DatabaseMigration;
use Tobento\Service\Database\Processor\ProcessorInterface;
use Tobento\Service\Database\Schema\Table;
use Tobento\Service\Database\Storage\StorageDatabase;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\Storage\Queue as StorageQueue;

class StoragesMigration extends DatabaseMigration
{
    /**
     * Create a new instance.
     *
     * @param ProcessorInterface $processor
     * @param DatabasesInterface $databases
     * @param QueuesInterface $queues
     */
    public function __construct(
        protected ProcessorInterface $processor,
        protected DatabasesInterface $databases,
        protected QueuesInterface $queues,
    ) {
        $this->registerTables();
    }
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Queues databases';
    }
        
    /**
     * Register tables used by the install and uninstall methods
     * to create the actions from.
     *
     * @return void
     */
    protected function registerTables(): void
    {
        foreach($this->queues->names() as $name) {
            $queue = $this->queues->queue(name: $name);
            
            if (! $queue instanceof StorageQueue) {
                continue;
            }
            
            $storage = $queue->storage();
            
            $this->registerTable(
                table: function() use ($storage): Table {
                    $table = new Table(name: $storage->getTable());
                    $table->bigPrimary('id');
                    $table->string('queue', 100);
                    $table->string('job_id', 255);
                    $table->string('name', 100);
                    $table->json('payload');
                    $table->json('parameters');
                    $table->int('priority', 11);
                    $table->timestamp('available_at');
                    return $table;
                },
                database: new StorageDatabase($storage),
                name: 'Queue jobs',
                description: 'Queue jobs database table',
            );
        }
    }
}