<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

use Maduser\Argon\Workflows\Contracts\ExecutionObserverInterface;
use Maduser\Argon\Workflows\Contracts\ContextInterface;
use Maduser\Argon\Workflows\Exceptions\WorkflowException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class WorkflowRunner
 * Executes workflows by running their handlers in sequence.
 */
final readonly class WorkflowRunner
{
    private ExecutionObserverInterface $observer;

    public function __construct(
        private StateHandlerRegistry $registry,
        private TransitionResolver $resolver,
        private WorkflowRegistry $workflowRegistry,
        private ?LoggerInterface $logger = null,
        ?ExecutionObserverInterface $observer = null,
        private int $maxSteps = 1000,
    ) {
        if ($this->maxSteps < 1) {
            throw WorkflowException::forInvalidMaxSteps($this->maxSteps);
        }

        $this->observer = $observer ?? new NullExecutionObserver();
    }

    /**
     * Runs the specified workflow and processes transitions.
     *
     * @param ContextInterface $context
     * @param string $workflowId
     * @param string|null $runId
     * @return ContextInterface
     */
    public function run(
        ContextInterface $context,
        string $workflowId = 'default',
        ?string $runId = null
    ): ContextInterface {
        $workflowStart = microtime(true);
        $runId = $runId ?? $this->createRunId();
        $this->log("Running workflow: " . $workflowId);

        $this->emit(
            ExecutionEvent::TYPE_RUN_STARTED,
            $runId,
            $workflowId,
            $context->getState()
        );

        try {
            $workflow = $this->workflowRegistry->get($workflowId);
            $steps = 0;

            while (!$context->isComplete()) {
                $state = $context->getState();
                $contextClass = $context::class;
                $stepStart = microtime(true);

                ++$steps;
                if ($steps > $this->maxSteps) {
                    throw WorkflowException::forMaxStepsExceeded($workflowId, $this->maxSteps, $state);
                }

                $this->log("State {$state}...");
                $this->emit(
                    ExecutionEvent::TYPE_STEP_STARTED,
                    $runId,
                    $workflowId,
                    $state
                );

                try {
                    $handler = $this->registry->get($state);
                    $result = $handler->handle($context);

                    $nextContext = $result->context;

                    if ($nextContext::class !== $contextClass) {
                        throw WorkflowException::forContextTypeMismatch(
                            $contextClass,
                            $nextContext::class,
                            $state
                        );
                    }

                    $nextState = $this->resolver->resolve($nextContext, $result->signals, $workflow);

                    $this->emit(
                        ExecutionEvent::TYPE_TRANSITION_TAKEN,
                        $runId,
                        $workflowId,
                        $state,
                        [
                            'from' => $state,
                            'to' => $nextState,
                            'signals' => $result->signals,
                        ]
                    );

                    $context = $nextContext->withState($nextState);

                    if ($context::class !== $contextClass) {
                        throw WorkflowException::forContextTypeMismatch(
                            $contextClass,
                            $context::class,
                            $state
                        );
                    }
                } catch (Throwable $exception) {
                    $this->emit(
                        ExecutionEvent::TYPE_STEP_FAILED,
                        $runId,
                        $workflowId,
                        $state,
                        [
                            'error' => $this->formatError($exception),
                        ]
                    );

                    throw $exception;
                }

                $stepDuration = round((microtime(true) - $stepStart) * 1000.0, 2);
                $this->emit(
                    ExecutionEvent::TYPE_STEP_FINISHED,
                    $runId,
                    $workflowId,
                    $state,
                    [
                        'duration_ms' => $stepDuration,
                        'next_state' => $context->getState(),
                    ]
                );

                $this->log("State $state finished in {$stepDuration}ms");
            }

            $workflowDuration = round((microtime(true) - $workflowStart) * 1000.0, 2);
            $this->emit(
                ExecutionEvent::TYPE_RUN_FINISHED,
                $runId,
                $workflowId,
                $context->getState(),
                [
                    'duration_ms' => $workflowDuration,
                ]
            );

            $this->log("Workflow finished in {$workflowDuration}ms");

            return $context;
        } catch (Throwable $exception) {
            $this->emit(
                ExecutionEvent::TYPE_RUN_FAILED,
                $runId,
                $workflowId,
                $context->getState(),
                [
                    'error' => $this->formatError($exception),
                ]
            );

            throw $exception;
        }
    }

    /**
     * Logs workflow messages.
     *
     * @param string $message
     */
    private function log(string $message): void
    {
        $this->logger?->info("[workflow] $message");
    }

    /**
     * @return array{type: string, message: string}
     */
    private function formatError(Throwable $exception): array
    {
        return [
            'type' => $exception::class,
            'message' => $exception->getMessage(),
        ];
    }

    private function createRunId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function emit(
        string $type,
        string $runId,
        string $workflowId,
        string $state,
        array $meta = []
    ): void {
        $this->observer->emit(new ExecutionEvent(
            $type,
            $runId,
            $workflowId,
            $state,
            $this->nowMs(),
            $meta
        ));
    }

    private function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000.0);
    }
}
