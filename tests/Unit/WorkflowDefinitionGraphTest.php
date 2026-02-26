<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Unit;

use Maduser\Argon\Workflows\WorkflowDefinition;
use PHPUnit\Framework\TestCase;

final class WorkflowDefinitionGraphTest extends TestCase
{
    public function testBuildsGraphWithStaticAndSignalEdges(): void
    {
        $definition = new WorkflowDefinition(
            ['start' => 'next', 'next' => 'done'],
            ['doneSignal' => 'done']
        );

        $graph = $definition->toGraph();

        $this->assertArrayHasKey('nodes', $graph);
        $this->assertArrayHasKey('edges', $graph);

        $this->assertSame('start', $graph['nodes']['start']['id']);
        $this->assertSame('done', $graph['nodes']['done']['id']);

        $staticEdge = $graph['edges']['start->next'];
        $this->assertSame('static', $staticEdge['type']);
        $this->assertSame('start', $staticEdge['from']);
        $this->assertSame('next', $staticEdge['to']);
        $this->assertNull($staticEdge['signal']);

        $signalEdge = $graph['edges']['*~doneSignal->done'];
        $this->assertSame('signal', $signalEdge['type']);
        $this->assertSame('*', $signalEdge['from']);
        $this->assertSame('done', $signalEdge['to']);
        $this->assertSame('doneSignal', $signalEdge['signal']);
    }
}
