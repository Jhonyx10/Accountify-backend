<?php

use App\Models\Setting;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$companyId = 2;
$settings = Setting::where('created_by', $companyId)->get();

echo "Settings for Company ID {$companyId}:\n";
foreach ($settings as $setting) {
    echo "{$setting->name}: {$setting->value}\n";
}
