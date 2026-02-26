<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Unit;

use Maduser\Argon\Workflows\Contracts\ContextInterface;
use Maduser\Argon\Workflows\Exceptions\WorkflowException;
use Maduser\Argon\Workflows\HandlerResult;
use PHPUnit\Framework\TestCase;

final class HandlerResultTest extends TestCase
{
    public function testThrowsWhenSignalKeyIsNotString(): void
    {
        $context = $this->createMock(ContextInterface::class);

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Signal names must be strings, int given');

        /** @psalm-suppress InvalidArgument */
        new HandlerResult($context, [0 => true]);
    }

    public function testThrowsWhenSignalValueIsNotBool(): void
    {
        $context = $this->createMock(ContextInterface::class);

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage("Signal 'invalid' must resolve to a boolean, string given");

        /** @psalm-suppress InvalidArgument */
        new HandlerResult($context, ['invalid' => 'yes']);
    }

    public function testAcceptsBooleanSignals(): void
    {
        $context = $this->createMock(ContextInterface::class);

        $result = new HandlerResult($context, ['valid' => true, 'disabled' => false]);

        self::assertSame($context, $result->context);
        self::assertSame(['valid' => true, 'disabled' => false], $result->signals);
    }
}
