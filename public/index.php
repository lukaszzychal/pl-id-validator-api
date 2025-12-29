<?php

declare(strict_types=1);

use App\Controllers\ValidatorController;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Request/Response logging middleware (before body parsing to capture raw body)
$app->add(function ($request, $handler) use ($app) {
    $startTime = microtime(true);
    $method = $request->getMethod();
    $path = $request->getUri()->getPath();
    
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
    
    // Log request
    error_log(sprintf(
        "[REQUEST] %s %s | IP: %s | Body (raw): %s | Body (parsed): %s",
        $method,
        $path,
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

$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

$controller = new ValidatorController();

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
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    return $response;
});

$app->get('/v1/health', [$controller, 'health']);
$app->post('/v1/normalize', [$controller, 'normalize']);
$app->post('/v1/validate/nip', [$controller, 'nip']);
$app->post('/v1/validate/regon', [$controller, 'regon']);
$app->post('/v1/validate/iban', [$controller, 'iban']);

$app->run();
