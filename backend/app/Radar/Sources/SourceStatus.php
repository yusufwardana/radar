<?php

declare(strict_types=1);

namespace App\Radar\Sources;

enum SourceStatus: string
{
    case ACTIVE = 'ACTIVE';
    case PAUSED = 'PAUSED';
    case DEGRADED = 'DEGRADED';
    case FAILED = 'FAILED';
    case DISABLED = 'DISABLED';
}