<?php

namespace App\Cache;

use Psr\Cache\CacheItemInterface;

class SimpleCacheItem implements CacheItemInterface 
{
    private string $key;
    private string $cacheDir;
    private bool $hit = false;
    private mixed $value = null;
    
    public function __construct(string $key, string $cacheDir) 
    {
        $this->key = $key;
        $this->cacheDir = $cacheDir;
        $this->loadFromCache();
    }
    
    private function loadFromCache(): void 
    {
        $file = $this->cacheDir . '/' . md5($this->key) . '.cache';
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            if ($data && $data['expires'] > time()) {
                $this->hit = true;
                $this->value = $data['value'];
            }
        }
    }
    
    public function getKey(): string 
    { 
        return $this->key; 
    }
    
    public function get(): mixed 
    { 
        return $this->value; 
    }
    
    public function isHit(): bool 
    { 
        return $this->hit; 
    }
    
    public function set(mixed $value): static 
    { 
        $this->value = $value; 
        return $this; 
    }
    
    public function expiresAt(?\DateTimeInterface $expiration): static 
    { 
        return $this; 
    }
    
    public function expiresAfter($time): static 
    { 
        return $this; 
    }
    
    public function save($pool): void 
    {
        $file = $this->cacheDir . '/' . md5($this->key) . '.cache';
        $data = [
            'value' => $this->value,
            'expires' => time() + 1800 // 30 minutes
        ];
        file_put_contents($file, serialize($data));
    }
}