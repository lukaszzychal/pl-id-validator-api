<?php

declare(strict_types=1);

namespace App\Tests;

use App\Controllers\ValidatorController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

final class ValidatorControllerTest extends TestCase
{
    private ValidatorController $controller;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        $this->controller = new ValidatorController();
        $this->responseFactory = new ResponseFactory();
    }

    public function testHealthEndpoint(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/v1/health');
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->health($request, $response);

        self::assertSame(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        self::assertSame(['status' => 'ok'], $data);
    }

    public function testNormalizeEndpoint(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/v1/normalize')
            ->withParsedBody(['value' => 'PL 10 1050 0099 7603 1234 5678 9123']);
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->normalize($request, $response);

        self::assertSame(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        self::assertSame('PL10105000997603123456789123', $data['normalized']);
    }

    public function testNormalizeEndpointWithEmptyValue(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/v1/normalize')
            ->withParsedBody(['value' => '']);
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->normalize($request, $response);

        self::assertSame(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        self::assertSame('', $data['normalized']);
    }

    public function testNormalizeEndpointWithMissingValue(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/v1/normalize')
            ->withParsedBody([]);
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->normalize($request, $response);

        self::assertSame(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        self::assertSame('', $data['normalized']);
    }

    public function testValidateNipEndpoint(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/v1/validate/nip')
            ->withParsedBody(['value' => '123-456-32-18']);
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->nip($request, $response);

        self::assertSame(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        self::assertTrue($data['valid']);
        self::assertSame('1234563218', $data['normalized']);
    }

    public function testValidateNipEndpointInvalid(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/v1/validate/nip')
            ->withParsedBody(['value' => '123-456-32-19']);
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->nip($request, $response);

        self::assertSame(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        self::assertFalse($data['valid']);
    }

    public function testValidateRegonEndpoint9(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/v1/validate/regon')
            ->withParsedBody(['value' => '590096454']);
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->regon($request, $response);

        self::assertSame(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        self::assertTrue($data['valid']);
        self::assertSame('9', $data['type']);
        self::assertSame('590096454', $data['normalized']);
    }

    public function testValidateRegonEndpoint14(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/v1/validate/regon')
            ->withParsedBody(['value' => '59009645400002']);
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->regon($request, $response);

        self::assertSame(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        self::assertTrue($data['valid']);
        self::assertSame('14', $data['type']);
        self::assertSame('59009645400002', $data['normalized']);
    }

    public function testValidateRegonEndpointInvalid(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/v1/validate/regon')
            ->withParsedBody(['value' => '590096455']);
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->regon($request, $response);

        self::assertSame(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        self::assertFalse($data['valid']);
    }

    public function testValidateIbanEndpoint(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/v1/validate/iban')
            ->withParsedBody(['value' => 'PL 10 1050 0099 7603 1234 5678 9123']);
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->iban($request, $response);

        self::assertSame(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        self::assertTrue($data['valid']);
        self::assertSame('PL', $data['country']);
        self::assertSame('PL10105000997603123456789123', $data['normalized']);
    }

    public function testValidateIbanEndpointInvalid(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/v1/validate/iban')
            ->withParsedBody(['value' => 'PL00105000997603123456789123']);
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->iban($request, $response);

        self::assertSame(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        self::assertFalse($data['valid']);
        self::assertSame('PL', $data['country']);
    }

    public function testValidateIbanEndpointWithEmptyValue(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/v1/validate/iban')
            ->withParsedBody(['value' => '']);
        $response = $this->responseFactory->createResponse();

        $result = $this->controller->iban($request, $response);

        self::assertSame(200, $result->getStatusCode());
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        self::assertFalse($data['valid']);
        self::assertNull($data['country']);
        self::assertSame('', $data['normalized']);
    }
}

