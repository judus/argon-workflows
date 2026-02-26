<?php

declare(strict_types=1);

use Maduser\Argon\Workflows\WorkflowDefinition;

require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

$workflow = new WorkflowDefinition(
    ['start' => 'process', 'process' => 'done'],
    []
);

echo json_encode($workflow->toGraph(), JSON_PRETTY_PRINT);
