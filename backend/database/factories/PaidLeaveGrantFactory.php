<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PaidLeaveGrant;

class PaidLeaveGrantFactory extends Factory
{
  protected $model = PaidLeaveGrant::class;

  public function definition(): array
  {
    return [
      'user_id' => null,
      'start_date' => now()->subDays(10),
      'end_date' => now()->addDays(10),
      'days' => fake()->numberBetween(1, 10),
      'unit' => 'day',
      'status' => 'approved',
      'grant_year' => 5,
    ];
  }
}
