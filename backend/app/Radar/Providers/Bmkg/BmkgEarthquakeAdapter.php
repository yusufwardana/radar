<?php

declare(strict_types=1);

namespace App\Radar\Providers\Bmkg;

use App\Radar\Ingestion\HttpTransport;
use InvalidArgumentException;
use Radar\Contracts\SourceAdapter;
use Radar\Domain\NormalizedItem;
use Radar\Domain\SourceDefinition;

final class BmkgEarthquakeAdapter implements SourceAdapter
{
    public function __construct(
        private readonly SourceDefinition $definition,
        private readonly HttpTransport $transport,
    ) {}

    public function fetch(): mixed
    {
        return $this->transport->get((string) $this->definition->endpoint, 'application/json')->payload;
    }

    public function normalize(mixed $payload): array
    {
        $earthquakes = $payload['Infogempa']['gempa'] ?? null;
        if (!is_array($earthquakes)) {
            throw new InvalidArgumentException('BMKG earthquake payload has an invalid Infogempa.gempa structure.');
        }
        if (isset($earthquakes['DateTime'])) {
            $earthquakes = [$earthquakes];
        }

        return array_values(array_filter(array_map(function (mixed $event): ?NormalizedItem {
            if (!is_array($event) || empty($event['DateTime']) || empty($event['Coordinates'])) return null;
            [$latitude, $longitude] = array_map('floatval', array_pad(explode(',', (string) $event['Coordinates']), 2, null));
            $magnitude = (float) ($event['Magnitude'] ?? 0);
            $depth = (float) preg_replace('/[^0-9.\-]/', '', (string) ($event['Kedalaman'] ?? '0'));
            $externalId = hash('sha256', implode('|', [
                $event['DateTime'], $event['Coordinates'], $event['Magnitude'] ?? '', $event['Kedalaman'] ?? '', $event['Wilayah'] ?? '',
            ]));

            return new NormalizedItem(
                externalId: $externalId,
                title: sprintf('BMKG earthquake M%s — %s', $event['Magnitude'] ?? 'unknown', $event['Wilayah'] ?? 'Indonesia'),
                text: trim((string) ($event['Potensi'] ?? $event['Dirasakan'] ?? '')), 
                url: $this->definition->endpoint,
                publishedAt: (string) $event['DateTime'],
                metadata: [
                    'provider' => 'BMKG',
                    'source_url' => $this->definition->endpoint,
                    'attribution' => 'BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)',
                    'event_time' => $event['DateTime'],
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'depth_km' => $depth,
                    'magnitude' => $magnitude,
                    'magnitude_type' => 'M',
                    'region' => $event['Wilayah'] ?? null,
                    'felt_description' => $event['Dirasakan'] ?? null,
                    'potential' => $event['Potensi'] ?? null,
                    'tsunami_status' => str_contains(strtolower((string) ($event['Potensi'] ?? '')), 'tsunami') ? (string) $event['Potensi'] : null,
                    'priority' => $this->priority($magnitude, $event),
                    'raw_reference' => ['Tanggal' => $event['Tanggal'] ?? null, 'Jam' => $event['Jam'] ?? null],
                ],
            );
        }, $earthquakes)));
    }

    public function source(): string { return $this->definition->slug; }

    private function priority(float $magnitude, array $event): string
    {
        $felt = trim((string) ($event['Dirasakan'] ?? '')) !== '';
        $potential = strtolower((string) ($event['Potensi'] ?? ''));
        $tsunami = str_contains($potential, 'tsunami') && !str_contains($potential, 'tidak');
        if ($tsunami || $magnitude >= 6.0) return 'CRITICAL';
        if ($magnitude >= 5.0 || $felt) return 'HIGH';
        if ($magnitude >= 4.0) return 'MEDIUM';
        return 'LOW';
    }
}