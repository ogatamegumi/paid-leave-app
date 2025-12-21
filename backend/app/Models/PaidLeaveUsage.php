<?php

namespace App\Models;

use App\Models\PaidLeaveGrant;
use App\Models\PaidLeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaidLeaveUsage extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'paid_leave_grant_id',
        'paid_leave_request_id',
        'used_days',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'used_days'       => 'decimal:2',
        ];
    }

    public function requests(): BelongsTo
    {
        return $this->belongsTo(PaidleaveRequest::class);
    }

    public function grants(): BelongsTo
    {
        return $this->belongsTo(PaidleaveGrant::class);
    }
}
