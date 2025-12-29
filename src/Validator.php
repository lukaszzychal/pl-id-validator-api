<?php

declare(strict_types=1);

namespace App;

final class Validator
{
    public function normalize(string $value): string
    {
        $value = preg_replace('/[^0-9A-Za-z]/', '', $value) ?? '';
        return strtoupper($value);
    }

    public function validateNip(string $value): array
    {
        $nip = preg_replace('/\D/', '', $value) ?? '';
        $valid = $this->isValidNip($nip);

        return [
            'valid' => $valid,
            'normalized' => $nip,
        ];
    }

    public function validateRegon(string $value): array
    {
        $regon = preg_replace('/\D/', '', $value) ?? '';
        [$valid, $type] = $this->isValidRegon($regon);

        return [
            'valid' => $valid,
            'type' => $type,
            'normalized' => $regon,
        ];
    }

    public function validateIban(string $value): array
    {
        $iban = $this->normalize($value);

        return [
            'valid' => $this->isValidIban($iban),
            'country' => strlen($iban) >= 2 ? substr($iban, 0, 2) : null,
            'normalized' => $iban,
        ];
    }

    private function isValidNip(string $nip): bool
    {
        if (!preg_match('/^\d{10}$/', $nip)) {
            return false;
        }

        $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $nip[$i]) * $weights[$i];
        }

        $checksum = $sum % 11;

        return $checksum !== 10 && $checksum === (int) $nip[9];
    }

    /**
     * @return array{0: bool, 1: string|null} [valid, type: "9"|"14"|null]
     */
    private function isValidRegon(string $regon): array
    {
        if (preg_match('/^\d{9}$/', $regon)) {
            $weights = [8, 9, 2, 3, 4, 5, 6, 7];
            $sum = 0;

            for ($i = 0; $i < 8; $i++) {
                $sum += ((int) $regon[$i]) * $weights[$i];
            }

            $c = $sum % 11;
            if ($c === 10) {
                $c = 0;
            }

            return [$c === (int) $regon[8], '9'];
        }

        if (preg_match('/^\d{14}$/', $regon)) {
            $weights = [2, 4, 8, 5, 0, 9, 7, 3, 6, 1, 2, 4, 8];
            $sum = 0;

            for ($i = 0; $i < 13; $i++) {
                $sum += ((int) $regon[$i]) * $weights[$i];
            }

            $c = $sum % 11;
            if ($c === 10) {
                $c = 0;
            }

            return [$c === (int) $regon[13], '14'];
        }

        return [false, null];
    }

    private function isValidIban(string $iban): bool
    {
        // Generic IBAN sanity check (length depends on country)
        if (!preg_match('/^[A-Z]{2}[0-9A-Z]{13,32}$/', $iban)) {
            return false;
        }

        // MOD 97-10
        $moved = substr($iban, 4) . substr($iban, 0, 4);

        $expanded = '';
        $len = strlen($moved);
        for ($i = 0; $i < $len; $i++) {
            $ch = $moved[$i];
            if (ctype_alpha($ch)) {
                $expanded .= (string) (ord($ch) - 55);
            } else {
                $expanded .= $ch;
            }
        }

        $mod = 0;
        $elen = strlen($expanded);
        for ($i = 0; $i < $elen; $i++) {
            $mod = ($mod * 10 + (int) $expanded[$i]) % 97;
        }

        return $mod === 1;
    }
}
