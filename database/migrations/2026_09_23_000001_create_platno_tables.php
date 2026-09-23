<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Refuse partial installations or unrelated tables before any DDL.
        foreach (['platno_pages', 'platno_publications'] as $table) {
            if (Schema::hasTable($table)) {
                throw new RuntimeException("Table [{$table}] already exists without this migration. No tables were changed.");
            }
        }

        Schema::create('platno_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 200);
            $table->json('document');
            $table->unsignedInteger('revision')->default(1);
            $table->unsignedBigInteger('publication_id')->nullable();
            $table->timestamps();
        });

        Schema::create('platno_publications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained('platno_pages')->restrictOnDelete();
            $table->string('slug', 120);
            $table->string('title', 200);
            $table->json('document');
            $table->unsignedInteger('source_revision');
            $table->timestamp('created_at');
            $table->unique(['page_id', 'source_revision']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('platno_publications');
        Schema::dropIfExists('platno_pages');
    }
};
