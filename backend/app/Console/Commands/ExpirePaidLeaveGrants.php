<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaidLeaveGrant;

class ExpirePaidLeaveGrants extends Command
{
    /**
     * @var string
     */
    protected $signature = 'paid-leave:expire-grants';

    /**
     * @var string
     */
    protected $description = 'Command description';

    public function handle(): int
    {
        PaidLeaveGrant::where('status', 'active')
            ->whereDate('end_date', '<', now())
            ->update(['status' => 'expired']);
        
        return self::SUCCESS;
    }
}
