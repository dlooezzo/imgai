<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tool_articles', function (Blueprint $table) {
            $table->id();
            $table->string('tool_key', 50)->unique()->index(); // 'image-generator', 'video-generator', 'image-to-video'
            $table->string('title');
            $table->string('slug')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content_html');
            $table->string('featured_image')->nullable();
            $table->string('status', 20)->default('draft')->index(); // 'draft', 'published'
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_articles');
    }
};
