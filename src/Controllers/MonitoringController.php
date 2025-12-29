<?php

declare(strict_types=1);

namespace App\Controllers;

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

    public function reset(Request $request, Response $response): Response
    {
        RequestMonitor::resetStats();
        
        $response->getBody()->write(json_encode([
            'status' => 'ok',
            'message' => 'Statistics reset successfully',
        ], JSON_UNESCAPED_UNICODE));
        
        return $response->withStatus(200);
    }
}

