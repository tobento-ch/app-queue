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

namespace Tobento\App\Queue\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\Queue\LogFailedJobHandler;
use Tobento\Service\Queue\FailedJobHandlerInterface;
use Tobento\Service\Queue\Test\Mock;

class LogFailedJobHandlerTest extends TestCase
{
    public function testImplementsJobFailedJobHandlerInterface()
    {
        $this->assertInstanceof(FailedJobHandlerInterface::class, new LogFailedJobHandler());
    }
    
    public function testHandleFailedJobMethod()
    {
        $handler = new LogFailedJobHandler();
        
        $handler->handleFailedJob(new Mock\CallableJob(), new \Exception('message'));
        
        $this->assertTrue(true);
    }
    
    public function testHandleExceptionMethod()
    {
        $handler = new LogFailedJobHandler();
        
        $handler->handleException(new \Exception('message'));
        
        $this->assertTrue(true);
    }
}