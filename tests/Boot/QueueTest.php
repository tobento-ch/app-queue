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

namespace Tobento\App\Queue\Test\Boot;

use PHPUnit\Framework\TestCase;
use Tobento\App\Queue\Boot\Queue;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\FailedJobHandlerInterface;
use Tobento\Service\Queue\LazyQueues;
use Tobento\Service\Queue\SyncQueue;
use Tobento\Service\Queue\NullQueue;
use Tobento\Service\Queue\Storage\Queue as StorageQueue;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\App\AppInterface;
use Tobento\App\AppFactory;
use Tobento\App\Boot;
use Tobento\Service\Filesystem\Dir;

class QueueTest extends TestCase
{
    protected function createApp(bool $deleteDir = true): AppInterface
    {
        if ($deleteDir) {
            (new Dir())->delete(__DIR__.'/../app/');
        }
        
        (new Dir())->create(__DIR__.'/../app/');
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../../'), 'root')
            ->dir(realpath(__DIR__.'/../app/'), 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config')
            ->dir($app->dir('root').'vendor', 'vendor');
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testInterfacesAreAvailable()
    {
        $app = $this->createApp();
        $app->boot(Queue::class);
        $app->booting();
        
        $this->assertInstanceof(QueueInterface::class, $app->get(QueueInterface::class));
        $this->assertInstanceof(QueuesInterface::class, $app->get(QueuesInterface::class));
        $this->assertInstanceof(JobProcessorInterface::class, $app->get(JobProcessorInterface::class));
        $this->assertInstanceof(FailedJobHandlerInterface::class, $app->get(FailedJobHandlerInterface::class));
    }
    
    public function testDefaultConfigQueuesAreAvailable()
    {
        $app = $this->createApp();
        $app->boot(Queue::class);
        $app->booting();
        $queues = $app->get(QueuesInterface::class);
        
        $this->assertInstanceof(SyncQueue::class, $queues->get(name: 'sync'));
        $this->assertInstanceof(StorageQueue::class, $queues->get(name: 'file'));
        $this->assertInstanceof(NullQueue::class, $queues->get(name: 'null'));
    }
    
    public function testQueueUsesLazyQueues()
    {
        $app = $this->createApp();
        $app->boot(Queue::class);
        $app->booting();
        
        $this->assertInstanceof(LazyQueues::class, $app->get(QueueInterface::class));
    }
    
    public function testQueueUsesFirstQueueFromQueuesIfNotImplementingQueueInterface()
    {
        $app = $this->createApp();
        $app->boot(Queue::class);
        $app->booting();
        
        $app->on(QueuesInterface::class, function (): QueuesInterface {
            return new class() implements QueuesInterface {
                public function queue(string $name): QueueInterface
                {
                    return new NullQueue(name: 'first');
                }

                public function get(string $name): null|QueueInterface
                {
                    return new NullQueue(name: 'first');
                }

                public function has(string $name): bool
                {
                    return in_array($name, ['first']);
                }

                public function names(): array
                {
                    return ['first'];
                }
            };
        });
        
        $this->assertInstanceof(NullQueue::class, $app->get(QueueInterface::class));
    }
    
    public function testQueueUsesSyncQueueIfQueuesNotImplementingQueueInterfaceAndIsEmpty()
    {
        $app = $this->createApp();
        $app->boot(Queue::class);
        $app->booting();
        
        $app->on(QueuesInterface::class, function (): QueuesInterface {
            return new class() implements QueuesInterface {
                public function queue(string $name): QueueInterface
                {
                    throw new \Exception('queue not found');
                }

                public function get(string $name): null|QueueInterface
                {
                    return null;
                }

                public function has(string $name): bool
                {
                    return false;
                }

                public function names(): array
                {
                    return [];
                }
            };
        });
        
        $this->assertInstanceof(SyncQueue::class, $app->get(QueueInterface::class));
    }
    
    public function testConsoleCommandsAreAvailable()
    {
        $app = $this->createApp();
        $app->boot(Queue::class);
        $app->booting();
        
        $console = $app->get(ConsoleInterface::class);
        $this->assertTrue($console->hasCommand('queue:work'));
        $this->assertTrue($console->hasCommand('queue:clear'));
    }
    
    public function testQueueWorkRunCommand()
    {
        $app = $this->createApp();
        $app->boot(Queue::class);
        $app->boot(\Tobento\App\Event\Boot\Event::class);
        $app->booting();

        $executed = $app->get(ConsoleInterface::class)->execute(
            command: 'queue:work',
            input: ['--sleep' => 0, '--stop-when-empty' => null]
        );
        
        $output = $executed->output();
        $this->assertSame(0, $executed->code());
        $this->assertStringContainsString('Worker default starting', $output);
        $this->assertStringContainsString('Worker default stopped', $output);
    }
}