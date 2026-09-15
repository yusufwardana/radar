<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FetchLog extends Model
{
    protected $fillable = [
        'source_id', 'source_page_id', 'status', 'http_status', 'latency_ms',
        'error_code', 'error_message', 'attempt', 'content_type', 'response_size',
        'started_at', 'finished_at', 'fetched_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'fetched_at' => 'immutable_datetime',
        ];
    }
}