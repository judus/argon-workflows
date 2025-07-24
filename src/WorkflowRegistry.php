<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

use Maduser\Argon\Workflows\Exceptions\WorkflowException;
use RuntimeException;

/**
 * Class WorkflowRegistry
 * Manages the set of available workflows.
 */
final class WorkflowRegistry
{
    /** @var array<string, WorkflowDefinition> */
    private array $workflows = [];

    /**
     * Adds a workflow to the registry.
     *
     * @param string $name
     * @param WorkflowDefinition $workflow
     * @throws RuntimeException When workflow with the same name already exists.
     */
    public function add(string $name, WorkflowDefinition $workflow): void
    {
        if (isset($this->workflows[$name])) {
            throw WorkflowException::forWorkflowAlreadyRegistered($name);
        }

        $this->workflows[$name] = $workflow;
    }

    /**
     * Retrieves a workflow by name.
     *
     * @param string $name
     * @return WorkflowDefinition
     * @throws RuntimeException When no workflow is registered under the specified name.
     */
    public function get(string $name = 'default'): WorkflowDefinition
    {
        if (!isset($this->workflows[$name])) {
            throw WorkflowException::forWorkflowNotRegistered($name);
        }

        return $this->workflows[$name];
    }

    /**
     * Checks if a workflow with the given name exists.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->workflows[$name]);
    }
}
