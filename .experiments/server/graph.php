<?php

declare(strict_types=1);

use Maduser\Argon\Workflows\WorkflowDefinition;

require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

$workflow = new WorkflowDefinition(
    [
        'fetch_email' => 'ask_llm',
        'ask_llm' => 'forward_attachments',
        'forward_attachments' => '__end',
    ],
    []
);

echo json_encode($workflow->toGraph(), JSON_PRETTY_PRINT);
