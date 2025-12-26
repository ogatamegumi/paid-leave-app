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
        Schema::table('paid_leave_grants', function (Blueprint $table) {
            $table->integer('grant_year')
            ->comment('有給付与の回数（入社後n回目の付与）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paid_leave_grants', function (Blueprint $table) {
            $table->dropColumn(['grant_year']);
        });
    }
};
