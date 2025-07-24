<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Unit;

use Maduser\Argon\Workflows\Contracts\StateHandlerInterface;
use Maduser\Argon\Workflows\Exceptions\WorkflowException;
use Maduser\Argon\Workflows\StateHandlerRegistry;
use PHPUnit\Framework\TestCase;

class StateHandlerRegistryTest extends TestCase
{
    public function testRegisterThrowsExceptionWhenHandlerAlreadyRegistered(): void
    {
        $registry = new StateHandlerRegistry();
        $handlerMock = $this->createMock(StateHandlerInterface::class);

        $registry->register('start', $handlerMock);

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Handler already registered for state: start');

        $registry->register('start', $handlerMock);
    }

    public function testGetThrowsExceptionWhenHandlerNotRegistered(): void
    {
        $registry = new StateHandlerRegistry();

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('No handler registered for state: missing');

        $registry->get('missing');
    }
}
