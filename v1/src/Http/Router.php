<?php

namespace App\Http;

use App\Http\ApiRequest;
use App\Middleware\AuthMiddleware;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class Router 
{
    private $config;
    private $middlewareConfig;
    private $request;

    public function __construct() 
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->middlewareConfig = require __DIR__ . '/../../config/middleware.php';
        
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    public function handleRequest(): void 
    {
        $this->request = new ApiRequest();
        $this->parseRoute();
        $this->runMiddleware();
        $response = $this->dispatchController();
        $this->sendResponse($response);
    }

    private function parseRoute(): void 
    {
        // Extract route from URI
        if (!preg_match('#^/v1/(.*)#', $this->request->getUri(), $matches)) {
            throw new ValidationException('Invalid route format');
        }

        $route = rtrim($matches[1], '/');
        $routeParts = explode('/', $route);

        if (empty($routeParts[0])) {
            throw new ValidationException('Controller required');
        }

        $controller = $routeParts[0];
        
        // Security validation with length limits
        if (strlen($controller) > $this->config['max_controller_length']) {
            throw new ValidationException('Controller name too long');
        }

        if (!preg_match($this->config['allowed_controller_pattern'], $controller)) {
            throw new ValidationException('Invalid controller name format');
        }

        // Determine action and params based on RESTful conventions
        if ($controller === 'apps') {
            $params = array_slice($routeParts, 1);
            $action = $this->getRESTfulAction($this->request->getMethod(), $params);
        } else {
            $action = $routeParts[1] ?? 'index';
            $params = array_slice($routeParts, 2);
            
            if (strlen($action) > $this->config['max_action_length']) {
                throw new ValidationException('Action name too long');
            }
            
            if (!preg_match($this->config['allowed_action_pattern'], $action)) {
                throw new ValidationException('Invalid action name format');
            }
        }

        $this->request->setRoute($route)
                     ->setController($controller)
                     ->setAction($action)
                     ->setParams($params);
    }

    private function getRESTfulAction(string $method, array $params): string 
    {
        switch ($method) {
            case 'GET':
                return empty($params) ? 'getCollection' : 'get';
            case 'POST':
                if (!empty($params)) {
                    throw new ValidationException('POST not allowed on specific resource');
                }
                return 'post';
            case 'PUT':
                if (empty($params)) {
                    throw new ValidationException('PUT requires resource ID');
                }
                return 'put';
            case 'DELETE':
                if (empty($params)) {
                    throw new ValidationException('DELETE requires resource ID');
                }
                return 'delete';
            default:
                throw new ValidationException('Method not allowed');
        }
    }

    private function runMiddleware(): void 
    {
        // Run auth middleware
        foreach ($this->middlewareConfig['auth'] as $middlewareClass) {
            $middleware = new $middlewareClass();
            $this->request = $middleware->handle($this->request);
        }
    }

    private function dispatchController() 
    {
        $controllerClass = 'App\\Controllers\\' . ucfirst($this->request->getController()) . 'Controller';
        
        if (!class_exists($controllerClass)) {
            throw new NotFoundException("Controller not found: {$this->request->getController()}");
        }

        $controller = new $controllerClass();
        $action = $this->request->getAction();

        if (!method_exists($controller, $action)) {
            throw new NotFoundException("Action '{$action}' not found in controller");
        }

        // Call controller with request object
        return $controller->$action($this->request);
    }

    private function sendResponse($response): void 
    {
        if ($response === null) {
            // 204 No Content (like DELETE)
            exit();
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
}