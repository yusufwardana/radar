<?php

declare(strict_types=1);

namespace App\Models;

use App\Radar\Locations\LocationLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'location_dataset_id', 'level', 'code', 'source_code', 'source_row', 'source_metadata', 'adm1_code', 'adm2_code', 'adm3_code', 'adm4_code', 'name', 'normalized_name', 'type', 'province_name', 'regency_name', 'district_name', 'latitude', 'longitude', 'timezone', 'metadata', 'is_active', 'status'];

    protected function casts(): array
    {
        return ['level' => LocationLevel::class, 'metadata' => 'array', 'source_metadata' => 'array', 'is_active' => 'boolean', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function dataset() { return $this->belongsTo(LocationDataset::class, 'location_dataset_id'); }
    public function providerMappings() { return $this->hasMany(LocationProviderMapping::class); }

    public function getForecastReadyAttribute(): bool
    {
        return $this->is_active && $this->status === 'ACTIVE' && in_array($this->level, [LocationLevel::VILLAGE, LocationLevel::KELURAHAN, LocationLevel::ADM4], true) && $this->providerMappings()->where('provider', 'BMKG')->where('provider_level', 'ADM4')->where('is_active', true)->exists();
    }
}