<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Unit;

use Maduser\Argon\Workflows\Contracts\ContextInterface;
use Maduser\Argon\Workflows\Contracts\StateHandlerInterface;
use Maduser\Argon\Workflows\ExecutionEvent;
use Maduser\Argon\Workflows\HandlerResult;
use Maduser\Argon\Workflows\StateHandlerRegistry;
use Maduser\Argon\Workflows\TransitionResolver;
use Maduser\Argon\Workflows\WorkflowDefinition;
use Maduser\Argon\Workflows\WorkflowRegistry;
use Maduser\Argon\Workflows\WorkflowRunner;
use Maduser\Argon\Workflows\Tests\Unit\Fixtures\EventCollector;
use Maduser\Argon\Workflows\Tests\Unit\Fixtures\EventContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WorkflowRunnerEventsTest extends TestCase
{
    public function testEmitsEventsInOrder(): void
    {
        $registry = new StateHandlerRegistry();
        $resolver = new TransitionResolver();
        $workflows = new WorkflowRegistry();
        $workflows->add('default', new WorkflowDefinition(['start' => 'done'], []));

        $registry->register('start', new class implements StateHandlerInterface {
            #[\Override]
            public function handle(ContextInterface $context): HandlerResult
            {
                return new HandlerResult($context, []);
            }
        });

        $observer = new EventCollector();
        $runner = new WorkflowRunner($registry, $resolver, $workflows, null, $observer);

        $runner->run(new EventContext('start'));

        $types = array_map(
            static fn (ExecutionEvent $event): string => $event->type,
            $observer->events
        );

        $this->assertSame(
            [
                ExecutionEvent::TYPE_RUN_STARTED,
                ExecutionEvent::TYPE_STEP_STARTED,
                ExecutionEvent::TYPE_TRANSITION_TAKEN,
                ExecutionEvent::TYPE_STEP_FINISHED,
                ExecutionEvent::TYPE_RUN_FINISHED,
            ],
            $types
        );

        $runId = $observer->events[0]->runId;
        foreach ($observer->events as $event) {
            $this->assertSame($runId, $event->runId);
            $this->assertSame('default', $event->workflowId);
        }

        $this->assertSame('start', $observer->events[1]->state);
        $this->assertSame('done', $observer->events[4]->state);

        $transitionMeta = $observer->events[2]->meta;
        $this->assertSame('start', $transitionMeta['from']);
        $this->assertSame('done', $transitionMeta['to']);
    }

    public function testEmitsFailureEventsWhenHandlerThrows(): void
    {
        $registry = new StateHandlerRegistry();
        $resolver = new TransitionResolver();
        $workflows = new WorkflowRegistry();
        $workflows->add('default', new WorkflowDefinition(['start' => 'done'], []));

        $registry->register('start', new class implements StateHandlerInterface {
            #[\Override]
            public function handle(ContextInterface $context): HandlerResult
            {
                throw new RuntimeException('boom');
            }
        });

        $observer = new EventCollector();
        $runner = new WorkflowRunner($registry, $resolver, $workflows, null, $observer);

        $thrown = null;
        try {
            $runner->run(new EventContext('start'));
        } catch (RuntimeException $exception) {
            $thrown = $exception;
        }

        $this->assertInstanceOf(RuntimeException::class, $thrown);

        $types = array_map(
            static fn (ExecutionEvent $event): string => $event->type,
            $observer->events
        );

        $this->assertSame(
            [
                ExecutionEvent::TYPE_RUN_STARTED,
                ExecutionEvent::TYPE_STEP_STARTED,
                ExecutionEvent::TYPE_STEP_FAILED,
                ExecutionEvent::TYPE_RUN_FAILED,
            ],
            $types
        );

        $errorMeta = $observer->events[2]->meta['error'] ?? null;
        $this->assertIsArray($errorMeta);
        /** @var array{type: string, message: string} $errorMeta */
        $this->assertSame(RuntimeException::class, $errorMeta['type']);
        $this->assertSame('boom', $errorMeta['message']);
    }

    public function testUsesProvidedRunId(): void
    {
        $registry = new StateHandlerRegistry();
        $resolver = new TransitionResolver();
        $workflows = new WorkflowRegistry();
        $workflows->add('default', new WorkflowDefinition(['start' => 'done'], []));

        $registry->register('start', new class implements StateHandlerInterface {
            #[\Override]
            public function handle(ContextInterface $context): HandlerResult
            {
                return new HandlerResult($context, []);
            }
        });

        $observer = new EventCollector();
        $runner = new WorkflowRunner($registry, $resolver, $workflows, null, $observer);

        $runner->run(new EventContext('start'), 'default', 'run-123');

        $this->assertSame('run-123', $observer->events[0]->runId);
        $this->assertSame('run-123', $observer->events[1]->runId);
    }

    public function testTerminalStatesAllowFinishingWithoutSyntheticEndTransition(): void
    {
        $registry = new StateHandlerRegistry();
        $resolver = new TransitionResolver();
        $workflows = new WorkflowRegistry();
        $workflows->add('default', new WorkflowDefinition(
            ['start' => 'process', 'process' => 'final'],
            [],
            null,
            ['final']
        ));

        $registry->register('start', new class implements StateHandlerInterface {
            #[\Override]
            public function handle(ContextInterface $context): HandlerResult
            {
                return new HandlerResult($context, []);
            }
        });

        $registry->register('process', new class implements StateHandlerInterface {
            #[\Override]
            public function handle(ContextInterface $context): HandlerResult
            {
                return new HandlerResult($context, []);
            }
        });

        $registry->register('final', new class implements StateHandlerInterface {
            #[\Override]
            public function handle(ContextInterface $context): HandlerResult
            {
                return new HandlerResult($context, []);
            }
        });

        $context = new class('start') implements ContextInterface {
            public function __construct(private string $state)
            {
            }

            #[\Override]
            public function getState(): string
            {
                return $this->state;
            }

            #[\Override]
            public function isComplete(): bool
            {
                return false;
            }

            #[\Override]
            public function withState(string $state): ContextInterface
            {
                return new self($state);
            }
        };

        $observer = new EventCollector();
        $runner = new WorkflowRunner($registry, $resolver, $workflows, null, $observer);

        $result = $runner->run($context);
        $this->assertSame('final', $result->getState());

        $types = array_map(
            static fn (ExecutionEvent $event): string => $event->type,
            $observer->events
        );

        $this->assertSame(
            [
                ExecutionEvent::TYPE_RUN_STARTED,
                ExecutionEvent::TYPE_STEP_STARTED,
                ExecutionEvent::TYPE_TRANSITION_TAKEN,
                ExecutionEvent::TYPE_STEP_FINISHED,
                ExecutionEvent::TYPE_STEP_STARTED,
                ExecutionEvent::TYPE_TRANSITION_TAKEN,
                ExecutionEvent::TYPE_STEP_FINISHED,
                ExecutionEvent::TYPE_STEP_STARTED,
                ExecutionEvent::TYPE_STEP_FINISHED,
                ExecutionEvent::TYPE_RUN_FINISHED,
            ],
            $types
        );
    }
}
