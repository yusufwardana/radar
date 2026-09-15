<?php

declare(strict_types=1);

namespace App\Radar\Locations;

use InvalidArgumentException;

final class OfficialDatasetUrlValidator
{
    /** @param array<int,string>|null $allowedHosts */
    public function __construct(private readonly ?array $allowedHosts = null) {}

    public function validate(string $rawUrl): string
    {
        $parts = parse_url($rawUrl);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user'], $parts['pass'])) {
            throw new InvalidArgumentException('LOCATION_DATASET_URL_INVALID');
        }
        $allowed = $this->allowedHosts ?? ['kemendagri.go.id'];
        $valid = collect($allowed)->contains(fn (string $domain): bool => $host === $domain || str_ends_with($host, '.'.$domain));
        if (!$valid) throw new InvalidArgumentException('LOCATION_DATASET_URL_HOST_NOT_ALLOWED');
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) throw new InvalidArgumentException('LOCATION_DATASET_URL_HOST_NOT_ALLOWED');
        return $rawUrl;
    }
}