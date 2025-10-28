<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

use Maduser\Argon\Workflows\Contracts\ContextInterface;
use Maduser\Argon\Workflows\Exceptions\WorkflowException;
use Psr\Log\LoggerInterface;

/**
 * Class WorkflowRunner
 * Executes workflows by running their handlers in sequence.
 */
final readonly class WorkflowRunner
{
    public function __construct(
        private StateHandlerRegistry $registry,
        private TransitionResolver $resolver,
        private WorkflowRegistry $workflowRegistry,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Runs the specified workflow and processes transitions.
     *
     * @param ContextInterface $context
     * @param string $workflowId
     * @return ContextInterface
     */
    public function run(ContextInterface $context, string $workflowId = 'default'): ContextInterface
    {
        $workflowStart = microtime(true);
        $this->log("Running workflow: " . $workflowId);

        $workflow = $this->workflowRegistry->get($workflowId);

        while (!$context->isComplete()) {
            $state = $context->getState();
            $contextClass = $context::class;
            $stepStart = microtime(true);

            $this->log("State {$state}...");

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
            $context = $nextContext->withState($nextState);

            if ($context::class !== $contextClass) {
                throw WorkflowException::forContextTypeMismatch(
                    $contextClass,
                    $context::class,
                    $state
                );
            }

            $stepDuration = round((microtime(true) - $stepStart) * 1000.0, 2);
            $this->log("State $state finished in {$stepDuration}ms");
        }

        $workflowDuration = round((microtime(true) - $workflowStart) * 1000.0, 2);
        $this->log("Workflow finished in {$workflowDuration}ms");

        return $context;
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
}
