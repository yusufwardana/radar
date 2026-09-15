<?php

declare(strict_types=1);

namespace App\Models;

use App\Radar\Locations\LocationImportBatchStatus;
use Illuminate\Database\Eloquent\Model;

class LocationImportBatch extends Model
{
    protected $fillable = ['location_dataset_id', 'acquisition_id', 'status', 'started_at', 'completed_at', 'processed', 'valid', 'invalid', 'duplicates', 'orphans', 'warnings', 'duration_ms', 'metadata'];
    protected function casts(): array { return ['status' => LocationImportBatchStatus::class, 'metadata' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime']; }
    public function rows() { return $this->hasMany(LocationStagingRow::class, 'batch_id'); }
    public function dataset() { return $this->belongsTo(LocationDataset::class, 'location_dataset_id'); }
}