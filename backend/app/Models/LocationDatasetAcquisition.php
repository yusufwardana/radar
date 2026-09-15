<?php

declare(strict_types=1);

namespace App\Models;

use App\Radar\Locations\LocationAcquisitionStatus;
use Illuminate\Database\Eloquent\Model;

class LocationDatasetAcquisition extends Model
{
    protected $fillable = ['provider', 'regulation_number', 'dataset_name', 'source_url', 'download_url', 'content_type', 'source_format', 'retrieved_at', 'sha256', 'file_size', 'http_etag', 'http_last_modified', 'status', 'stored_path', 'notes'];
    protected function casts(): array { return ['status' => LocationAcquisitionStatus::class, 'notes' => 'array', 'retrieved_at' => 'datetime']; }
    public function batches() { return $this->hasMany(LocationImportBatch::class, 'acquisition_id'); }
}