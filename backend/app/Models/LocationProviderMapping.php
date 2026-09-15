<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationProviderMapping extends Model
{
    protected $fillable = ['location_id', 'provider', 'provider_code', 'provider_level', 'location_dataset_id', 'is_active', 'metadata'];
    protected function casts(): array { return ['is_active' => 'boolean', 'metadata' => 'array']; }
    public function location() { return $this->belongsTo(Location::class); }
    public function dataset() { return $this->belongsTo(LocationDataset::class, 'location_dataset_id'); }
}