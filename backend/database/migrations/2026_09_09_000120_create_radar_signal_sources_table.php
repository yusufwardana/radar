<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('signal_sources', function (Blueprint $table): void {
            $table->foreignId('signal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('source_title')->nullable();
            $table->text('source_url')->nullable();
            $table->timestampTz('first_detected_at')->nullable();
            $table->timestampTz('last_checked_at')->nullable();
            $table->text('attribution')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->primary(['signal_id', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signal_sources');
    }
};