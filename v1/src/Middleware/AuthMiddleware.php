<?php

namespace App\Middleware;

use App\Http\ApiRequest;
use App\Exceptions\AuthException;
use Firebase\JWT\CachedKeySet;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use App\Cache\SimpleCache;

class AuthMiddleware 
{
    private $config;
    private $keySet;

    public function __construct() 
    {
        $this->config = require __DIR__ . '/../../config/auth.php';
        $this->initializeKeySet();
    }

    private function initializeKeySet() 
    {
        $jwksUri = 'https://' . $this->config['okta_domain'] . '/oauth2/default/v1/keys';
        $httpClient = new Client();
        $httpFactory = new HttpFactory();
        $cacheItemPool = new SimpleCache();

        $this->keySet = new CachedKeySet(
            $jwksUri,
            $httpClient,
            $httpFactory,
            $cacheItemPool,
            $this->config['jwks_cache_ttl'],
            true
        );
    }

    public function handle(ApiRequest $request): ApiRequest 
    {
        // Check if route requires authentication
        if ($this->isPublicRoute($request->getController(), $request->getAction())) {
            return $request;
        }

        $authHeader = $request->getHeaders()['Authorization'] ?? 
                     $request->getHeaders()['authorization'] ?? '';

        if (!$authHeader) {
            throw new AuthException('Authorization header missing');
        }

        if (!preg_match('/^Bearer\s+(.*)$/i', $authHeader, $matches)) {
            throw new AuthException('Invalid authorization header format');
        }

        $token = $matches[1];
        $user = $this->validateJWT($token);
        
        return $request->setUser($user)
                      ->setMetadata('auth_method', 'jwt')
                      ->setMetadata('token_validated_at', time());
    }

    private function isPublicRoute(string $controller, string $action): bool 
    {
        $publicRoutes = $this->config['public_routes'];
        return isset($publicRoutes[$controller]) && 
               in_array($action, $publicRoutes[$controller]);
    }

    private function validateJWT(string $token): array 
    {
        try {
            $decoded = JWT::decode($token, $this->keySet, ['RS256']);
            $payload = (array) $decoded;

            if (!isset($payload['iss']) || $payload['iss'] !== $this->config['okta_issuer']) {
                throw new UnexpectedValueException('Invalid issuer');
            }

            if (!isset($payload['aud']) || $payload['aud'] !== $this->config['okta_audience']) {
                throw new UnexpectedValueException('Invalid audience');
            }

            return $payload;

        } catch (InvalidArgumentException $e) {
            throw new AuthException("Could not get JWKS to validate token");
        } catch (DomainException $e) {
            throw new AuthException("Bad token - treachery suspected (Domain)");
        } catch (SignatureInvalidException $e) {
            throw new AuthException("Bad token - signature invalid");
        } catch (BeforeValidException $e) {
            throw new AuthException("Token not yet valid");
        } catch (ExpiredException $e) {
            throw new AuthException("Token expired");
        } catch (UnexpectedValueException $e) {
            throw new AuthException("Bad token - treachery suspected: " . $e->getMessage());
        }
    }
}
