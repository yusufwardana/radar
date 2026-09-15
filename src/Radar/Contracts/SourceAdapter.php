<?php

declare(strict_types=1);

namespace Radar\Contracts;

use Radar\Domain\NormalizedItem;

interface SourceAdapter
{
    /** @return mixed */
    public function fetch(): mixed;

    /** @return list<NormalizedItem> */
    public function normalize(mixed $payload): array;

    public function source(): string;
}