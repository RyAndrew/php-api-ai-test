<?php

namespace App\Controllers;

use App\Http\ApiRequest;
use App\Models\AppsModel;
use App\Validators\InputValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class AppsController 
{
    private $model;
    
    public function __construct() 
    {
        $this->model = new AppsModel();
    }
    
    public function getCollection(ApiRequest $request) 
    {
        $validated = InputValidator::validatePagination($request->getQuery());
        
        $limit = $validated['query']['limit'] ?? 50;
        $offset = $validated['query']['offset'] ?? 0;
        
        $result = $this->model->getAll($limit, $offset);
        
        return [
            'success' => true,
            'data' => $result['apps'],
            'pagination' => $result['pagination']
        ];
    }
    
    public function get(ApiRequest $request) 
    {
        $appId = $request->getParams()[0] ?? null;
        
        if (!$appId) {
            throw new ValidationException('App ID is required');
        }
        
        $app = $this->model->getById($appId);
        
        if (!$app) {
            throw new NotFoundException('App not found');
        }
        
        return [
            'success' => true,
            'data' => $app
        ];
    }
    
    public function post(ApiRequest $request) 
    {
        $validated = InputValidator::validate($request->toArray(), [
            'body' => [
                'appName' => 'required|string|max:255',
                'appDescription' => 'optional|string|max:1000'
            ]
        ]);
        
        $app = $this->model->create(
            $validated['body']['appName'],
            $validated['body']['appDescription'] ?? null
        );
        
        http_response_code(201);
        return [
            'success' => true,
            'message' => 'App created successfully',
            'data' => $app
        ];
    }
    
    public function put(ApiRequest $request) 
    {
        $appId = $request->getParams()[0] ?? null;
        
        if (!$appId) {
            throw new ValidationException('App ID is required');
        }
        
        $validated = InputValidator::validate($request->toArray(), [
            'body' => [
                'appName' => 'optional|string|max:255',
                'appDescription' => 'optional|string|max:1000'
            ]
        ]);
        
        if (empty($validated['body'])) {
            throw new ValidationException('At least one field must be provided for update');
        }
        
        $updatedApp = $this->model->update($appId, $validated['body']);
        
        if (!$updatedApp) {
            throw new NotFoundException('App not found');
        }
        
        return [
            'success' => true,
            'message' => 'App updated successfully',
            'data' => $updatedApp
        ];
    }
    
    public function delete(ApiRequest $request) 
    {
        $appId = $request->getParams()[0] ?? null;
        
        if (!$appId) {
            throw new ValidationException('App ID is required');
        }
        
        $deleted = $this->model->delete($appId);
        
        if (!$deleted) {
            throw new NotFoundException('App not found');
        }
        
        http_response_code(204);
        return null;
    }
}