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

namespace Tobento\App\Queue;

use Tobento\Service\Queue\FailedJobHandler;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\JobInterface;
use Tobento\App\Logging\LoggerTrait;
use Psr\Log\LogLevel;
use Throwable;

/**
 * LogFailedJobHandler
 */
class LogFailedJobHandler extends FailedJobHandler
{
    use LoggerTrait;
    
    /**
     * The log level used for the logger.
     */
    protected const LOG_LEVEL = LogLevel::ERROR;
    
    /**
     * Create a new LogFailedJobHandler.
     *
     * @param null|QueuesInterface $queues
     */
    public function __construct(
        protected null|QueuesInterface $queues = null,
    ) {}
    
    /**
     * Handle jobs that are finally failed.
     *
     * @param JobInterface $job
     * @param Throwable $e
     * @return void
     */
    protected function finallyFailed(JobInterface $job, Throwable $e): void
    {
        $this->getLogger()->log(
            static::LOG_LEVEL,
            sprintf('Job %s with the id %s failed: %s', $job->getName(), $job->getId(), $e->getMessage()),
            [
                'name' => $job->getName(),
                'id' => $job->getId(),
                'payload' => $job->getPayload(),
                'parameters' => $job->parameters()->jsonSerialize(),
                'exception' => $e,
            ]
        );
    }
    
    /**
     * Handle exception thrown by the worker e.g.
     *
     * @param Throwable $e
     * @return void
     */
    public function handleException(Throwable $e): void
    {
        $this->getLogger()->log(
            static::LOG_LEVEL,
            sprintf('Queue exception: %s', $e->getMessage()),
            ['exception' => $e]
        );
    }
}