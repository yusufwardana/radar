<?php

declare(strict_types=1);

namespace App\Radar\Locations;

use InvalidArgumentException;

final readonly class AdministrativeCode
{
    private function __construct(public string $digits, public string $display)
    {
    }

    public static function parse(string $value): self
    {
        $value = trim($value);
        if ($value === '' || preg_match('/^(?:\d{2}|\d{2}\.\d{2}|\d{2}\.\d{2}\.\d{2}|\d{2}\.\d{2}\.\d{2}\.\d{4}|\d{4}|\d{6}|\d{10})$/', $value) !== 1) {
            throw new InvalidArgumentException('Invalid administrative code format.');
        }

        $digits = preg_replace('/\D/', '', $value);
        if ($digits === null || !in_array(strlen($digits), [2, 4, 6, 10], true)) {
            throw new InvalidArgumentException('Administrative code must represent ADM1 through ADM4.');
        }

        return new self($digits, self::formatDigits($digits));
    }

    public static function forLevel(string $value, LocationLevel $level): self
    {
        $code = self::parse($value);
        $expected = match ($level) {
            LocationLevel::PROVINCE => 2,
            LocationLevel::REGENCY, LocationLevel::CITY => 4,
            LocationLevel::DISTRICT => 6,
            LocationLevel::VILLAGE, LocationLevel::KELURAHAN, LocationLevel::ADM4 => 10,
            LocationLevel::COUNTRY => 0,
        };
        if ($expected === 0 || strlen($code->digits) !== $expected) {
            throw new InvalidArgumentException('Administrative code does not match location level.');
        }
        return $code;
    }

    public function parent(): ?self
    {
        return match (strlen($this->digits)) {
            2 => null,
            4, 6 => self::parse(substr($this->digits, 0, -2)),
            10 => self::parse(substr($this->digits, 0, -4)),
            default => throw new InvalidArgumentException('Administrative code hierarchy is invalid.'),
        };
    }

    public function isAdm4(): bool { return strlen($this->digits) === 10; }

    public function equals(self $other): bool { return $this->digits === $other->digits; }

    public function __toString(): string { return $this->display; }

    private static function formatDigits(string $digits): string
    {
        return implode('.', array_filter([substr($digits, 0, 2), substr($digits, 2, 2), substr($digits, 4, 2), substr($digits, 6, 4)], fn (?string $part): bool => $part !== ''));
    }
}