<?php

declare(strict_types=1);

namespace App\Models;

use App\Radar\Locations\LocationDatasetStatus;
use Illuminate\Database\Eloquent\Model;

class LocationDataset extends Model
{
    protected $fillable = ['provider', 'name', 'regulation_number', 'version', 'effective_date', 'published_at', 'source_url', 'source_format', 'checksum', 'status', 'metadata', 'imported_at'];

    protected function casts(): array
    {
        return ['status' => LocationDatasetStatus::class, 'metadata' => 'array', 'effective_date' => 'date', 'published_at' => 'datetime', 'imported_at' => 'datetime'];
    }

    public function locations() { return $this->hasMany(Location::class); }
    public function providerMappings() { return $this->hasMany(LocationProviderMapping::class); }
}