<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signal extends Model
{
    use HasFactory;

    protected $fillable = [
        'fingerprint', 'title', 'summary', 'type', 'category', 'priority',
        'latitude', 'longitude', 'first_detected_at', 'last_updated_at',
        'confidence_score', 'importance_score', 'source_count', 'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'first_detected_at' => 'immutable_datetime',
            'last_updated_at' => 'immutable_datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function sources()
    {
        return $this->belongsToMany(Source::class, 'signal_sources')
            ->withPivot(['source_title', 'source_url', 'first_detected_at', 'last_checked_at', 'attribution', 'metadata'])
            ->withTimestamps();
    }

    public function events()
    {
        return $this->hasMany(SignalEvent::class);
    }
}