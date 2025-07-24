<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Unit;

use Maduser\Argon\Workflows\Contracts\ContextInterface;
use Maduser\Argon\Workflows\TransitionResolver;
use Maduser\Argon\Workflows\WorkflowDefinition;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TransitionResolverTest extends TestCase
{
    public function testSignalMustBeExplicitlyTruthy(): void
    {
        $resolver = new TransitionResolver();

        $definition = new WorkflowDefinition(
            ['start' => 'next'],
            ['signalX' => 'alt']
        );

        $context = $this->createMock(ContextInterface::class);
        $context->method('getState')->willReturn('start');

        // signal is missing => resolve() should fallback to static
        $signals = []; // infection flipped this to true

        $result = $resolver->resolve($context, $signals, $definition);
        $this->assertEquals('next', $result);
    }

    public function testThrowsWhenNoSignalAndNoStaticTransition(): void
    {
        $resolver = new TransitionResolver();

        $definition = new WorkflowDefinition(
            [], // no static transitions
            ['signalY' => 'somewhere']
        );

        $context = $this->createMock(ContextInterface::class);
        $context->method('getState')->willReturn('nowhere');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No valid transition for state: nowhere');

        $resolver->resolve($context, [], $definition);
    }
}
