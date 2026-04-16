<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Support\Str;

class CouponService
{
    /**
     * Generate a unique coupon code based on the provided title/name.
     */
    public function generateUniqueCode(string $title): string
    {
        // 1. Take the first 4 letters of the title (e.g., "Anniversary" -> "ANNI")
        $prefix = Str::upper(substr(preg_replace('/[^A-Za-z0-9]/', '', $title), 0, 4));

        if (empty($prefix)) {
            $prefix = 'CODE';
        }

        // 2. Generate a random 4-character suffix
        // We use a custom string to avoid confusing characters like 1, I, 0, O
        $suffix = strtoupper(Str::password(4, letters: true, numbers: true, symbols: false, spaces: false));
        
        // 3. Combine them
        $code = $prefix . '-' . $suffix;

        // 4. Check for uniqueness (Safety first!)
        if (Coupon::where('code', $code)->exists()) {
            return $this->generateUniqueCode($title); // Retry if exists
        }

        return $code;
    }
}
