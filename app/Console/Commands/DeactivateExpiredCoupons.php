<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Coupon;
use Carbon\Carbon;

class DeactivateExpiredCoupons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coupons:deactivate-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate coupons that have passed their expiration date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $updated = Coupon::where('is_active', 1)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->update(['is_active' => 0]);

        $this->info("Successfully deactivated {$updated} expired coupons.");
    }
}
