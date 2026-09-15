<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('level')->index();
            $table->string('code')->unique();
            $table->string('adm1_code')->nullable()->index();
            $table->string('adm2_code')->nullable()->index();
            $table->string('adm3_code')->nullable()->index();
            $table->string('adm4_code')->nullable()->index();
            $table->string('name');
            $table->string('normalized_name')->index();
            $table->string('type')->nullable();
            $table->string('province_name')->nullable();
            $table->string('regency_name')->nullable();
            $table->string('district_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestampsTz();
            $table->index(['normalized_name', 'level']);
        });
    }

    public function down(): void { Schema::dropIfExists('locations'); }
};