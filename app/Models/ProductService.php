<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CustomFieldValue;
use App\Traits\BelongsToCompany;
use App\Traits\HasCustomFields;

class ProductService extends Model
{
    use HasFactory;
    use BelongsToCompany;
    use HasCustomFields;

    protected $fillable = [
        'name',
        'sku',
        'sale_price',
        'purchase_price',
        'quantity',
        'tax_id',
        'category_id',
        'unit_id',
        'type',
        'sale_chartaccount_id',
        'expense_chartaccount_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(ProductServiceUnit::class, 'unit_id');
    }

    public function saleChartAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'sale_chartaccount_id');
    }

    public function expenseChartAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'expense_chartaccount_id');
    }

    public function taxes()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function customFieldValues()
    {
        // We point to CustomFieldValue and tell Laravel that 'record_id' matches our product ID
        return $this->hasMany(CustomFieldValue::class, 'record_id');
    }

    public function syncCustomFields(array $customFields)
    {
        // First, clear out old values for this product record to prevent duplicates
        $this->customFieldValues()->delete();

        // Loop through the new values and save them
        foreach ($customFields as $fieldId => $value) {
            if (!is_null($value) && $value !== '') {
                $this->customFieldValues()->create([
                    'field_id'  => $fieldId,
                    'value'     => $value,
                ]);
            }
        }
    }
}
