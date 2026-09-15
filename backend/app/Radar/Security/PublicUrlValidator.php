<?php

declare(strict_types=1);

namespace App\Radar\Security;

use InvalidArgumentException;

final class PublicUrlValidator
{
    public function validate(string $rawUrl): string
    {
        $url = parse_url($rawUrl);
        if ($url === false || !isset($url['scheme'], $url['host']) || !in_array(strtolower($url['scheme']), ['http', 'https'], true)) {
            throw new InvalidArgumentException('URL must use HTTP or HTTPS.');
        }
        if (isset($url['user'], $url['pass']) || filter_var($url['host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $this->isPrivateIpv4($url['host'])) {
            throw new InvalidArgumentException('URL destination is not public.');
        }
        if (in_array(strtolower($url['host']), ['localhost', 'metadata.google.internal', 'host.docker.internal'], true)) {
            throw new InvalidArgumentException('URL destination is not public.');
        }
        return $rawUrl;
    }

    private function isPrivateIpv4(string $ip): bool
    {
        $long = ip2long($ip);
        return $long !== false && (
            ($long >= ip2long('10.0.0.0') && $long <= ip2long('10.255.255.255')) ||
            ($long >= ip2long('172.16.0.0') && $long <= ip2long('172.31.255.255')) ||
            ($long >= ip2long('192.168.0.0') && $long <= ip2long('192.168.255.255')) ||
            ($long >= ip2long('127.0.0.0') && $long <= ip2long('127.255.255.255')) ||
            ($long >= ip2long('169.254.0.0') && $long <= ip2long('169.254.255.255'))
        );
    }
}