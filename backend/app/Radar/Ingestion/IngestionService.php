<?php

declare(strict_types=1);

namespace App\Radar\Ingestion;

use DateTimeImmutable;
use Radar\Contracts\SourceAdapter;
use Radar\Domain\Snapshot;
use Radar\Engine\ChangeEngine;
use Radar\Engine\SignalFactory;

final class IngestionService
{
    public function __construct(
        private readonly ChangeEngine $changeEngine,
        private readonly SignalFactory $signalFactory,
    ) {}

    /** @param array<string, Snapshot> $previous */
    /** @return list<\Radar\Domain\Signal> */
    public function process(SourceAdapter $adapter, array $previous): array
    {
        $fetchedAt = new DateTimeImmutable();
        $snapshots = [];
        foreach ($adapter->normalize($adapter->fetch()) as $item) {
            $snapshots[] = new Snapshot($adapter->source(), $item, $item->normalizedHash(), $fetchedAt);
        }

        return array_map(
            $this->signalFactory->fromChange(...),
            $this->changeEngine->compare($previous, $snapshots),
        );
    }
}