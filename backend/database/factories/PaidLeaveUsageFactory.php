<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PaidLeaveUsage;

class PaidLeaveUsageFactory extends Factory
{
  protected $model = PaidLeaveUsage::class;

  public function definition(): array
  {
    return [
      'paid_leave_grant_id' => null,
      'paid_leave_request_id' => null,
      'used_days' => 1,
  ];
  }
}
