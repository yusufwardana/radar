<?php

declare(strict_types=1);

namespace App\Radar\Locations;

enum LocationAcquisitionStatus: string
{
    case DISCOVERED = 'DISCOVERED';
    case DOWNLOADED = 'DOWNLOADED';
    case VERIFIED = 'VERIFIED';
    case REJECTED = 'REJECTED';
    case IMPORTED = 'IMPORTED';
}