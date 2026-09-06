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
        Schema::create('video_generations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id', 191)->nullable()->index();
            $table->string('prediction_id', 191)->nullable()->index();
            $table->text('prompt');
            $table->string('aspect_ratio', 10)->default('1:1');
            $table->unsignedSmallInteger('width')->default(768);
            $table->unsignedSmallInteger('height')->default(768);
            $table->unsignedTinyInteger('steps')->default(30);
            $table->unsignedTinyInteger('crf')->default(19);
            $table->unsignedTinyInteger('flow_shift')->default(9);
            $table->unsignedTinyInteger('frame_rate')->default(24);
            $table->decimal('guidance_scale', 4, 1)->default(6.0);
            $table->decimal('denoise_strength', 4, 2)->default(0.85);
            $table->string('model_version')->default('d550f226f28b1030c2fedd2947f39f19b4b0233b50364904538caaf037fb18d3');
            $table->string('status', 30)->default('starting'); // starting, processing, succeeded, failed, cancelled
            $table->text('video_path')->nullable();
            $table->text('remote_url')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_generations');
    }
};
