<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WriteCheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $bankAccount = $this->bankAccount;
        $bankAccountLabel = $bankAccount
            ? $bankAccount->bank_name . ' (**** ' . substr($bankAccount->account_number, -4) . ')'
            : 'Unknown Account';

        return [
            'id'               => $this->id,
            'transaction_date' => $this->date,
            'check_date'       => $this->date,
            'check_number'     => $this->reference ?: ('CHK-' . str_pad($this->id, 4, '0', STR_PAD_LEFT)),
            'payee'            => $this->payee_name, // Uses getPayeeNameAttribute()
            'payee_id'         => $this->payee_id,
            'payee_type'       => $this->payee_type,
            'amount'           => (float) $this->amount,
            'amount_in_words'  => $this->amount_in_words,
            'description'      => $this->description,
            'bank_account_id'  => $this->bank_account_id,
            'bank_account'     => $bankAccountLabel,
            'bank_balance'     => $bankAccount ? (float) $bankAccount->opening_balance : null,
            'status'           => 'Printed',
            'category'         => $this->whenLoaded('items', function () {
                $items = $this->items;
                if ($items->count() > 1)
                    return 'Split (' . $items->count() . ' lines)';
                return $items->first()?->description ?? 'Uncategorized';
            }, 'Uncategorized'),
            'items'            => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id'                  => $item->id,
                        'chart_of_account_id' => $item->chart_of_account_id,
                        'account_name'        => $item->chartOfAccount
                            ? ($item->chartOfAccount->code . ' - ' . $item->chartOfAccount->name)
                            : '—',
                        'product_id'          => $item->product_id,
                        'description'         => $item->description,
                        'amount'              => (float) $item->amount,
                    ];
                });
            }),
            'created_at'       => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
