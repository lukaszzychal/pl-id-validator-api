<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\SimpleAuth;
use App\Monitoring\RequestMonitor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class MonitoringController
{
    public function stats(Request $request, Response $response): Response
    {
        $stats = RequestMonitor::getStats();
        
        $response->getBody()->write(json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response->withStatus(200);
    }

    public function statsFiltered(Request $request, Response $response): Response
    {
        // Require authentication
        if (!SimpleAuth::isAuthenticated($request)) {
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized',
                'message' => 'Authentication required. Use Basic Auth or X-API-Key header.',
            ], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(401)->withHeader('WWW-Authenticate', 'Basic realm="Monitoring"');
        }

        // Get query parameters as filters
        $queryParams = $request->getQueryParams();
        $filters = [
            'endpoint' => $queryParams['endpoint'] ?? null,
            'method' => $queryParams['method'] ?? null,
            'source' => $queryParams['source'] ?? null, // 'rapidapi' or 'direct'
            'token_hash' => $queryParams['token_hash'] ?? null,
            'limit' => isset($queryParams['limit']) && is_numeric($queryParams['limit']) 
                ? (int) $queryParams['limit'] 
                : null,
        ];

        // Remove null filters
        $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

        $stats = RequestMonitor::getStats(!empty($filters) ? $filters : null);
        
        // Add filter info to response
        $result = [
            'filters_applied' => $filters,
            'statistics' => $stats,
        ];
        
        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response->withStatus(200);
    }

    public function reset(Request $request, Response $response): Response
    {
        // Require authentication
        if (!SimpleAuth::isAuthenticated($request)) {
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized',
                'message' => 'Authentication required. Use Basic Auth or X-API-Key header.',
            ], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(401)->withHeader('WWW-Authenticate', 'Basic realm="Monitoring"');
        }

        RequestMonitor::resetStats();
        
        $response->getBody()->write(json_encode([
            'status' => 'ok',
            'message' => 'Statistics reset successfully',
        ], JSON_UNESCAPED_UNICODE));
        
        return $response->withStatus(200);
    }
}

