<?php

declare(strict_types=1);

namespace Alix\Workflows\Contracts;

interface ContextInterface
{
    public function getState(): string;
    public function withState(string $state): static;
    public function isComplete(): bool;
}