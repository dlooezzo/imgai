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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'credit_balance')) {
                $table->integer('credit_balance')->default(0)->index();
            }
            if (!Schema::hasColumn('users', 'paddle_customer_id')) {
                $table->string('paddle_customer_id', 191)->nullable()->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'credit_balance')) {
                $table->dropColumn('credit_balance');
            }
            if (Schema::hasColumn('users', 'paddle_customer_id')) {
                $table->dropColumn('paddle_customer_id');
            }
        });
    }
};
