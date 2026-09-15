<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationStagingRow extends Model
{
    protected $fillable = ['batch_id', 'row_number', 'source_code', 'source_name', 'source_level', 'source_parent_code', 'normalized_code', 'normalized_name', 'validation_status', 'validation_errors'];
    protected function casts(): array { return ['validation_errors' => 'array']; }
}