[![PHP](https://img.shields.io/badge/php-8.2+-blue)](https://www.php.net/)
[![Build](https://github.com/judus/argon-workflows/actions/workflows/php.yml/badge.svg)](https://github.com/judus/argon-workflows/actions)
[![codecov](https://codecov.io/gh/judus/argon-workflows/branch/master/graph/badge.svg)](https://codecov.io/gh/judus/argon-workflows)
[![Psalm Level](https://shepherd.dev/github/judus/argon-workflows/coverage.svg)](https://shepherd.dev/github/judus/argon-workflows)
[![Code Style](https://img.shields.io/badge/code%20style-PSR--12-brightgreen.svg)](https://www.php-fig.org/psr/psr-12/)
[![Latest Version](https://img.shields.io/packagist/v/maduser/argon-workflows.svg)](https://packagist.org/packages/maduser/argon-workflows)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

# Argon Workflows

A minimal workflow runner. Define state handlers and wire them together into workflows.
**Transitions can be static or triggered by signals emitted from handlers.**

## Basic Concept

Define a `ContextInterface` that represents the state of your system and passes through your workflow.

Each state has a `StateHandlerInterface`, which processes the context and returns a `HandlerResult`, possibly with transition **signals**.

The `WorkflowRunner` coordinates:

* Calling handlers in a loop
* Transitioning based on signal or static mapping

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

$runner = new WorkflowRunner($registry, new TransitionResolver(), $workflows);
$finalContext = $runner->run($context);
```

## Transition Behavior

If a handler "emits" a signal (via `HandlerResult::$signals`), that takes precedence over the static transition.

```php
return new HandlerResult(
    context: $context,
    signals: ['done' => true] // will override static transition
);
```

If no signals match, the runner falls back to the static transition based on current state.

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
        $result = $this->workflowRunner->run($context, $context->agentId);

        return $result->getFinalResponse()
            ?? throw new RuntimeException('Agent completed but returned no response.');
    }
}
```

## Interface Definitions

Implement:

* `ContextInterface`: your mutable data object, immutable in use.
* `StateHandlerInterface`: logic per step/state.

## TODO

* Make it container aware (optional)


## License

MIT License

