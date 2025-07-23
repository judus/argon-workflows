<?php

declare(strict_types=1);

namespace Alix\Workflows;

use Alix\Workflows\Contracts\ContextInterface;

final readonly class HandlerResult
{
    /**
     * @param ContextInterface $context
     * @param array<string, mixed> $signals
     */
    public function __construct(
        public ContextInterface $context,
        public array $signals = [],
    ) {}
}
