<?php

return [
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'version' => '1.0.0',
    'timezone' => 'UTC',
    
    // Security settings
    'max_controller_length' => 30,
    'max_action_length' => 30,
    'allowed_controller_pattern' => '/^[a-zA-Z][a-zA-Z0-9_]*$/',
    'allowed_action_pattern' => '/^[a-zA-Z][a-zA-Z0-9_]*$/',
];

