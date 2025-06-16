<?php

namespace App\Http;

class ApiRequest
{
    private $method;
    private $uri;
    private $route;
    private $controller;
    private $action;
    private $params;
    private $query;
    private $body;
    private $bodyParsed = false;
    private $headers;
    private $user;
    private $metadata;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = strtok($_SERVER['REQUEST_URI'], '?');
        $this->query = $_GET;
        $this->headers = getallheaders();
        $this->body = json_decode(file_get_contents('php://input'), true) ?: [];
        $this->metadata = [];
        $this->user = null;
    }

    // Getters
    public function getMethod(): string { return $this->method; }
    public function getUri(): string { return $this->uri; }
    public function getRoute(): string { return $this->route ?? ''; }
    public function getController(): string { return $this->controller ?? ''; }
    public function getAction(): string { return $this->action ?? ''; }
    public function getParams(): array { return $this->params ?? []; }
    public function getQuery(): array { return $this->query; }
    public function getBody(): array 
    {
        if (!$this->bodyParsed) {
            $rawInput = file_get_contents('php://input');
            $this->body = $rawInput ? (json_decode($rawInput, true) ?: []) : [];
            $this->bodyParsed = true;
        }
        return $this->body;
    }
    public function getHeaders(): array { return $this->headers; }
    public function getUser(): ?array { return $this->user; }
    public function getMetadata(string $key = null) { 
        return $key ? ($this->metadata[$key] ?? null) : $this->metadata; 
    }
    
    public function isAuthenticated(): bool { return $this->user !== null; }

    // Setters for routing metadata
    public function setRoute(string $route): self { $this->route = $route; return $this; }
    public function setController(string $controller): self { $this->controller = $controller; return $this; }
    public function setAction(string $action): self { $this->action = $action; return $this; }
    public function setParams(array $params): self { $this->params = $params; return $this; }
    public function setUser(?array $user): self { $this->user = $user; return $this; }
    public function setMetadata(string $key, $value): self { $this->metadata[$key] = $value; return $this; }

    // Legacy array access for backward compatibility
    public function toArray(): array 
    {
        return [
            'method' => $this->method,
            'uri' => $this->uri,
            'route' => $this->route,
            'controller' => $this->controller,
            'action' => $this->action,
            'params' => $this->params,
            'query' => $this->query,
            'body' => $this->body,
            'headers' => $this->headers,
            'user' => $this->user,
            'authenticated' => $this->isAuthenticated(),
            'metadata' => $this->metadata
        ];
    }
}
