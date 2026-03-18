<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class Invoice extends Model
{
    use HasFactory;
    use BelongsToCompany;

    protected $fillable = [
        'invoice_id',
        'customer_id',
        'issue_date',
        'due_date',
        'send_date',
        'category_id',
        'ref_number',
        'status',
        'shipping_display',
        'discount_apply',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'send_date' => 'date',
        'status' => 'integer',
        'shipping_display' => 'integer',
        'discount_apply' => 'integer',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category()
    {
        return $this->belongsTo(ProductServiceCategory::class, 'category_id');
    }

    public function products()
    {
        return $this->hasMany(InvoiceProduct::class, 'invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class, 'invoice_id');
    }
}
