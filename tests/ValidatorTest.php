<?php

declare(strict_types=1);

namespace App\Tests;

use App\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testNormalize(): void
    {
        $v = new Validator();
        self::assertSame('PL10105000997603123456789123', $v->normalize('PL 10 1050 0099 7603 1234 5678 9123'));
    }

    public function testValidNip(): void
    {
        $v = new Validator();
        $res = $v->validateNip('123-456-32-18'); // valid checksum
        self::assertTrue($res['valid']);
        self::assertSame('1234563218', $res['normalized']);
    }

    public function testInvalidNip(): void
    {
        $v = new Validator();
        $res = $v->validateNip('123-456-32-19');
        self::assertFalse($res['valid']);
    }

    public function testValidRegon9(): void
    {
        $v = new Validator();
        $res = $v->validateRegon('590096454');
        self::assertTrue($res['valid']);
        self::assertSame('9', $res['type']);
    }

    public function testValidRegon14(): void
    {
        $v = new Validator();
        $res = $v->validateRegon('59009645400002');
        self::assertTrue($res['valid']);
        self::assertSame('14', $res['type']);
    }

    public function testInvalidRegon(): void
    {
        $v = new Validator();
        $res = $v->validateRegon('590096455');
        self::assertFalse($res['valid']);
    }

    public function testValidIbanPolandExample(): void
    {
        $v = new Validator();
        $res = $v->validateIban('PL 10 1050 0099 7603 1234 5678 9123');
        self::assertTrue($res['valid']);
        self::assertSame('PL', $res['country']);
        self::assertSame('PL10105000997603123456789123', $res['normalized']);
    }

    public function testInvalidIban(): void
    {
        $v = new Validator();
        $res = $v->validateIban('PL00105000997603123456789123');
        self::assertFalse($res['valid']);
    }

    public function testNormalizeWithSpecialCharacters(): void
    {
        $v = new Validator();
        self::assertSame('ABC123', $v->normalize('A-B C_1.2,3'));
        self::assertSame('PL10105000997603123456789123', $v->normalize('pl-10-1050-0099-7603-1234-5678-9123'));
    }

    public function testNormalizeEmptyString(): void
    {
        $v = new Validator();
        self::assertSame('', $v->normalize(''));
    }

    public function testValidateNipEmptyString(): void
    {
        $v = new Validator();
        $res = $v->validateNip('');
        self::assertFalse($res['valid']);
        self::assertSame('', $res['normalized']);
    }

    public function testValidateNipTooShort(): void
    {
        $v = new Validator();
        $res = $v->validateNip('123456789');
        self::assertFalse($res['valid']);
    }

    public function testValidateNipTooLong(): void
    {
        $v = new Validator();
        $res = $v->validateNip('12345678901');
        self::assertFalse($res['valid']);
    }

    public function testValidateNipWithOnlyDigits(): void
    {
        $v = new Validator();
        $res = $v->validateNip('1234563218');
        self::assertTrue($res['valid']);
        self::assertSame('1234563218', $res['normalized']);
    }

    public function testValidateRegonEmptyString(): void
    {
        $v = new Validator();
        $res = $v->validateRegon('');
        self::assertFalse($res['valid']);
        self::assertNull($res['type']);
    }

    public function testValidateRegonTooShort(): void
    {
        $v = new Validator();
        $res = $v->validateRegon('12345678');
        self::assertFalse($res['valid']);
    }

    public function testValidateRegonWrongLength(): void
    {
        $v = new Validator();
        $res = $v->validateRegon('1234567890'); // 10 digits
        self::assertFalse($res['valid']);
    }

    public function testValidateRegonTooLong(): void
    {
        $v = new Validator();
        $res = $v->validateRegon('123456789012345');
        self::assertFalse($res['valid']);
    }

    public function testValidateIbanEmptyString(): void
    {
        $v = new Validator();
        $res = $v->validateIban('');
        self::assertFalse($res['valid']);
        self::assertNull($res['country']);
        self::assertSame('', $res['normalized']);
    }

    public function testValidateIbanTooShort(): void
    {
        $v = new Validator();
        $res = $v->validateIban('PL12');
        self::assertFalse($res['valid']);
    }

    public function testValidateIbanInvalidCountryCode(): void
    {
        $v = new Validator();
        $res = $v->validateIban('12 10 1050 0099 7603 1234 5678 9123');
        self::assertFalse($res['valid']);
    }

    public function testValidateIbanOtherCountry(): void
    {
        $v = new Validator();
        // DE89 3704 0044 0532 0130 00 - valid German IBAN
        $res = $v->validateIban('DE89 3704 0044 0532 0130 00');
        self::assertTrue($res['valid']);
        self::assertSame('DE', $res['country']);
    }

    public function testNormalizeWithUnicode(): void
    {
        $v = new Validator();
        // Should remove all non-alphanumeric including Unicode
        self::assertSame('ABC123', $v->normalize('A-B-C-1-2-3-ą-ć-ę'));
    }
}
