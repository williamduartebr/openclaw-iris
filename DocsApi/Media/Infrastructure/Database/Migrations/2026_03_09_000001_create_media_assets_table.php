<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('status', 30)->default('pending');
            $table->string('provider', 50);
            $table->string('model', 100);
            $table->text('prompt');
            $table->text('negative_prompt')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('style', 50)->nullable();
            $table->string('quality', 50)->nullable();
            $table->string('original_path', 500)->nullable();
            $table->string('processed_path', 500)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedBigInteger('original_size')->nullable();
            $table->decimal('compression_ratio', 6, 4)->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->json('orchestrator_context')->nullable();
            $table->json('provider_metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_media_assets_status');
            $table->index('provider', 'idx_media_assets_provider');
            $table->index('created_at', 'idx_media_assets_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
