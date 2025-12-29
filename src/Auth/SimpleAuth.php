<?php

declare(strict_types=1);

namespace App\Auth;

use Psr\Http\Message\ServerRequestInterface;

final class SimpleAuth
{
    private static ?string $adminUsername = null;
    private static ?string $adminPassword = null;
    private static ?string $adminApiKey = null;

    public static function initialize(): void
    {
        self::$adminUsername = getenv('MONITORING_USERNAME') ?: null;
        self::$adminPassword = getenv('MONITORING_PASSWORD') ?: null;
        self::$adminApiKey = getenv('MONITORING_API_KEY') ?: null;
    }

    public static function isAuthenticated(ServerRequestInterface $request): bool
    {
        // If no auth is configured, allow access (development mode)
        if (self::$adminUsername === null && self::$adminApiKey === null) {
            return true;
        }

        // Check API Key (X-API-Key header or ?api_key query param)
        if (self::$adminApiKey !== null) {
            $apiKey = $request->getHeaderLine('X-API-Key') 
                   ?: $request->getQueryParams()['api_key'] ?? null;
            
            if ($apiKey === self::$adminApiKey) {
                return true;
            }
        }

        // Check Basic Auth
        if (self::$adminUsername !== null && self::$adminPassword !== null) {
            $authHeader = $request->getHeaderLine('Authorization');
            
            if (preg_match('/Basic\s+(.*)$/i', $authHeader, $matches)) {
                $decoded = base64_decode($matches[1], true);
                if ($decoded !== false) {
                    [$username, $password] = explode(':', $decoded, 2) + ['', ''];
                    if ($username === self::$adminUsername && $password === self::$adminPassword) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public static function requireAuth(ServerRequestInterface $request): void
    {
        if (!self::isAuthenticated($request)) {
            throw new \RuntimeException('Unauthorized', 401);
        }
    }
}

