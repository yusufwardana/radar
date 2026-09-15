<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('source_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('canonical_url');
            $table->string('title')->nullable();
            $table->string('status')->default('ACTIVE')->index();
            $table->timestampsTz();
            $table->unique(['source_id', 'canonical_url']);
        });

        Schema::create('fetch_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_page_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->string('content_type')->nullable();
            $table->unsignedBigInteger('response_size')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampTz('fetched_at')->index();
            $table->timestampsTz();
        });

        Schema::create('snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_page_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('content_hash', 64);
            $table->string('normalized_hash', 64)->index();
            $table->string('title')->nullable();
            $table->longText('text_content')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->timestampTz('fetched_at')->index();
            $table->timestampsTz();
            $table->unique(['source_id', 'external_id', 'normalized_hash']);
        });

        Schema::create('changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('previous_snapshot_id')->nullable()->constrained('snapshots')->nullOnDelete();
            $table->foreignId('current_snapshot_id')->constrained('snapshots')->cascadeOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->string('change_type')->index();
            $table->json('details')->nullable();
            $table->timestampsTz();
            $table->unique(['source_id', 'current_snapshot_id', 'change_type']);
        });

        Schema::create('signal_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('signal_id')->constrained()->cascadeOnDelete();
            $table->string('event_type')->index();
            $table->text('summary');
            $table->json('metadata')->nullable();
            $table->timestampTz('occurred_at')->index();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signal_events');
        Schema::dropIfExists('changes');
        Schema::dropIfExists('snapshots');
        Schema::dropIfExists('fetch_logs');
        Schema::dropIfExists('source_pages');
    }
};