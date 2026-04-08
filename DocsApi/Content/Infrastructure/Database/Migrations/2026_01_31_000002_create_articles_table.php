<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique();
            $table->string('title');
            $table->string('subtitle', 255)->nullable();
            $table->string('slug')->unique();
            $table->string('full_url')->nullable();
            $table->text('excerpt');
            $table->longText('content');
            $table->string('status', 20)->default('draft');

            // AI Review system columns
            $table->boolean('needs_review')->default(false);
            $table->boolean('is_reviewed')->default(false);
            $table->timestamp('reviewed_at')->nullable();

            $table->string('featured_image')->nullable();
            $table->unsignedBigInteger('cover_media_id')->nullable();
            $table->json('gallery_image_urls')->nullable();
            $table->json('gallery_media')->nullable();
            $table->json('video_urls')->nullable();
            $table->string('author_name')->default('Equipe Editorial');
            $table->integer('reading_time')->default(5); // em minutos
            $table->boolean('is_published')->default(false);
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->json('meta')->nullable(); // SEO metadata
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_published');
            $table->index('published_at');
            $table->index('status');
            $table->index('featured');
            $table->index('cover_media_id', 'idx_articles_cover_media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
