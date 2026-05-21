<?php
$user = App\Models\User::first();
$request = ['vender_id' => 7, 'po_date' => '2026-05-20', 'delivery_date' => '2026-05-20', 'status' => 1, 'category_id' => 0, 'shipping_display' => 1, 'discount_apply' => 0];
$items = [['product_id' => 2, 'quantity' => 2, 'price' => 150, 'tax' => 0, 'discount' => 0]];
DB::beginTransaction();
try {
    $po = App\Models\PurchaseOrder::create(array_merge($request, ['po_number' => '1', 'created_by' => $user->id]));
    echo "PO Created ID: " . $po->id . "\n";
    foreach ($items as $item) {
        App\Models\PurchaseOrderProduct::create(array_merge(['purchase_order_id' => $po->id], $item));
    }
    echo "Products created\n";
    $resource = new App\Http\Resources\POResource($po->load(['vender', 'products']));
    echo json_encode($resource);
    echo "\nSuccess\n";
} catch(\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
DB::rollBack();
