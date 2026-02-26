<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows;

use Maduser\Argon\Workflows\Contracts\ContextInterface;
use Maduser\Argon\Workflows\Exceptions\WorkflowException;

/**
 * Class HandlerResult
 * Encapsulates the result of handling a state.
 */
final readonly class HandlerResult
{
    /** @var array<string, bool> */
    public array $signals;

    public ContextInterface $context;

    /**
     * @param ContextInterface $context  The context after handling.
     * @param array<array-key, mixed> $signals  Signals emitted during handling.
     */
    public function __construct(ContextInterface $context, array $signals = [])
    {
        $validatedSignals = [];

        foreach ($signals as $signal => $value) {
            if (!is_string($signal)) {
                throw WorkflowException::forInvalidSignalKey(get_debug_type($signal));
            }

            if (!is_bool($value)) {
                throw WorkflowException::forInvalidSignalValue($signal, get_debug_type($value));
            }

            $validatedSignals[$signal] = $value;
        }

        $this->context = $context;
        $this->signals = $validatedSignals;
    }
}
