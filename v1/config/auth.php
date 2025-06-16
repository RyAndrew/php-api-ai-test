<?php

return [
    'okta_domain' => $_ENV['OKTA_DOMAIN'] ?? 'your-domain.okta.com',
    'okta_audience' => $_ENV['OKTA_AUDIENCE'] ?? 'api://default',
    'okta_issuer' => $_ENV['OKTA_ISSUER'] ?? 'https://your-domain.okta.com/oauth2/default',
    'jwks_cache_ttl' => 1800, // 30 minutes
    'public_routes' => [
        'health' => ['check']
    ]
];

