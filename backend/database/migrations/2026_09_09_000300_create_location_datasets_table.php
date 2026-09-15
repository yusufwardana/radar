<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('location_datasets', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('name');
            $table->string('regulation_number')->nullable();
            $table->string('version');
            $table->date('effective_date')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->text('source_url');
            $table->string('source_format');
            $table->string('checksum', 64)->nullable();
            $table->string('status')->index();
            $table->json('metadata')->nullable();
            $table->timestampTz('imported_at')->nullable();
            $table->timestampsTz();
            $table->unique(['provider', 'version']);
        });

        Schema::table('locations', function (Blueprint $table): void {
            $table->foreignId('location_dataset_id')->nullable()->after('id')->constrained('location_datasets')->nullOnDelete();
            $table->string('source_code')->nullable()->after('code');
            $table->unsignedInteger('source_row')->nullable()->after('source_code');
            $table->json('source_metadata')->nullable()->after('metadata');
            $table->string('status')->default('ACTIVE')->after('is_active')->index();
            $table->index(['location_dataset_id', 'status', 'level']);
        });

        Schema::create('location_provider_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_code');
            $table->string('provider_level')->nullable();
            $table->foreignId('location_dataset_id')->nullable()->constrained('location_datasets')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->unique(['provider', 'provider_code']);
            $table->index(['provider', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_provider_mappings');
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropForeign(['location_dataset_id']);
            $table->dropColumn(['location_dataset_id', 'source_code', 'source_row', 'source_metadata', 'status']);
        });
        Schema::dropIfExists('location_datasets');
    }
};