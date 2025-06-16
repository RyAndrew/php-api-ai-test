<?php

namespace App\Controllers;

use App\Http\ApiRequest;

class HealthController 
{
    public function check(ApiRequest $request) 
    {
        $config = require __DIR__ . '/../../config/app.php';
        
        return [
            'success' => true,
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $config['version'],
            'environment' => $config['environment']
        ];
    }
}