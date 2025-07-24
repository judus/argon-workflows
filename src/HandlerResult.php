<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

use Maduser\Argon\Workflows\Contracts\ContextInterface;

/**
 * Class HandlerResult
 * Encapsulates the result of handling a state.
 */
final readonly class HandlerResult
{
    /**
     * @param ContextInterface $context  The context after handling.
     * @param array<string, mixed> $signals  Signals emitted during handling.
     */
    public function __construct(
        public ContextInterface $context,
        public array $signals = [],
    ) {
    }
}
