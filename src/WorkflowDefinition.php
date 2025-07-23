<?php

declare(strict_types=1);

namespace Alix\Workflows;

use Alix\Workflows\Contracts\SignalInterface;
use Alix\Workflows\Contracts\StateInterface;

final readonly class WorkflowDefinition
{
    /**
     * @param array<string, string> $staticTransitions
     * @param array<string, string> $signalTransitions
     */
    public function __construct(
        public array $staticTransitions,
        public array $signalTransitions,
    ) {}
}
