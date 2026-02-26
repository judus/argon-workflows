<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

/**
 * Class WorkflowDefinition
 * Represents the definition of a workflow including transitions.
 */
final readonly class WorkflowDefinition
{
    /**
     * @param array<string, string> $staticTransitions
     * @param array<string, string> $signalTransitions
     */
    public function __construct(
        public array $staticTransitions,
        public array $signalTransitions,
    ) {
    }

    /**
     * @return array{
     *     nodes: array<string, array{id: string, label: string}>,
     *     edges: array<string, array{
     *         id: string,
     *         from: string,
     *         to: string,
     *         type: string,
     *         signal: string|null
     *     }>
     * }
     */
    public function toGraph(): array
    {
        $nodes = [];
        $edges = [];

        foreach ($this->staticTransitions as $from => $to) {
            $nodes[$from] = ['id' => $from, 'label' => $from];
            $nodes[$to] = ['id' => $to, 'label' => $to];

            $edgeId = $from . '->' . $to;
            $edges[$edgeId] = [
                'id' => $edgeId,
                'from' => $from,
                'to' => $to,
                'type' => 'static',
                'signal' => null,
            ];
        }

        foreach ($this->signalTransitions as $signal => $to) {
            $nodes[$to] = ['id' => $to, 'label' => $to];

            $edgeId = '*~' . $signal . '->' . $to;
            $edges[$edgeId] = [
                'id' => $edgeId,
                'from' => '*',
                'to' => $to,
                'type' => 'signal',
                'signal' => $signal,
            ];
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }
}
