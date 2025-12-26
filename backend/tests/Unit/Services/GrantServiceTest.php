<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\PaidLeaveGrant;
use App\Services\PaidLeave\GrantService;
use App\Services\PaidLeave\PaidLeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GrantServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(PaidLeaveService::class);
    }

    /**
     * 入社日から6ヶ月後に10日の有給が付与されること
     */
    public function test_first_grant_is_created_after_6_months(): void
    {
        $user = User::factory()->create([
            'joined_on' => now()->submonth(6),
        ]);

        (new GrantService())->grantIfNeeded($user);

        $this->assertDatabaseHas('paid_leave_grants', [
            'user_id' => $user->id,
            'grant_year' => 1,
            'days' => 10,
        ]);
    }

    /**
     * 同じ有給が2回以上付与されないこと
     */
    public function test_grant_is_not_created_twice(): void
    {
        $user = User::factory()->create([
            'joined_on' => now()->subMonths(6),
        ]);

        $service = new GrantService();
        $service->grantIfNeeded($user);
        $service->grantIfNeeded($user);

        $this->assertEquals(
            1,
            PaidLeaveGrant::where('user_id', $user->id)->count()
        );
    }
}
