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
        Schema::create('generations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id', 191)->nullable()->index();
            $table->string('prediction_id', 191)->nullable()->index();
            $table->text('prompt');
            $table->string('aspect_ratio')->default('1:1');
            $table->unsignedTinyInteger('megapixels')->default(2);
            $table->string('output_format', 10)->default('jpg');
            $table->unsignedTinyInteger('output_quality')->default(80);
            $table->bigInteger('seed')->nullable();
            $table->boolean('juiced')->default(false);
            $table->string('model_version')->default('16e15e913fcc71c1a5defb335ea84739f99731fa1ee17995117c7d9adc6d176c');
            $table->string('status', 30)->default('starting'); // starting, processing, succeeded, failed
            $table->text('image_path')->nullable();
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
        Schema::dropIfExists('generations');
    }
};
