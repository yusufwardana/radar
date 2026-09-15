<?php

declare(strict_types=1);

namespace App\Radar\Locations;

enum LocationImportBatchStatus: string
{
    case PENDING = 'PENDING';
    case PARSING = 'PARSING';
    case VALIDATING = 'VALIDATING';
    case READY = 'READY';
    case FAILED = 'FAILED';
    case ACTIVATED = 'ACTIVATED';
}