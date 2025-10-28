<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Unit;

use Maduser\Argon\Workflows\Contracts\ContextInterface;
use Maduser\Argon\Workflows\Contracts\StateHandlerInterface;
use Maduser\Argon\Workflows\Exceptions\WorkflowException;
use Maduser\Argon\Workflows\HandlerResult;
use Maduser\Argon\Workflows\StateHandlerRegistry;
use Maduser\Argon\Workflows\TransitionResolver;
use Maduser\Argon\Workflows\WorkflowDefinition;
use Maduser\Argon\Workflows\WorkflowRegistry;
use Maduser\Argon\Workflows\WorkflowRunner;
use PHPUnit\Framework\TestCase;

final class WorkflowRunnerTest extends TestCase
{
    public function testThrowsWhenHandlerChangesContextImplementation(): void
    {
        $registry = new StateHandlerRegistry();
        $resolver = new TransitionResolver();
        $workflows = new WorkflowRegistry();
        $workflows->add('default', new WorkflowDefinition(['start' => 'done'], []));

        $registry->register('start', new class implements StateHandlerInterface {
            public function handle(ContextInterface $context): HandlerResult
            {
                return new HandlerResult(new AlienContext('mutated'), ['mutated' => true]);
            }
        });

        $runner = new WorkflowRunner($registry, $resolver, $workflows);

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage(
            'Handler for state start returned context of type ' . AlienContext::class .
            '; expected ' . NativeContext::class
        );

        $runner->run(new NativeContext('start'));
    }
}

final class NativeContext implements ContextInterface
{
    public function __construct(
        private string $state,
        private bool $complete = false
    ) {
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isComplete(): bool
    {
        return $this->complete;
    }

    public function withState(string $state): ContextInterface
    {
        return new self($state, $state === 'done');
    }
}

final class AlienContext implements ContextInterface
{
    public function __construct(private string $state)
    {
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isComplete(): bool
    {
        return false;
    }

    public function withState(string $state): ContextInterface
    {
        return new self($state);
    }
}
