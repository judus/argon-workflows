<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Unit\Fixtures;

use Maduser\Argon\Workflows\Contracts\ContextInterface;

final class NativeContext implements ContextInterface
{
    public function __construct(
        private string $state,
        private bool $complete = false
    ) {
    }

    #[\Override]
    public function getState(): string
    {
        return $this->state;
    }

    #[\Override]
    public function isComplete(): bool
    {
        return $this->complete;
    }

    #[\Override]
    public function withState(string $state): ContextInterface
    {
        return new self($state, $state === 'done');
    }
}
