<?php

use App\Radar\Sources\SourceStatus;
use App\Radar\Sources\SourceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('provider');
            $table->string('source_type')->default(SourceType::API->value);
            $table->string('base_url')->nullable();
            $table->string('endpoint')->nullable();
            $table->string('authentication_type')->nullable();
            $table->unsignedInteger('poll_interval_seconds')->default(3600);
            $table->unsignedInteger('rate_limit')->nullable();
            $table->string('location_scope')->nullable();
            $table->json('categories')->nullable();
            $table->string('terms_url')->nullable();
            $table->text('attribution')->nullable();
            $table->string('license')->nullable();
            $table->string('robots_status')->nullable();
            $table->boolean('crawl_allowed')->default(false);
            $table->string('status')->default(SourceStatus::ACTIVE->value)->index();
            $table->timestampTz('last_fetch_at')->nullable();
            $table->timestampTz('last_success_at')->nullable();
            $table->timestampTz('last_failure_at')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};