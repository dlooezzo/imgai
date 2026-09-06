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
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('pricing_plans', 'monthly_credits')) {
                $table->integer('monthly_credits')->default(0)->after('yearly_price_id');
            }
            if (!Schema::hasColumn('pricing_plans', 'yearly_credits')) {
                $table->integer('yearly_credits')->default(0)->after('monthly_credits');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (Schema::hasColumn('pricing_plans', 'monthly_credits')) {
                $table->dropColumn('monthly_credits');
            }
            if (Schema::hasColumn('pricing_plans', 'yearly_credits')) {
                $table->dropColumn('yearly_credits');
            }
        });
    }
};
