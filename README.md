[![PHP](https://img.shields.io/badge/php-8.2+-blue)](https://www.php.net/)
[![Build](https://github.com/judus/argon-workflows/actions/workflows/php.yml/badge.svg)](https://github.com/judus/argon-workflows/actions)
[![codecov](https://codecov.io/gh/judus/argon-workflows/branch/main/graph/badge.svg)](https://codecov.io/gh/judus/argon-workflows)
[![Psalm Level](https://shepherd.dev/github/judus/argon-workflows/coverage.svg)](https://shepherd.dev/github/judus/argon-workflows)
[![Latest Version](https://img.shields.io/packagist/v/maduser/argon-workflows.svg)](https://packagist.org/packages/maduser/argon-workflows)
[![Downloads](https://img.shields.io/packagist/dt/maduser/argon-workflows.svg)](https://packagist.org/packages/maduser/argon-workflows)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

# Argon Workflows

A minimal workflow runner. Define state handlers and wire them together into workflows. Transitions can be static or triggered by signals emitted from handlers.

## Basic Concept

Define a `ContextInterface` that represents the state of your system and passes through your workflow.

Each state has a `StateHandlerInterface`, which processes the context and returns a `HandlerResult`, possibly with transition signals.

The `WorkflowRunner` coordinates:

* Calling handlers in a loop
* Transitioning based on signal or static mapping
* Emitting execution events for logging, telemetry, queue correlation, or UI updates

The runner has a configurable max-step guard. This prevents broken workflow definitions from looping forever.

## Example

```php
$context = new MyContext(state: 'start');

$registry = new StateHandlerRegistry();
$registry->register('start', new StartHandler());
$registry->register('next', new NextHandler());

$workflow = new WorkflowDefinition(
    staticTransitions: ['start' => 'next'],
    signalTransitions: ['done' => 'final']
);

$workflows = new WorkflowRegistry();
$workflows->add('default', $workflow);

$runner = new WorkflowRunner(
    $registry,
    new TransitionResolver(),
    $workflows,
    maxSteps: 1000
);

$finalContext = $runner->run($context);
```

## Execution Events

The runner can emit execution events for run, step, and transition lifecycle changes.
Provide your own `runId` when you want to correlate runs with external systems.

```php
$observer = new MyExecutionObserver();
$runner = new WorkflowRunner(
    $registry,
    new TransitionResolver(),
    $workflows,
    null,
    $observer
);

$finalContext = $runner->run($context, 'default', runId: 'job-123');
```

## Transition Behavior

If a handler emits a signal through `HandlerResult::$signals`, that takes precedence over the static transition.

```php
return new HandlerResult(
    context: $context,
    signals: ['done' => true]
);
```

If no signals match, the runner falls back to the static transition based on the current state.

Signal transitions are global in the current model:

* The first truthy matching signal wins.
* Signal transitions are not scoped to the current state.
* Use distinct signal names when the same signal would mean different targets in different states.

## Graph Export

You can export a workflow definition as a graph to visualize it in a UI.

```php
$graph = $workflow->toGraph();
```

Signal transitions use `from = '*'` in the exported graph because they are global.

## Integration Example

In a real project:

```php
final class Agent
{
    public function __construct(
        private WorkflowRunner $workflowRunner,
    ) {}

    public function run(string $agentId, string $input): LLMResponse
    {
        $context = new AgentContext(agentId: $agentId, input: $input);
        $result = $this->workflowRunner->run($context, 'default', runId: $context->agentId);

        return $result->getFinalResponse()
            ?? throw new RuntimeException('Agent completed but returned no response.');
    }
}
```

## Interface Definitions

Implement:

* `ContextInterface`: your workflow data object. The runner expects `withState()` to return a context of the same concrete type.
* `StateHandlerInterface`: logic per step/state.

## Boundaries

This package does not provide persistence, queue execution, scheduling, retries, locking, or dependency injection integration.

Observers fail the workflow if they throw. Wrap observers yourself if telemetry failures should be swallowed.

Handlers are registered directly in `StateHandlerRegistry`. Container-backed handler resolution belongs in an integration layer, not in the workflow runner core.

## License

MIT License
