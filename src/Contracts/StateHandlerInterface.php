<?php

declare(strict_types=1);

namespace Alix\Workflows\Contracts;

use Alix\Workflows\HandlerResult;

interface StateHandlerInterface {
    public function handle(ContextInterface $context): HandlerResult;
}
