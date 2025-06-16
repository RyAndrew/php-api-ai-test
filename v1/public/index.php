<?php

// error_reporting(E_ALL);
// ini_set('display_errors',1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Http\Router;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException; 
use App\Exceptions\AuthException;

// Global exception handler
set_exception_handler(function($e) {
    header('Content-Type: application/json');
    
    $response = ['error' => true, 'message' => $e->getMessage()];
    
    if ($e instanceof ValidationException) {
        http_response_code(400);
    } elseif ($e instanceof NotFoundException) {
        http_response_code(404);
    } elseif ($e instanceof AuthException) {
        http_response_code(403);
    } else {
        http_response_code(500);
        // Log the exception here!
        error_log("Unhandled exception: " . $e->getMessage());
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
});

// Now router can be simple
$router = new Router();
$router->handleRequest();