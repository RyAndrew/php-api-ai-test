<?php

return [
    'global' => [
        // Middleware that runs on every request
    ],
    'auth' => [
        App\Middleware\AuthMiddleware::class
    ],
    'api' => [
        // API-specific middleware
    ]
];