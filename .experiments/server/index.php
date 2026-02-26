<?php

declare(strict_types=1);

use Maduser\Argon\Workflows\Contracts\ContextInterface;
use Maduser\Argon\Workflows\Contracts\ExecutionObserverInterface;
use Maduser\Argon\Workflows\Contracts\StateHandlerInterface;
use Maduser\Argon\Workflows\ExecutionEvent;
use Maduser\Argon\Workflows\HandlerResult;
use Maduser\Argon\Workflows\StateHandlerRegistry;
use Maduser\Argon\Workflows\TransitionResolver;
use Maduser\Argon\Workflows\WorkflowDefinition;
use Maduser\Argon\Workflows\WorkflowRegistry;
use Maduser\Argon\Workflows\WorkflowRunner;

require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$runId = $_GET['runId'] ?? 'demo-run';
$workflowId = 'email-pipeline';

$registry = new StateHandlerRegistry();
$registry->register('fetch_email', new class implements StateHandlerInterface {
    public function handle(ContextInterface $context): HandlerResult
    {
        usleep(300_000);
        return new HandlerResult($context);
    }
});
$registry->register('ask_llm', new class implements StateHandlerInterface {
    public function handle(ContextInterface $context): HandlerResult
    {
        usleep(500_000);
        return new HandlerResult($context);
    }
});
$registry->register('forward_attachments', new class implements StateHandlerInterface {
    public function handle(ContextInterface $context): HandlerResult
    {
        usleep(200_000);
        return new HandlerResult($context);
    }
});

$workflow = new WorkflowDefinition(
    [
        'fetch_email' => 'ask_llm',
        'ask_llm' => 'forward_attachments',
        'forward_attachments' => '__end',
    ],
    []
);
$workflows = new WorkflowRegistry();
$workflows->add($workflowId, $workflow);

$observer = new class implements ExecutionObserverInterface {
    public function emit(ExecutionEvent $event): void
    {
        echo "event: workflow\n";
        echo "data: " . json_encode([
            'type' => $event->type,
            'runId' => $event->runId,
            'workflowId' => $event->workflowId,
            'state' => $event->state,
            'timestampMs' => $event->timestampMs,
            'meta' => $event->meta,
        ]) . "\n\n";

        if (function_exists('ob_flush')) {
            ob_flush();
        }
        flush();
    }
};

$runner = new WorkflowRunner(
    $registry,
    new TransitionResolver(),
    $workflows,
    null,
    $observer
);

$context = new class('fetch_email') implements ContextInterface {
    public function __construct(private string $state)
    {
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isComplete(): bool
    {
        return $this->state === '__end';
    }

    public function withState(string $state): ContextInterface
    {
        return new self($state);
    }
};

$runner->run($context, $workflowId, $runId);
