<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\PaidLeaveGrant;
use App\Models\PaidLeaveRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'joined_on',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'joined_on'         => 'date',
        ];
    }

    public function paidLeaveGrants(): HasMany
    {
        return $this->hasMany(paidLeaveGrants::class);
    }

    public function paidLeaveRequests(): HasMany
    {
        return $this->hasMany(paidLeaveRequest::class);
    }

    public function remainingPaidLeaveDays(): float
    {
        return PaidLeaveGrant::where('user_id', $this->id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->get()
            ->sum(fn ($grant) => $grant->remainingDays());
    }
}
