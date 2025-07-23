<?php

declare(strict_types=1);

namespace Alix\Workflows;

use Alix\Workflows\Contracts\ContextInterface;
use Psr\Log\LoggerInterface;

final readonly class WorkflowRunner
{
    public function __construct(
        private StateHandlerRegistry $registry,
        private TransitionResolver $resolver,
        private WorkflowRegistry $workflowRegistry,
        private ?LoggerInterface $logger = null,
    ) {}

    public function run(ContextInterface $context, string $workflowId = 'default'): ContextInterface
    {
        $workflowStart = microtime(true);
        $this->log("Running workflow: " . $workflowId);

        $workflow = $this->workflowRegistry->get($workflowId);

        while (!$context->isComplete()) {
            $state = $context->getState();
            $stepStart = microtime(true);

            $this->log("State $state...");

            $handler = $this->registry->get($state);
            $result = $handler->handle($context);

            $context = $result->context;
            $context = $context->withState(
                $this->resolver->resolve($context, $result->signals, $workflow)
            );

            $stepDuration = round((microtime(true) - $stepStart) * 1000, 2);
            $this->log("State $state finished in {$stepDuration}ms");
        }

        $workflowDuration = round((microtime(true) - $workflowStart) * 1000, 2);
        $this->log("Workflow finished in {$workflowDuration}ms");

        return $context;
    }

    private function log(string $message): void
    {
        $this->logger?->info("[workflow] $message");
    }
}
