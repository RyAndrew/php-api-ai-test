<?php

namespace App\Validators;

use App\Exceptions\ValidationException;

class InputValidator 
{
    public static function validate($request, $schema = []) 
    {
        $validated = [];
        
        if (isset($schema['query'])) {
            $validated['query'] = self::validateSection($request['query'] ?? [], $schema['query'], 'query');
        }
        
        if (isset($schema['body'])) {
            $body = $request['body'] ?? [];
            
            if (empty($body) && self::hasRequiredFields($schema['body'])) {
                throw new ValidationException('Request body is required but empty');
            }
            
            $validated['body'] = self::validateSection($body, $schema['body'], 'body');
        }
        
        return $validated;
    }
    
    private static function validateSection($data, $rules, $section) 
    {
        $validated = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $ruleSet = explode('|', $rule);
            
            $isRequired = in_array('required', $ruleSet);
            $isOptional = in_array('optional', $ruleSet);
            
            if ($isRequired && ($value === null || $value === '')) {
                throw new ValidationException("{$field} is required", $field);
            }
            
            if ($isOptional && ($value === null || $value === '')) {
                continue;
            }
            
            if ($value !== null && $value !== '') {
                $validated[$field] = self::validateField($field, $value, $ruleSet);
            }
        }
        
        return $validated;
    }
    
    private static function validateField($field, $value, $rules) 
    {
        foreach ($rules as $rule) {
            $ruleParts = explode(':', $rule);
            $ruleName = $ruleParts[0];
            $ruleParam = $ruleParts[1] ?? null;
            
            switch ($ruleName) {
                case 'string':
                    if (!is_string($value)) {
                        throw new ValidationException("{$field} must be a string", $field);
                    }
                    break;
                    
                case 'integer':
                    if (!is_numeric($value)) {
                        throw new ValidationException("{$field} must be an integer", $field);
                    }
                    $value = (int) $value;
                    break;
                    
                case 'max':
                    if ($ruleParam && strlen($value) > (int)$ruleParam) {
                        throw new ValidationException("{$field} must be no more than {$ruleParam} characters", $field);
                    }
                    break;
                    
                case 'min':
                    if ($ruleParam && is_numeric($value) && $value < (int)$ruleParam) {
                        throw new ValidationException("{$field} must be at least {$ruleParam}", $field);
                    }
                    break;
                    
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        throw new ValidationException("{$field} must be a valid email address", $field);
                    }
                    break;
                    
                case 'url':
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        throw new ValidationException("{$field} must be a valid URL", $field);
                    }
                    break;
                    
                case 'boolean':
                    if (!is_bool($value) && !in_array(strtolower($value), ['true', 'false', '1', '0'])) {
                        throw new ValidationException("{$field} must be a boolean value", $field);
                    }
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                    
                case 'in':
                    if ($ruleParam) {
                        $allowedValues = explode(',', $ruleParam);
                        if (!in_array($value, $allowedValues)) {
                            throw new ValidationException("{$field} must be one of: " . implode(', ', $allowedValues), $field);
                        }
                    }
                    break;
                    
                case 'required':
                case 'optional':
                    break;
                    
                default:
                    break;
            }
        }
        
        return $value;
    }
    
    private static function hasRequiredFields($schema) 
    {
        foreach ($schema as $field => $rules) {
            if (strpos($rules, 'required') !== false) {
                return true;
            }
        }
        return false;
    }
    
    public static function validatePagination($query) 
    {
        return self::validate(['query' => $query], [
            'query' => [
                'limit' => 'optional|integer|min:1|max:100',
                'offset' => 'optional|integer|min:0'
            ]
        ]);
    }
}