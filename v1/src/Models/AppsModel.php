<?php

namespace App\Models;

use PDO;
use PDOException;
use Exception;

class AppsModel 
{
    private $dbPath;
    private $pdo;
    
    public function __construct() 
    {
        $this->dbPath = __DIR__ . '/../../data/apps.db';
        $this->initDatabase();
    }
    
    private function initDatabase() 
    {
        $dataDir = dirname($this->dbPath);
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        try {
            $this->pdo = new PDO('sqlite:' . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $createTableSQL = "
                CREATE TABLE IF NOT EXISTS apps (
                    appId TEXT PRIMARY KEY,
                    dateCreated TEXT NOT NULL,
                    dateChanged TEXT NOT NULL,
                    appName TEXT NOT NULL,
                    appDescription TEXT
                )
            ";
            
            $this->pdo->exec($createTableSQL);
            
        } catch (PDOException $e) {
            throw new Exception('Database initialization failed: ' . $e->getMessage());
        }
    }
    
    public function generateAppId() 
    {
        // Generate 128 bits (16 bytes) of cryptographically secure random data
        $randomBytes = random_bytes(16);
        
        // Convert to URL-safe base64
        $base64 = base64_encode($randomBytes);
        return rtrim(strtr($base64, '+/', '-_'), '=');
    }
    
    public function create($appName, $appDescription = null) 
    {
        try {
            $appId = $this->generateAppId();
            $now = date('Y-m-d H:i:s');
            
            $stmt = $this->pdo->prepare("
                INSERT INTO apps (appId, dateCreated, dateChanged, appName, appDescription)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$appId, $now, $now, $appName, $appDescription]);
            
            return [
                'appId' => $appId,
                'dateCreated' => $now,
                'dateChanged' => $now,
                'appName' => $appName,
                'appDescription' => $appDescription
            ];
            
        } catch (PDOException $e) {
            throw new Exception('Failed to create app: ' . $e->getMessage());
        }
    }
    
    public function getById($appId) 
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM apps WHERE appId = ?");
            $stmt->execute([$appId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            throw new Exception('Failed to fetch app: ' . $e->getMessage());
        }
    }
    
    public function getAll($limit = 50, $offset = 0) 
    {
        try {
            if ($limit > 100) $limit = 100;
            if ($limit < 1) $limit = 50;
            if ($offset < 0) $offset = 0;
            
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM apps");
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM apps 
                ORDER BY dateCreated DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'apps' => $apps,
                'pagination' => [
                    'total' => intval($total),
                    'limit' => $limit,
                    'offset' => $offset,
                    'hasMore' => ($offset + $limit) < $total
                ]
            ];
            
        } catch (PDOException $e) {
            throw new Exception('Failed to fetch apps: ' . $e->getMessage());
        }
    }
    
    public function update($appId, $updates) 
    {
        try {
            if (!$this->exists($appId)) {
                return false;
            }
            
            $updateFields = [];
            $updateValues = [];
            
            if (isset($updates['appName']) && !empty($updates['appName'])) {
                $updateFields[] = 'appName = ?';
                $updateValues[] = $updates['appName'];
            }
            
            if (isset($updates['appDescription'])) {
                $updateFields[] = 'appDescription = ?';
                $updateValues[] = $updates['appDescription'];
            }
            
            if (empty($updateFields)) {
                throw new Exception('No valid fields to update');
            }
            
            $updateFields[] = 'dateChanged = ?';
            $updateValues[] = date('Y-m-d H:i:s');
            $updateValues[] = $appId;
            
            $updateSQL = "UPDATE apps SET " . implode(', ', $updateFields) . " WHERE appId = ?";
            $stmt = $this->pdo->prepare($updateSQL);
            $stmt->execute($updateValues);
            
            return $this->getById($appId);
            
        } catch (PDOException $e) {
            throw new Exception('Failed to update app: ' . $e->getMessage());
        }
    }
    
    public function delete($appId) 
    {
        try {
            if (!$this->exists($appId)) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM apps WHERE appId = ?");
            $stmt->execute([$appId]);
            
            return true;
            
        } catch (PDOException $e) {
            throw new Exception('Failed to delete app: ' . $e->getMessage());
        }
    }
    
    public function exists($appId) 
    {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM apps WHERE appId = ?");
            $stmt->execute([$appId]);
            return $stmt->fetch() !== false;
            
        } catch (PDOException $e) {
            throw new Exception('Failed to check app existence: ' . $e->getMessage());
        }
    }
}