<?php

declare(strict_types=1);

namespace App\Radar\Sources;

enum SourceType: string
{
    case API = 'API';
    case OPEN_DATA = 'OPEN_DATA';
    case RSS = 'RSS';
    case ATOM = 'ATOM';
    case CAP = 'CAP';
    case SITEMAP = 'SITEMAP';
    case HTML = 'HTML';
    case BROWSER = 'BROWSER';
}