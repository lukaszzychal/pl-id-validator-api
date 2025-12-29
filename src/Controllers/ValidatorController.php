<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ValidatorController
{
    private Validator $validator;

    public function __construct(?Validator $validator = null)
    {
        $this->validator = $validator ?? new Validator();
    }

    public function health(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE));
        return $response;
    }

    public function normalize(Request $request, Response $response): Response
    {
        $data = (array) ($request->getParsedBody() ?? []);
        $value = (string) ($data['value'] ?? '');

        $payload = ['normalized' => $this->validator->normalize($value)];

        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response;
    }

    public function nip(Request $request, Response $response): Response
    {
        $data = (array) ($request->getParsedBody() ?? []);
        $value = (string) ($data['value'] ?? '');

        $payload = $this->validator->validateNip($value);

        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response;
    }

    public function regon(Request $request, Response $response): Response
    {
        $data = (array) ($request->getParsedBody() ?? []);
        $value = (string) ($data['value'] ?? '');

        $payload = $this->validator->validateRegon($value);

        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response;
    }

    public function iban(Request $request, Response $response): Response
    {
        $data = (array) ($request->getParsedBody() ?? []);
        $value = (string) ($data['value'] ?? '');

        $payload = $this->validator->validateIban($value);

        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response;
    }
}
