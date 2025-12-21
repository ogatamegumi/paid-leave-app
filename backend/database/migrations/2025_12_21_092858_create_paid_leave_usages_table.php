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
        Schema::create('paid_leave_usages', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('paid_leave_grant_id')
                ->constrained('paid_leave_grants')
                ->cascadeOnDelete()
                ->comment('有給付与ID');
            
            $table->foreignId('paid_leave_request_id')
                ->constrained('paid_leave_requests')
                ->cascadeOnDelete()
                ->comment('有給申請ID');;    
            
            $table->decimal('used_days', 5, 2)
                ->comment('使用した日数');; 

            $table->timestamp('created_at')->useCurrent();
            
            $table->unique([
                'paid_leave_grant_id',
                'paid_leave_request_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paid_leave_usages');
    }
};
