<?php

declare(strict_types=1);

namespace App\Radar\Providers\Bmkg;

use App\Radar\Ingestion\HttpTransport;
use InvalidArgumentException;
use Radar\Contracts\SourceAdapter;
use Radar\Domain\NormalizedItem;
use Radar\Domain\SourceDefinition;

final class BmkgWeatherWarningAdapter implements SourceAdapter
{
    public function __construct(
        private readonly SourceDefinition $definition,
        private readonly HttpTransport $transport,
    ) {}

    public function fetch(): mixed
    {
        $rss = $this->transport->get((string) $this->definition->endpoint, 'xml')->payload;
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string) $rss, 'SimpleXMLElement', LIBXML_NONET);
        if ($xml === false) throw new InvalidArgumentException('BMKG warning RSS is malformed XML.');

        $items = [];
        foreach (($xml->channel->item ?? []) as $item) {
            $detailUrl = trim((string) $item->link);
            if ($detailUrl === '') continue;
            $cap = $this->transport->get($detailUrl, 'xml')->payload;
            $items[] = ['rss' => $item, 'cap' => $cap, 'detail_url' => $detailUrl];
        }
        return $items;
    }

    public function normalize(mixed $payload): array
    {
        if (!is_array($payload)) throw new InvalidArgumentException('BMKG warning payload must be a list.');
        return array_values(array_filter(array_map(function (mixed $entry): ?NormalizedItem {
            if (!is_array($entry) || !isset($entry['rss'], $entry['cap'])) return null;
            libxml_use_internal_errors(true);
            $cap = simplexml_load_string((string) $entry['cap'], 'SimpleXMLElement', LIBXML_NONET);
            if ($cap === false) throw new InvalidArgumentException('BMKG CAP detail is malformed XML.');
            $info = $cap->info[0] ?? null;
            if ($info === null) return null;
            $identifier = trim((string) ($cap->identifier ?? $entry['detail_url']));
            $polygon = trim((string) ($info->area->polygon ?? ''));
            $geometry = $this->geometry($polygon);
            $severity = strtolower(trim((string) ($info->severity ?? '')));
            $urgency = strtolower(trim((string) ($info->urgency ?? '')));
            $certainty = strtolower(trim((string) ($info->certainty ?? '')));

            return new NormalizedItem(
                externalId: $identifier,
                title: trim((string) ($info->headline ?? $entry['rss']->title)),
                text: trim((string) ($info->description ?? $entry['rss']->description)),
                url: $entry['detail_url'],
                publishedAt: trim((string) ($info->effective ?? $cap->sent)) ?: null,
                metadata: [
                    'provider' => 'BMKG',
                    'source_url' => $entry['detail_url'],
                    'attribution' => 'BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)',
                    'identifier' => $identifier,
                    'event' => (string) ($info->event ?? ''),
                    'sent' => (string) ($cap->sent ?? ''),
                    'effective' => (string) ($info->effective ?? ''),
                    'expires' => (string) ($info->expires ?? ''),
                    'urgency' => $urgency,
                    'severity' => $severity,
                    'certainty' => $certainty,
                    'sender_name' => (string) ($info->senderName ?? $cap->senderName ?? ''),
                    'instruction' => (string) ($info->instruction ?? ''),
                    'area_description' => (string) ($info->area->areaDesc ?? $entry['rss']->description),
                    'geometry' => $geometry,
                    'priority' => $this->priority($severity, $urgency, $certainty),
                ],
            );
        }, $payload)));
    }

    public function source(): string { return $this->definition->slug; }

    private function geometry(string $polygon): ?array
    {
        if ($polygon === '') return null;
        $coordinates = [];
        foreach (preg_split('/\s+/', $polygon) ?: [] as $pair) {
            [$lat, $lon] = array_map('floatval', array_pad(explode(',', $pair), 2, null));
            if ($lat !== 0.0 || $lon !== 0.0) $coordinates[] = [$lon, $lat];
        }
        return count($coordinates) >= 3 ? ['type' => 'Polygon', 'coordinates' => [$coordinates]] : null;
    }

    private function priority(string $severity, string $urgency, string $certainty): string
    {
        if ($severity === 'extreme' && in_array($urgency, ['immediate', 'expected'], true)) return 'CRITICAL';
        if ($severity === 'severe' || $urgency === 'immediate') return 'HIGH';
        if ($severity === 'moderate' || $certainty === 'likely') return 'MEDIUM';
        return 'LOW';
    }
}