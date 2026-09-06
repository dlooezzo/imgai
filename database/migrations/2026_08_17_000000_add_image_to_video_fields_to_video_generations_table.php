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
        Schema::table('video_generations', function (Blueprint $table) {
            $table->string('generation_type', 30)->default('text-to-video')->after('user_id')->index();
            $table->text('source_image_url')->nullable()->after('prompt');
            $table->text('source_image_path')->nullable()->after('source_image_url');
            $table->string('resolution', 10)->nullable()->after('aspect_ratio');
            $table->unsignedSmallInteger('num_frames')->default(81)->after('frame_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_generations', function (Blueprint $table) {
            $table->dropColumn([
                'generation_type',
                'source_image_url',
                'source_image_path',
                'resolution',
                'num_frames',
            ]);
        });
    }
};
