<?php

declare(strict_types=1);

use App\Controllers\MonitoringController;
use App\Controllers\ValidatorController;
use App\Monitoring\RequestMonitor;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Initialize monitoring
RequestMonitor::initialize();

$app = AppFactory::create();

// Request/Response logging and monitoring middleware (before body parsing to capture raw body)
$app->add(function ($request, $handler) use ($app) {
    $method = $request->getMethod();
    $path = $request->getUri()->getPath();
    
    // Handle OPTIONS requests early (CORS preflight) - return response directly
    if ($method === 'OPTIONS') {
        $response = $app->getResponseFactory()->createResponse();
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-RapidAPI-Proxy-Secret, X-RapidAPI-Key, X-RapidAPI-Host')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->withStatus(204);
    }
    
    $startTime = microtime(true);
    
    // Skip monitoring for monitoring endpoints themselves
    $skipMonitoring = in_array($path, ['/v1/monitoring/stats', '/v1/monitoring/reset']);
    
    // Detect RapidAPI request
    $isRapidApi = RequestMonitor::isRapidApiRequest($request);
    $rapidApiToken = $isRapidApi ? RequestMonitor::getRapidApiToken($request) : null;
    
    // Record request in monitoring (skip monitoring endpoints)
    if (!$skipMonitoring) {
        RequestMonitor::recordRequest($method, $path, $isRapidApi, $rapidApiToken);
    }
    
    // Read body before parsing (rewind stream first)
    $body = $request->getBody();
    $body->rewind();
    $rawBody = $body->getContents();
    $body->rewind(); // Reset for BodyParsingMiddleware
    
    // Get IP address
    $serverParams = $request->getServerParams();
    $clientIp = $serverParams['REMOTE_ADDR'] ?? 
                $request->getHeaderLine('X-Forwarded-For') ?: 
                $request->getHeaderLine('X-Real-IP') ?: 
                'unknown';
    
    // Handle request (body will be parsed by BodyParsingMiddleware)
    $response = $handler->handle($request);
    
    // Get parsed body after processing
    $parsedBody = $request->getParsedBody();
    
    // Determine request source
    $source = $isRapidApi ? 'RapidAPI' : 'Direct';
    $sourceInfo = $isRapidApi && $rapidApiToken 
        ? sprintf('RapidAPI (token: %s)', substr(md5($rapidApiToken), 0, 8)) 
        : $source;
    
    // Log request with source information
    error_log(sprintf(
        "[REQUEST] %s %s | Source: %s | IP: %s | Body (raw): %s | Body (parsed): %s",
        $method,
        $path,
        $sourceInfo,
        $clientIp,
        $rawBody ?: '(empty)',
        $parsedBody ? json_encode($parsedBody, JSON_UNESCAPED_UNICODE) : '(empty)'
    ));
    
    // Log response
    $responseBody = (string) $response->getBody();
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    error_log(sprintf(
        "[RESPONSE] %s %s | Status: %d | Duration: %.2fms | Body: %s",
        $method,
        $path,
        $response->getStatusCode(),
        $duration,
        $responseBody ?: '(empty)'
    ));
    
    // Trigger RapidAPI hooks (if applicable)
    if (!$skipMonitoring && $isRapidApi) {
        RequestMonitor::triggerRapidApiHooks($request, $response, [
            'duration' => $duration,
            'method' => $method,
            'path' => $path,
        ]);
    }
    
    // Rewind response body (it was read for logging)
    $response->getBody()->rewind();
    
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-RapidAPI-Proxy-Secret')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
});

// Basic JSON middleware (must be after logging to parse body for handlers)
$app->addBodyParsingMiddleware();

$validatorController = new ValidatorController();
$monitoringController = new MonitoringController();

// Root endpoint - API information
$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'name' => 'PL Validator API',
        'version' => '1.0.0',
        'description' => 'Validate and normalize Polish identifiers (NIP, REGON) and IBAN',
        'endpoints' => [
            'GET  /v1/health' => 'Health check',
            'POST /v1/normalize' => 'Normalize input value',
            'POST /v1/validate/nip' => 'Validate NIP',
            'POST /v1/validate/regon' => 'Validate REGON',
            'POST /v1/validate/iban' => 'Validate IBAN',
            'GET  /v1/monitoring/stats' => 'Get request statistics',
            'POST /v1/monitoring/reset' => 'Reset statistics',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    return $response;
});

// Validator endpoints
$app->get('/v1/health', [$validatorController, 'health']);
$app->post('/v1/normalize', [$validatorController, 'normalize']);
$app->post('/v1/validate/nip', [$validatorController, 'nip']);
$app->post('/v1/validate/regon', [$validatorController, 'regon']);
$app->post('/v1/validate/iban', [$validatorController, 'iban']);

// Monitoring endpoints
$app->get('/v1/monitoring/stats', [$monitoringController, 'stats']);
$app->post('/v1/monitoring/reset', [$monitoringController, 'reset']);

// Configure RapidAPI hooks (example hooks)
RequestMonitor::addRapidApiHook(function ($request, $response, $context) {
    error_log(sprintf(
        "[RAPIDAPI_HOOK] Request detected | Token: %s | Path: %s | Method: %s | Duration: %.2fms",
        $context['token_hash'] ?? 'unknown',
        $context['path'] ?? 'unknown',
        $context['method'] ?? 'unknown',
        $context['duration'] ?? 0
    ));
});

// Add more hooks as needed - they will all be called for RapidAPI requests
// Example: Send webhook, update database, trigger analytics, etc.

$app->run();
