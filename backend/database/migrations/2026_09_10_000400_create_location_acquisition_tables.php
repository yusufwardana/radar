<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('location_dataset_acquisitions', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('regulation_number')->nullable();
            $table->string('dataset_name');
            $table->text('source_url');
            $table->text('download_url')->nullable();
            $table->string('content_type')->nullable();
            $table->string('source_format');
            $table->timestampTz('retrieved_at')->nullable();
            $table->string('sha256', 64);
            $table->unsignedBigInteger('file_size');
            $table->string('http_etag')->nullable();
            $table->string('http_last_modified')->nullable();
            $table->string('status')->index();
            $table->text('stored_path');
            $table->json('notes')->nullable();
            $table->timestampsTz();
            $table->unique(['provider', 'sha256']);
        });

        Schema::create('location_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('location_dataset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('acquisition_id')->nullable()->constrained('location_dataset_acquisitions')->nullOnDelete();
            $table->string('status')->index();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->unsignedBigInteger('processed')->default(0);
            $table->unsignedBigInteger('valid')->default(0);
            $table->unsignedBigInteger('invalid')->default(0);
            $table->unsignedBigInteger('duplicates')->default(0);
            $table->unsignedBigInteger('orphans')->default(0);
            $table->unsignedBigInteger('warnings')->default(0);
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
        });

        Schema::create('location_staging_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('location_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('source_code')->nullable();
            $table->text('source_name')->nullable();
            $table->string('source_level')->nullable();
            $table->string('source_parent_code')->nullable();
            $table->string('normalized_code')->nullable();
            $table->text('normalized_name')->nullable();
            $table->string('validation_status')->index();
            $table->json('validation_errors')->nullable();
            $table->timestampsTz();
            $table->index(['batch_id', 'normalized_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_staging_rows');
        Schema::dropIfExists('location_import_batches');
        Schema::dropIfExists('location_dataset_acquisitions');
    }
};