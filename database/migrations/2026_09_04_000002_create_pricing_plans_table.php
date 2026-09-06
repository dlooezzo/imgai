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
        if (!Schema::hasTable('pricing_plans')) {
            Schema::create('pricing_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug', 191)->unique();
                $table->text('description')->nullable();
                $table->string('badge')->nullable(); // e.g. "Studio Choice", "Most Popular"
                $table->string('monthly_price')->nullable(); // e.g. "$19"
                $table->string('yearly_price')->nullable(); // e.g. "$190"
                $table->string('monthly_price_id')->nullable();
                $table->string('yearly_price_id')->nullable();
                $table->json('features')->nullable(); // Array of feature strings
                $table->boolean('is_popular')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->string('button_text')->default('Subscribe');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_plans');
    }
};
