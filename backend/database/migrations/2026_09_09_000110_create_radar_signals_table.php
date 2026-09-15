<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('signals', function (Blueprint $table): void {
            $table->id();
            $table->string('fingerprint')->unique();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('type');
            $table->string('category')->nullable();
            $table->string('priority')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestampTz('first_detected_at')->index();
            $table->timestampTz('last_updated_at')->index();
            $table->unsignedTinyInteger('confidence_score');
            $table->unsignedTinyInteger('importance_score');
            $table->unsignedInteger('source_count')->default(0);
            $table->string('status')->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};