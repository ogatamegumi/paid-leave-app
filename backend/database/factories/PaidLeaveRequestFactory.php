<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PaidLeaveRequest;

class PaidLeaveRequestFactory extends Factory
{
  protected $model = PaidLeaveRequest::class;

  public function definition(): array
  {
    return [
      'user_id' => null,
      'requested_days' => fake()->numberBetween(1, 5),
      'unit' => 'day',
      'status' => 'pending',
      'start_date' => now(),
      'end_date' => now()->addDays(5),
    ];
  }
}
