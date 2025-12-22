<?php

namespace Tests\Feature\Console;

use Tests\TestCase;
use App\Models\PaidLeaveGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExpirePaidLeaveGrantsTest extends TestCase
{
  use RefreshDatabase;

  /**
   * 有給失効日を過ぎたGrantは、コマンドでステータスがexpiredになること
   */
  public function test_grant_is_expired_by_command(): void
  {
    $user = User::factory()->create();
    
    $grant = PaidLeaveGrant::factory()->create([
      'user_id' => $user->id,
      'status' => 'active',
      'end_date' => now()->subDay(),
    ]);

    $this->artisan('paid-leave:expire-grants');

    $grant->refresh();
    $this->assertEquals('expired', $grant->status);
  }
}