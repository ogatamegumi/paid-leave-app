<?php

namespace App\Models;

use App\Models\PaidLeaveUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaidLeaveGrant extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'days',
        'unit',
        'status',
        'grant_year',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'days'       => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PaidleaveUsage::class);
    }

    public function remainingDays(): float
    {
        $usedDays = $this->usages()
            ->whereHas('request', function ($q) {
                $q->where('status', 'approved');
            })
            ->sum('used_days');

        return max(0, (float) $this->days - (float) $usedDays);
    }
}
