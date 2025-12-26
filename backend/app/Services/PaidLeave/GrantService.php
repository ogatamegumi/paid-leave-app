<?php

namespace App\Services\PaidLeave;

use App\Models\User;
use App\Models\PaidLeaveGrant;
use Carbon\Carbon;

class GrantService
{
  public function grantIfNeeded(User $user, ?Carbon $baseDate = null): void
  {
    $baseDate ??= now();

    foreach (config('paid_leave.grant_rules') as $grantYear => $rule) {
      $grantDate = $this->calculateGrantDate($user, $rule);

      // まだ付与日が来ていない
      if ($grantDate->isAfter($baseDate)) {
        continue;
      }

      // すでに付与済み
      if ($this->alreadyGranted($user, $grantYear)) {
        continue;
      }

      $this->createGrant(
        $user, 
        $grantDate, 
        $rule['days'], 
        'day', 
        $grantYear
      );
    }
  }

  private function calculateGrantDate(User $user, array $rule): Carbon
  {
    return $user->joined_on
      ->copy()
      ->addMonths($rule['months'])
      ->startOfDay();
  }

  private function alreadyGranted(User $user, int $grantYear): bool
  {
    return PaidLeaveGrant::where('user_id', $user->id)
      ->where('grant_year', $grantYear)
      ->exists();
  }

  private function createGrant(
    User $user,
    Carbon $grantDate,
    int $days,
    string $unit,
    int $grantYear,
  ): void {
    PaidLeaveGrant::create([
      'user_id' => $user->id,
      'start_date' => $grantDate,
      'end_date' => $grantDate->copy()->addYears(2),
      'days' => $days,
      'unit' => $unit,
      'status' => 'active',
      'grant_year' => $grantYear,
    ]);
  }
}