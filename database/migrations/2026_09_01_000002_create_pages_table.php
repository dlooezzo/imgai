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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug', 191)->unique()->index();
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->string('status', 20)->default('draft')->index(); // 'draft', 'published'
            $table->boolean('show_in_navigation')->default(false)->index();
            $table->string('navigation_label')->nullable();
            $table->integer('navigation_order')->default(0)->index();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
