<?php

declare(strict_types=1);

namespace App\Radar\Locations;

enum LocationDatasetStatus: string
{
    case DISCOVERED = 'DISCOVERED';
    case VALIDATED = 'VALIDATED';
    case IMPORTED = 'IMPORTED';
    case ACTIVE = 'ACTIVE';
    case SUPERSEDED = 'SUPERSEDED';
    case FAILED = 'FAILED';
}