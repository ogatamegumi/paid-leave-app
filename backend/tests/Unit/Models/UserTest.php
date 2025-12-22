<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\PaidLeaveGrant;
use App\Models\PaidLeaveRequest;
use App\Models\PaidLeaveUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * 有効期限内の10日付与されている有給のうち3日が使用済みのときに、残日数が7日になること
     */
    public function test_remaining_paid_leave_days_returns_correct_value(): void
    {
        $user = User::factory()->create();
    
        $grant = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 10,
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $request = PaidLeaveRequest::factory()->create([
            'user_id' => $user->id,
            'requested_days' => 3,
            'unit' => 'day',
            'status' => 'approved',
            'start_date' => now(),
            'end_date' => now(),
        ]);
    
        PaidLeaveUsage::factory()->create([
            'paid_leave_grant_id' => $grant->id,
            'paid_leave_request_id' => $request->id,
            'used_days' => 3,
        ]);
    
        $this->assertEquals(7, $user->remainingPaidLeaveDays());
    }
    
    /**
     * 付与された有給が残っていても、期限切れであればカウントされないこと
     */
    public function test_expired_grant_is_not_counted_in_remaining_paid_leave_days(): void
    {
        $user = User::factory()->create();

        PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 5,
            'status' => 'active',
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 10,
            'status' => 'active',
            'start_date' => now()->subDays(20),
            'end_date' => now()->subDay(),
        ]);

        $this->assertEquals(5, $user->remainingPaidLeaveDays());
    }

    /**
     * 未来に付与される有給はカウントされないこと
     */
    public function test_future_grant_is_not_counted(): void
    {
        $user = User::factory()->create();

        PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 8,
            'status' => 'active',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(10),
        ]);

        $this->assertEquals(0, $user->remainingPaidLeaveDays());
    }

    /**
     * 複数の付与を跨いで有給を使用したとき、複数の付与の合計日数から使用した日数が引かれること
     */
    public function test_remaining_paid_leave_days_with_multiple_grants(): void
    {
        $user = User::factory()->create();

        // 5日付与
        $grant1 = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 5,
            'status' => 'active',
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        // 10日付与
        $grant2 = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 10,
            'status' => 'active',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(10),
        ]);

        // 7日使用
        $request = PaidLeaveRequest::factory()->create([
            'user_id' => $user->id,
            'requested_days' => 7,
            'status' => 'approved',
            'start_date' => now(),
            'end_date' => now(),
        ]);

        // 7日分の5日使用
        PaidLeaveUsage::factory()->create([
            'paid_leave_grant_id' => $grant1->id,
            'paid_leave_request_id' => $request->id,
            'used_days' => 5,
        ]);

        // 7日分の2日使用
        PaidLeaveUsage::factory()->create([
            'paid_leave_grant_id' => $grant2->id,
            'paid_leave_request_id' => $request->id,
            'used_days' => 2,
        ]);

        // 8日残る
        $this->assertEquals(8, $user->remainingPaidLeaveDays());
    }

    /**
     * 期限の切れた有給は残り日数にカウントされないこと
     */
    public function test_expired_grant_has_zero_remaining_days(): void
    {
        $user = User::factory()->create();

        $grant = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 10,
            'start_date' => now()->subDays(20),
            'end_date' => now()->subDay(),
        ]);

        $this->assertEquals(0, $grant->remainingDays());
    }
}
