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
        // 意思・ワークフローを管理するテーブル
        Schema::create('paid_leave_requests', function (Blueprint $table) {
            $table->id();

            // 申請者
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // 申請日数（0.5日などを想定）
            $table->decimal('requested_days', 5, 2)
                  ->nullable(false)
                  ->comment('申請した有給日数（0.5日など）');

            // 単位（day / half / hour）
            $table->string('unit', 10)
                  ->nullable(false)
                  ->comment('有給の単位（day / half / hour）');

            // 休暇期間
            $table->date('start_date')
                  ->nullable(false)
                  ->comment('休暇開始日');

            $table->date('end_date')
                  ->nullable(false)
                  ->comment('休暇終了日');

            // 申請ステータス
            $table->string('status', 20)
                  ->nullable(false)
                  ->default('pending')
                  ->comment('申請ステータス（pending / approved / rejected / cancelled）');

            // 申請理由（任意）
            $table->text('reason')
                  ->nullable()
                  ->comment('申請理由');

            // 承認情報
            $table->timestamp('approved_at')
                  ->nullable()
                  ->comment('承認日時');

            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('承認者ユーザーID');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paid_leave_requests');
    }
};
