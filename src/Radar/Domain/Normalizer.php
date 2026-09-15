<?php

declare(strict_types=1);

namespace Radar\Domain;

final class Normalizer
{
    public static function text(string $value): string
    {
        $value = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $value) ?? $value;
        $value = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $value) ?? $value;
        $value = strip_tags($value);
        $value = preg_replace('/\b(?:utm_[a-z_]+|fbclid)=[^\s&]+/i', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($value, 'UTF-8');
    }

    public static function url(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $parts = parse_url(trim($url));
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return trim($url);
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '/';
        $query = [];
        parse_str($parts['query'] ?? '', $query);
        foreach (array_keys($query) as $key) {
            if (str_starts_with(strtolower((string) $key), 'utm_') || strtolower((string) $key) === 'fbclid') {
                unset($query[$key]);
            }
        }
        ksort($query);

        return $scheme.'://'.$host.$path.($query === [] ? '' : '?'.http_build_query($query));
    }

    /** @return mixed */
    public static function value(mixed $value): mixed
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                return array_map(self::value(...), $value);
            }
            ksort($value);
            foreach ($value as $key => $item) {
                $value[$key] = self::value($item);
            }
        }

        return $value;
    }
}