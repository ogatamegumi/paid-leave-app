<?php

namespace App\Services;

use App\Models\User;
use App\Models\PaidLeaveGrant;
use App\Models\PaidLeaveRequest;
use App\Models\PaidLeaveUsage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaidLeaveService
{
  public function createRequest(
    User $user, 
    int $requestedDays, 
    string $unit, 
    Carbon $startDate, 
    Carbon $endDate, 
    ?string $reason = null
  ): PaidLeaveRequest
  {
    return DB::transaction(function () use ($user, $requestedDays, $unit, $startDate, $endDate, $reason) {
      if ($requestedDays <= 0) {
        throw new \InvalidArgumentException('申請された日数が0日です。1日以上の有給を申請してください。');
      }

      [$grants, $totalAvailable] = $this->getAvailableGrantsAndTotal($user->id);

      if ($requestedDays > $totalAvailable) {
        throw new \Exception('残りの有給は' . $totalAvailable . '日だけです。日数を調整してください。');
      }

      return PaidLeaveRequest::create([
        'user_id' => $user->id,
        'requested_days' => $requestedDays,
        'unit' => $unit,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'status' =>'pending',
        'reason' => $reason, 
      ]);
    });
  }

  public function approveRequest(PaidLeaveRequest $request, User $approver): void
  {
    if ($request->status !== 'pending') {
      throw new \Exception('承認できません。すでに承認しているか却下済みです。');
    }

    DB::transaction(function () use ($request, $approver) {

      $remaining = $request->requested_days;

      [$grants, $totalAvailable] = $this->getAvailableGrantsAndTotal($request->user_id);

      if ($remaining > $totalAvailable) {
        throw new \Exception('有給の残り日数が足りません。申請を修正してもらってください。');
      }

      $request->status = 'approved';
      $request->approved_at = now();
      $request->approved_by = $approver->id;
      $request->save();

      foreach ($grants as $grant) {
        $grantRemaining = $grant->remainingDays();
        if ($grantRemaining <= 0) continue;

        $use = min($grantRemaining, $remaining);
        if ($use > 0) {
          PaidLeaveUsage::create([
            'paid_leave_grant_id' => $grant->id,
            'paid_leave_request_id' => $request->id,
            'used_days' =>$use,
          ]);
        }

        $remaining -= $use;
        if ($remaining <= 0) break;
      }
    });
  }

  public function rejectRequest(
    PaidLeaveRequest $request, 
    User $approver, 
    ?string $reason = null
  ): void
  {
    if ($request->status !== 'pending') {
      throw new \Exception('却下できません。すでに承認または却下しています。');
    }

    DB::transaction(function () use ($request, $approver, $reason) {
      $request->status = 'rejected';
      $request->approved_at = now();
      $request->approved_by = $approver->id;
      $request->reason = $reason;
      $request->save();
    });
  }

  private function getAvailableGrantsAndTotal(int $userId): array
  {
    $grants = PaidLeaveGrant::where('user_id', $userId)
      ->where('status', 'approved')
      ->whereDate('start_date', '<=', now())
      ->orderBy('start_date')
      ->get();

    $totalAvailable = $grants->sum(fn(PaidLeaveGrant $grant) => $grant->remainingDays());

    return [$grants, $totalAvailable];
  }
}
