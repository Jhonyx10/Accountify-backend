<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $request = Illuminate\Http\Request::create('/api/roles', 'GET');
    $user = App\Models\User::first();
    // Simulate Sanctum Auth
    $request->setUserResolver(function() use ($user) { return $user; });
    app()->bind('request', function() use ($request) { return $request; });
    
    $controller = app()->make(App\Http\Controllers\Api\RoleController::class);
    $response = $controller->index($request);
    
    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
} catch (\Error $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
