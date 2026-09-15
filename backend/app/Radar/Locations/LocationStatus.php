<?php

declare(strict_types=1);

namespace App\Radar\Locations;

enum LocationStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case SUPERSEDED = 'SUPERSEDED';
}