<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Unit;

use Maduser\Argon\Workflows\Exceptions\WorkflowException;
use Maduser\Argon\Workflows\WorkflowDefinition;
use Maduser\Argon\Workflows\WorkflowRegistry;
use PHPUnit\Framework\TestCase;

final class WorkflowRegistryTest extends TestCase
{
    public function testAddThrowsWhenWorkflowAlreadyRegistered(): void
    {
        $registry = new WorkflowRegistry();
        $definition = new WorkflowDefinition([], []);

        $registry->add('my-workflow', $definition);

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage("Workflow 'my-workflow' already registered.");

        $registry->add('my-workflow', $definition);
    }

    public function testGetThrowsWhenWorkflowNotRegistered(): void
    {
        $registry = new WorkflowRegistry();

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage("No workflow registered for 'missing'");

        $registry->get('missing');
    }

    public function testHasReturnsTrueIfWorkflowRegistered(): void
    {
        $registry = new WorkflowRegistry();
        $definition = new WorkflowDefinition([], []);
        $registry->add('foo', $definition);

        $this->assertTrue($registry->has('foo'));
    }

    public function testHasReturnsFalseIfWorkflowNotRegistered(): void
    {
        $registry = new WorkflowRegistry();

        $this->assertFalse($registry->has('ghost'));
    }

    public function testGetReturnsWorkflowIfExists(): void
    {
        $registry = new WorkflowRegistry();
        $definition = new WorkflowDefinition([], []);
        $registry->add('bar', $definition);

        $this->assertSame($definition, $registry->get('bar'));
    }
}
