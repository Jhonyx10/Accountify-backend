<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Http\Resources\UserResource;

try {
    $query = User::where('type', 'company');
    $companies = $query->with(['currentPlan'])->get();
    $resource = UserResource::collection($companies);
    echo json_encode($resource->resolve(), JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}
