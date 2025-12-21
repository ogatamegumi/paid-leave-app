<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\PaidLeaveGrant;
use App\Models\PaidLeaveRequest;
use App\Models\PaidLeaveUsage;
use App\Services\PaidLeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaidLeaveServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaidLeaveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(PaidLeaveService::class);
    }

    /**
     * 有効期限内の有給がある状態で3日の有給を申請したとき、正しく処理できること
     * @group create
     */
    public function test_creates_a_paid_leave_request(): void
    {
        $user = User::factory()->create();

        $grant = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 5,
            'unit' => 'day',
            'status' => 'approved',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(10),
        ]);
    
        // 実行
        $request = $this->service->createRequest(
            $user,
            3,
            'day',
            now(),
            now()->addDays(2)
        );

        // 検証
        $grant->refresh();
        $this->assertEquals(5, $grant->remainingDays());
        $this->assertDatabaseHas('paid_leave_requests', [
            'user_id' => $user->id,
            'requested_days' => 3,
            'unit' => 'day',
            'status' => 'pending',
        ]);
        $this->assertEquals('pending', $request->status);
    }

    /**
     * 0日の有給を申請したときにエラーが起こること
     * @group create
     */
    public function test_throws_exception_when_requested_days_is_zero(): void
    {
        $user = User::factory()->create();

        $grant = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 5,
            'unit' => 'day',
            'status' => 'approved',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(10),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('申請された日数が0日です。1日以上の有給を申請してください。');

        // 実行
        $this->service->createRequest(
            $user,
            0,
            'day',
            now(),
            now()->addDay()
        );
    }

    /**
     * 有効な有給の残日数が5日の状態で15日の有給を申請したときに、エラーが起こること
     * @group create
     */
    public function test_throws_exception_when_remaining_is_less_than_requested(): void
    {
        $user = User::factory()->create();

        $grant = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 5,
            'unit' => 'day',
            'status' => 'approved',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(10),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/残りの有給は\d+日だけです。日数を調整してください/');

        $this->service->createRequest(
            $user,
            15,
            'day',
            now(),
            now()->addDay()
        );
    }

    /** 
     * 複数の有給にまたがる申請が承認されたとき、より前の有給から割り当てられること（前の有給5日と最新の有給3日が残っている状態で6日の有給が承認されたとき、前の有給の残数が0日、最新の有給の残数が2日になること）
     * @group approve
    */
    public function test_assigns_request_to_grants_and_updates_usages(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $grant1 = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 5,
            'unit' => 'day',
            'status' => 'approved',
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $grant2 = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 3,
            'unit' => 'day',
            'status' => 'approved',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(10),
        ]);

        $request = PaidLeaveRequest::factory()->create([
            'user_id' => $user->id,
            'requested_days' => 6,
            'unit' => 'day',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
        ]);

        // 実行
        $this->service->approveRequest($request, $admin);

        // 検証
        $request->refresh();

        $this->assertEquals('approved', $request->status);
        $this->assertEquals($admin->id, $request->approved_by);
        $this->assertNotNull($request->approved_at);

        $this->assertDatabaseHas('paid_leave_usages', [
            'paid_leave_grant_id' => $grant1->id,
            'paid_leave_request_id' => $request->id,
            'used_days' => 5,
        ]);
        $this->assertDatabaseHas('paid_leave_usages', [
            'paid_leave_grant_id' => $grant2->id,
            'paid_leave_request_id' => $request->id,
            'used_days' => 1,
        ]);
        $this->assertEquals(0, $grant1->remainingDays());
        $this->assertEquals(2, $grant2->remainingDays());
    }

    /**
     * 有効期限内の有給が2日残っている状態で5日の有給が申請されたとき、エラーが発生すること
     * @group approve
     */
    public function test_throws_exception_when_grants_are_insufficient(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $grant = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 2,
            'unit' => 'day',
            'status' => 'approved',
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(5),
        ]);

        $request = PaidLeaveRequest::factory()->create([
            'user_id' => $user->id,
            'requested_days' => 5,
            'unit' => 'day',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('有給の残り日数が足りません。申請を修正してもらってください');

        try {
            // 実行
            $this->service->approveRequest($request, $admin);
        } catch (\Exception $e) {
            // 検証
            $request->refresh();
            $this->assertEquals('pending', $request->status);
            $this->assertDatabaseCount('paid_leave_usages', 0);
            $this->assertEquals(2, $grant->remainingDays());
            throw $e;
        }
    }

    /**
     * 期限切れの有給5日と有効期限内の有給3日が残っている状態で3日の有給が承認されたとき、期限切れの有給の残数が5日、有効期限内の有給の残数が0日になること
     * @group approve
     */
    public function test_uses_only_grants_currently_valid(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $pastGrant = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 5,
            'unit' => 'day',
            'status' => 'approved',
            'start_date' => now()->subDays(20),
            'end_date' => now()->subDays(10),
        ]);

        $validGrant = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 3,
            'unit' => 'day',
            'status' => 'approved',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
        ]);

        $request = PaidLeaveRequest::factory()->create([
            'user_id' => $user->id,
            'requested_days' => 3,
            'unit' => 'day',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addDays(2),
        ]);

        // 実行
        $this->service->approveRequest($request, $admin);

        // 検証
        $request->refresh();

        $this->assertEquals('approved', $request->status);
        $this->assertEquals($admin->id, $request->approved_by);
        $this->assertNotNull($request->approved_at);

        $this->assertDatabaseMissing('paid_leave_usages', [
            'paid_leave_grant_id' => $pastGrant->id,
            'paid_leave_request_id' => $request->id,
        ]);
        $this->assertDatabaseHas('paid_leave_usages', [
            'paid_leave_grant_id' => $validGrant->id,
            'paid_leave_request_id' => $request->id,
            'used_days' => 3,
        ]);
        $this->assertEquals(5, $pastGrant->remainingDays());
        $this->assertEquals(0, $validGrant->remainingDays());
    }

    /**
     * 0日の申請が承認されたとき、残日数が変わらずエラーも起こさないこと
     * @group approve
     */
    public function test_handles_zero_day_request(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $grant = PaidLeaveGrant::factory()->create([
            'user_id' => $user->id,
            'days' => 5,
            'unit' => 'day',
            'status' => 'approved',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
        ]);

        $request = PaidLeaveRequest::factory()->create([
            'user_id' => $user->id,
            'requested_days' => 0,
            'unit' => 'day',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addDays(1),
        ]);

        // 実行
        $this->service->approveRequest($request, $admin);

        // 検証
        $request->refresh();
        
        $this->assertEquals('approved', $request->status);
        $this->assertEquals($admin->id, $request->approved_by);
        $this->assertNotNull($request->approved_at);
        $this->assertDatabaseCount('paid_leave_usages', 0);
        $this->assertEquals(5, $grant->remainingDays());
    }

    /**
     * 他のユーザーの有給を使わないこと
     * @group approve
     */
    public function test_does_not_use_another_users_grants(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $admin = User::factory()->create();

        $grantA = PaidLeaveGrant::factory()->create([
            'user_id' => $userA->id,
            'days' => 5,
            'unit' => 'day',
            'status' => 'approved',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
        ]);

        $grantB = PaidLeaveGrant::factory()->create([
            'user_id' => $userB->id,
            'days' => 3,
            'unit' => 'day',
            'status' => 'approved',
            'start_date' => now()->subDays(3),
            'end_date' => now()->addDays(3),
        ]);

        $requestB = PaidLeaveRequest::factory()->create([
            'user_id' => $userB->id,
            'requested_days' => 2,
            'unit' => 'day',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addDays(2),
        ]);

        // 実行
        $this->service->approveRequest($requestB, $admin);

        // 検証
        $requestB->refresh();

        $this->assertEquals('approved', $requestB->status);
        $this->assertEquals($admin->id, $requestB->approved_by);
        $this->assertNotNull($requestB->approved_at);

        $this->assertDatabaseHas('paid_leave_usages', [
            'paid_leave_grant_id' => $grantB->id,
            'paid_leave_request_id' => $requestB->id,
            'used_days' => 2,
        ]);
        $grantA->refresh();
        $this->assertEquals(5, $grantA->remainingDays());
        $grantB->refresh();
        $this->assertEquals(1, $grantB->remainingDays());
    }

    /**
     * pendingの申請が却下できること
     * @group reject
     */
    public function test_rejects_pending_request(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();

        $request = PaidLeaveRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // 実行
        $this->service->rejectRequest($request, $approver, '理由テスト');

        // 検証
        $request->refresh();
        $this->assertEquals('rejected', $request->status);
        $this->assertEquals($approver->id, $request->approved_by);
        $this->assertNotNull($request->approved_at);

        // 理由の追記を使う場合
        // $this->assertStringContainsString('理由テスト', $request->reason);
    }

    /**
     * 承認済みの申請は却下できないこと
     * @group reject
     */
    public function test_throws_exception_if_request_already_approved_or_rejected(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();

        $request = PaidLeaveRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('承認できません。すでに承認しているか却下済みです。');

        // 実行
        $this->service->rejectRequest($request, $approver, '理由テスト');
    }
}
