<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['platno_assets', 'platno_asset_references'] as $table) {
            if (Schema::hasTable($table)) {
                throw new RuntimeException("Table [{$table}] already exists without this migration.");
            }
        }
        Schema::create('platno_assets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('disk', 100);
            $table->string('path');
            $table->string('name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->string('state', 20)->default('uploading')->index();
            $table->timestamps();
        });
        Schema::create('platno_asset_references', function (Blueprint $table): void {
            $table->foreignUuid('asset_id')->constrained('platno_assets')->restrictOnDelete();
            $table->string('owner_type', 20);
            $table->unsignedBigInteger('owner_id');
            $table->primary(['asset_id', 'owner_type', 'owner_id'], 'platno_asset_reference_primary');
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platno_asset_references');
        Schema::dropIfExists('platno_assets');
    }
};
