<?php

declare(strict_types=1);

namespace App\Radar\Locations;

enum LocationLevel: string
{
    case COUNTRY = 'COUNTRY';
    case PROVINCE = 'PROVINCE';
    case REGENCY = 'REGENCY';
    case CITY = 'CITY';
    case DISTRICT = 'DISTRICT';
    case VILLAGE = 'VILLAGE';
    case KELURAHAN = 'KELURAHAN';
    case ADM4 = 'ADM4';
}