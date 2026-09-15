<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Change extends Model
{
    protected $fillable = [
        'source_id', 'source_page_id', 'previous_snapshot_id',
        'current_snapshot_id', 'external_id', 'change_type', 'details',
    ];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }
}