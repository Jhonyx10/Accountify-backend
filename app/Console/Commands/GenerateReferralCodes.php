<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateReferralCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:generate-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate referral codes for all existing users who do not have one.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = \App\Models\User::whereNull('referral_code')
            ->orWhere('referral_code', '')
            ->orWhere('referral_code', '0')
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $user->generateReferralCode();
            $count++;
        }

        $this->info("Successfully generated referral codes for {$count} users.");
    }
}
