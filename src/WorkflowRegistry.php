<?php

declare(strict_types=1);

namespace Alix\Workflows;

use RuntimeException;

final class WorkflowRegistry
{
    /** @var array<string, WorkflowDefinition> */
    private array $workflows = [];

    public function add(string $name, WorkflowDefinition $workflow): void
    {
        if (isset($this->workflows[$name])) {
            throw new RuntimeException("Workflow '$name' already registered.");
        }

        $this->workflows[$name] = $workflow;
    }

    public function get(string $name = 'default'): WorkflowDefinition
    {
        return $this->workflows[$name]
            ?? throw new RuntimeException("No workflow registered for '$name'");
    }

    public function has(string $name): bool
    {
        return isset($this->workflows[$name]);
    }
}
