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
        // 確定した事実・履歴を管理するテーブル
        Schema::create('paid_leave_grants', function (Blueprint $table) {
            $table->id();

            // 付与対象ユーザー
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // どの申請から生まれた付与か（手動付与は null）
            $table->foreignId('paid_leave_request_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            // 有効期間
            $table->date('start_date')
                  ->nullable(false)
                  ->comment('有給利用開始日');

            $table->date('end_date')
                  ->nullable(false)
                  ->comment('有給失効日');

            // 付与日数
            $table->decimal('days', 5, 2)
                  ->nullable(false)
                  ->comment('付与された有給日数');

            // 単位
            $table->string('unit', 10)
                  ->nullable(false)
                  ->comment('有給単位（day / half / hour）');

            // 付与状態
            $table->string('status', 20)
                  ->nullable(false)
                  ->default('active')
                  ->comment('付与ステータス（active / expired / revoked）');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paid_leave_grants');
    }
};
