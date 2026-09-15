<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Snapshot extends Model
{
    protected $fillable = [
        'source_id', 'source_page_id', 'external_id', 'canonical_url',
        'content_hash', 'normalized_hash', 'title', 'text_content', 'metadata',
        'http_status', 'fetched_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'fetched_at' => 'immutable_datetime'];
    }
}