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

class WorkflowIntegrationTest extends TestCase
{
    public function testSimpleWorkflowExecution(): void
    {
        $contextMock = $this->createMock(ContextInterface::class);

        // Arrange mock behavior with assurance against null fall-through
        $contextMock->method('getState')
            ->willReturnOnConsecutiveCalls('start', 'process', 'complete', 'complete');

        // Let isComplete return as expected over multiple calls
        $contextMock->method('isComplete')
            ->willReturnOnConsecutiveCalls(false, false, true);

        $contextMock->method('withState')
            ->will($this->returnSelf());

        // Handler Mocks
        $startHandler = $this->createMock(StateHandlerInterface::class);
        $startHandler->method('handle')
            ->willReturn(new HandlerResult($contextMock, ['processSignal' => true]));

        $processHandler = $this->createMock(StateHandlerInterface::class);
        $processHandler->method('handle')
            ->willReturn(new HandlerResult($contextMock, []));

        $completeHandler = $this->createMock(StateHandlerInterface::class);
        $completeHandler->method('handle')
            ->willReturn(new HandlerResult($contextMock, []));

        // Registry with handlers
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
        $actualContext = $runner->run($contextMock, 'simpleWorkflow');

        // Assert final context state
        $this->assertEquals('complete', $actualContext->getState());
    }
}
