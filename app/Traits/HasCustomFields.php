<?php

namespace App\Traits;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasCustomFields
{
    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'record_id');
    }

    public function syncCustomFields(array $fieldValues)
    {
        foreach ($fieldValues as $fieldId => $value) {
            CustomFieldValue::updateOrCreate(
                [
                    'record_id' => $this->id,
                    'field_id' => $fieldId,
                ],
                [
                    'value' => $value,
                ]
            );
        }
    }

    public function getCustomFieldsAttribute()
    {
        // Return an associative array of field_id => value
        return $this->customFieldValues->pluck('value', 'field_id')->toArray();
    }
}
