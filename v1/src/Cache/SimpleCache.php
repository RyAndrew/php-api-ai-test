<?php

namespace App\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

class SimpleCache implements CacheItemPoolInterface 
{
    private $cacheDir;
    
    public function __construct() 
    {
        $this->cacheDir = __DIR__ . '/../../cache';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function getItem(string $key): CacheItemInterface 
    {
        return new SimpleCacheItem($key, $this->cacheDir);
    }
    
    public function getItems(array $keys = []): iterable 
    { 
        return array_map([$this, 'getItem'], $keys); 
    }
    
    public function hasItem(string $key): bool 
    { 
        return $this->getItem($key)->isHit(); 
    }
    
    public function clear(): bool { return true; }
    
    public function deleteItem(string $key): bool { return true; }
    
    public function deleteItems(array $keys): bool { return true; }
    
    public function save(CacheItemInterface $item): bool 
    { 
        $item->save($this); 
        return true; 
    }
    
    public function saveDeferred(CacheItemInterface $item): bool 
    { 
        return $this->save($item); 
    }
    
    public function commit(): bool { return true; }
}