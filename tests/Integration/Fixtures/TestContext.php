<?php

declare(strict_types=1);

namespace Maduser\Argon\Workflows\Tests\Integration\Fixtures;

use Maduser\Argon\Workflows\Contracts\ContextInterface;

final class TestContext implements ContextInterface
{
    /**
     * @param array<string> $terminalStates
     */
    public function __construct(
        private string $state,
        private array $terminalStates
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
        return in_array($this->state, $this->terminalStates, true);
    }

    #[\Override]
    public function withState(string $state): ContextInterface
    {
        return new self($state, $this->terminalStates);
    }
}
