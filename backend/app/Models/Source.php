<?php

declare(strict_types=1);

namespace App\Models;

use App\Radar\Sources\SourceStatus;
use App\Radar\Sources\SourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'provider', 'source_type', 'base_url', 'endpoint',
        'authentication_type', 'poll_interval_seconds', 'rate_limit',
        'location_scope', 'categories', 'terms_url', 'attribution', 'license',
        'robots_status', 'crawl_allowed', 'status', 'last_fetch_at',
        'last_success_at', 'last_failure_at', 'failure_count', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => SourceType::class,
            'status' => SourceStatus::class,
            'categories' => 'array',
            'metadata' => 'array',
            'crawl_allowed' => 'boolean',
            'last_fetch_at' => 'immutable_datetime',
            'last_success_at' => 'immutable_datetime',
            'last_failure_at' => 'immutable_datetime',
        ];
    }
}