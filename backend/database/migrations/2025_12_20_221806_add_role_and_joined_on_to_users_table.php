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

            // 権限（業務ロール）
            $table->string('role', 20)
                  ->nullable(false)
                  ->default('member')
                  ->comment('権限（admin / member）');

            // 入社日（未設定の可能性あり）
            $table->date('joined_on')
                  ->nullable()
                  ->comment('入社日');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'joined_on']);
        });
    }
};
