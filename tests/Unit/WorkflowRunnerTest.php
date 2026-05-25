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
use Maduser\Argon\Workflows\Tests\Unit\Fixtures\AlienContext;
use Maduser\Argon\Workflows\Tests\Unit\Fixtures\NativeContext;
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
            #[\Override]
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

    public function testThrowsWhenMaxStepsIsInvalid(): void
    {
        $registry = new StateHandlerRegistry();
        $resolver = new TransitionResolver();
        $workflows = new WorkflowRegistry();

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Workflow max steps must be greater than zero, 0 given');

        new WorkflowRunner($registry, $resolver, $workflows, maxSteps: 0);
    }

    public function testThrowsWhenWorkflowExceedsMaxSteps(): void
    {
        $registry = new StateHandlerRegistry();
        $resolver = new TransitionResolver();
        $workflows = new WorkflowRegistry();
        $workflows->add('default', new WorkflowDefinition(['start' => 'start'], []));

        $registry->register('start', new class implements StateHandlerInterface {
            #[\Override]
            public function handle(ContextInterface $context): HandlerResult
            {
                return new HandlerResult($context, []);
            }
        });

        $runner = new WorkflowRunner($registry, $resolver, $workflows, maxSteps: 2);

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage(
            "Workflow 'default' exceeded the configured max step limit of 2 while entering state 'start'"
        );

        $runner->run(new NativeContext('start'));
    }
}
