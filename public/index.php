<?php

declare(strict_types=1);

use App\Controllers\ValidatorController;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Basic JSON middleware
$app->addBodyParsingMiddleware();

// Simple CORS for local dev (RapidAPI does server-to-server anyway)
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-RapidAPI-Proxy-Secret')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
});

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
