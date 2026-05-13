<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$role = new App\Models\Role();
echo "New Role Guard: " . var_export($role->guard_name, true) . PHP_EOL;

$guard = array_key_exists('guard_name', $role->getAttributes()) ? $role->getAttributes()['guard_name'] : null;
$guard = $guard ?? config('auth.defaults.guard');
echo "Eval guard: " . var_export($guard, true) . PHP_EOL;
echo "Model for above guard: " . var_export(\Spatie\Permission\Guard::getModelForGuard($guard), true) . PHP_EOL;

try {
    $role->users();
    echo "SUCCESS: $role->users() ran successfully without null model class error!" . PHP_EOL;
} catch (\Exception $e) {
    echo "Caught exception: " . $e->getMessage() . PHP_EOL;
}


