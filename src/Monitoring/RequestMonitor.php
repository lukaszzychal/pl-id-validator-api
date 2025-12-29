<?php

declare(strict_types=1);

namespace App\Monitoring;

final class RequestMonitor
{
    private static string $statsFile = '/tmp/pl-validator-api-stats.json';
    private static array $rapidApiHooks = [];
    private static ?string $rapidApiSecret = null;
    private static ?int $startTime = null;

    public static function initialize(): void
    {
        self::$rapidApiSecret = getenv('RAPIDAPI_PROXY_SECRET') ?: null;
        
        // Initialize stats file if it doesn't exist
        if (!file_exists(self::$statsFile)) {
            self::resetStats();
        } else {
            $stats = self::loadStats();
            if (!isset($stats['start_time']) || $stats['start_time'] === null) {
                self::resetStats();
            }
        }
        
        if (self::$startTime === null) {
            $stats = self::loadStats();
            self::$startTime = $stats['start_time'] ?? time();
        }
    }
    
    private static function loadStats(): array
    {
        if (!file_exists(self::$statsFile)) {
            return self::getDefaultStats();
        }
        
        $content = file_get_contents(self::$statsFile);
        if ($content === false) {
            return self::getDefaultStats();
        }
        
        $stats = json_decode($content, true);
        if (!is_array($stats)) {
            return self::getDefaultStats();
        }
        
        return array_merge(self::getDefaultStats(), $stats);
    }
    
    private static function saveStats(array $stats): void
    {
        file_put_contents(self::$statsFile, json_encode($stats, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
    
    private static function getDefaultStats(): array
    {
        return [
            'total' => 0,
            'rapidapi' => 0,
            'direct' => 0,
            'by_endpoint' => [],
            'by_method' => [],
            'rapidapi_by_token' => [],
            'start_time' => time(),
        ];
    }

    public static function isRapidApiRequest(\Psr\Http\Message\ServerRequestInterface $request): bool
    {
        // Check for RapidAPI headers
        $hasRapidApiHost = $request->hasHeader('X-RapidAPI-Host');
        $hasRapidApiKey = $request->hasHeader('X-RapidAPI-Key');
        
        // Check for proxy secret if configured
        if (self::$rapidApiSecret !== null) {
            $proxySecret = $request->getHeaderLine('X-RapidAPI-Proxy-Secret');
            if ($proxySecret === self::$rapidApiSecret) {
                return true;
            }
        }

        return $hasRapidApiHost || $hasRapidApiKey;
    }

    public static function getRapidApiToken(\Psr\Http\Message\ServerRequestInterface $request): ?string
    {
        if (!self::isRapidApiRequest($request)) {
            return null;
        }

        return $request->getHeaderLine('X-RapidAPI-Key') ?: 'unknown';
    }

    public static function recordRequest(
        string $method,
        string $path,
        bool $isRapidApi,
        ?string $rapidApiToken = null
    ): void {
        $stats = self::loadStats();
        
        $stats['total']++;
        
        if ($isRapidApi) {
            $stats['rapidapi']++;
            
            if ($rapidApiToken !== null) {
                $tokenHash = substr(md5($rapidApiToken), 0, 8); // Store only hash for privacy
                if (!isset($stats['rapidapi_by_token'][$tokenHash])) {
                    $stats['rapidapi_by_token'][$tokenHash] = 0;
                }
                $stats['rapidapi_by_token'][$tokenHash]++;
            }
        } else {
            $stats['direct']++;
        }

        // Track by endpoint
        if (!isset($stats['by_endpoint'][$path])) {
            $stats['by_endpoint'][$path] = 0;
        }
        $stats['by_endpoint'][$path]++;

        // Track by method
        if (!isset($stats['by_method'][$method])) {
            $stats['by_method'][$method] = 0;
        }
        $stats['by_method'][$method]++;
        
        // Ensure start_time is set
        if (!isset($stats['start_time']) || $stats['start_time'] === null) {
            $stats['start_time'] = self::$startTime ?? time();
        }
        
        self::saveStats($stats);
    }

    public static function addRapidApiHook(callable $hook): void
    {
        self::$rapidApiHooks[] = $hook;
    }

    public static function triggerRapidApiHooks(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $context = []
    ): void {
        if (!self::isRapidApiRequest($request)) {
            return;
        }

        $token = self::getRapidApiToken($request);
        
        foreach (self::$rapidApiHooks as $hook) {
            try {
                $hook($request, $response, [
                    'token' => $token,
                    'token_hash' => $token ? substr(md5($token), 0, 8) : null,
                    ...$context
                ]);
            } catch (\Throwable $e) {
                error_log(sprintf(
                    "[RAPIDAPI_HOOK_ERROR] Hook failed: %s",
                    $e->getMessage()
                ));
            }
        }
    }

    public static function getStats(): array
    {
        $stats = self::loadStats();
        $startTime = $stats['start_time'] ?? (self::$startTime ?? time());
        $uptime = time() - $startTime;
        
        return [
            'total_requests' => $stats['total'],
            'rapidapi_requests' => $stats['rapidapi'],
            'direct_requests' => $stats['direct'],
            'rapidapi_percentage' => $stats['total'] > 0 
                ? round(($stats['rapidapi'] / $stats['total']) * 100, 2) 
                : 0,
            'direct_percentage' => $stats['total'] > 0 
                ? round(($stats['direct'] / $stats['total']) * 100, 2) 
                : 0,
            'by_endpoint' => $stats['by_endpoint'],
            'by_method' => $stats['by_method'],
            'rapidapi_by_token' => $stats['rapidapi_by_token'],
            'uptime_seconds' => $uptime,
            'uptime_formatted' => self::formatUptime($uptime),
            'requests_per_minute' => $uptime > 0 
                ? round(($stats['total'] / $uptime) * 60, 2) 
                : 0,
        ];
    }

    public static function resetStats(): void
    {
        $stats = self::getDefaultStats();
        $stats['start_time'] = time();
        self::$startTime = time();
        self::saveStats($stats);
    }

    private static function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($days > 0) {
            return sprintf('%dd %dh %dm %ds', $days, $hours, $minutes, $secs);
        }
        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $secs);
        }
        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $secs);
        }
        return sprintf('%ds', $secs);
    }
}

