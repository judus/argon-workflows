<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Integration;

use Maduser\Argon\Workflows\Contracts\ContextInterface;
use Maduser\Argon\Workflows\Contracts\StateHandlerInterface;
use Maduser\Argon\Workflows\HandlerResult;
use Maduser\Argon\Workflows\StateHandlerRegistry;
use Maduser\Argon\Workflows\TransitionResolver;
use Maduser\Argon\Workflows\WorkflowDefinition;
use Maduser\Argon\Workflows\WorkflowRegistry;
use Maduser\Argon\Workflows\WorkflowRunner;
use PHPUnit\Framework\TestCase;

final class WorkflowIntegrationTest extends TestCase
{
    public function testSimpleWorkflowExecution(): void
    {
        $context = new TestContext('start', ['complete']);

        $startHandler = new class implements StateHandlerInterface {
            public function handle(ContextInterface $context): HandlerResult
            {
                return new HandlerResult($context, ['processSignal' => true]);
            }
        };

        $processHandler = new class implements StateHandlerInterface {
            public function handle(ContextInterface $context): HandlerResult
            {
                return new HandlerResult($context, []);
            }
        };

        $completeHandler = new class implements StateHandlerInterface {
            public function handle(ContextInterface $context): HandlerResult
            {
                return new HandlerResult($context, []);
            }
        };

        $stateHandlerRegistry = new StateHandlerRegistry();
        $stateHandlerRegistry->register('start', $startHandler);
        $stateHandlerRegistry->register('process', $processHandler);
        $stateHandlerRegistry->register('complete', $completeHandler);

        // Define transitions matching 'HandlerResult' signals
        $workflowDefinition = new WorkflowDefinition(
            [
                'start' => 'process',
                'process' => 'complete',
                'complete' => 'complete'
            ],
            ['processSignal' => 'process']
        );

        $workflowRegistry = new WorkflowRegistry();
        $workflowRegistry->add('simpleWorkflow', $workflowDefinition);

        $runner = new WorkflowRunner(
            $stateHandlerRegistry,
            new TransitionResolver(),
            $workflowRegistry
        );

        // Verify the integration by running the workflow
        $actualContext = $runner->run($context, 'simpleWorkflow');

        // Assert final context state
        $this->assertEquals('complete', $actualContext->getState());
    }
}

final class TestContext implements ContextInterface
{
    /**
     * @param array<string> $terminalStates
     */
    public function __construct(
        private string $state,
        private array $terminalStates
    ) {
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isComplete(): bool
    {
        return in_array($this->state, $this->terminalStates, true);
    }

    public function withState(string $state): ContextInterface
    {
        return new self($state, $this->terminalStates);
    }
}
