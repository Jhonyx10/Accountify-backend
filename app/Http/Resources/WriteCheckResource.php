<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WriteCheckResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_date' => $this->date,
            'check_date' => $this->date,
            'check_number' => $this->reference ?: ('CHK-' . str_pad($this->id, 4, '0', STR_PAD_LEFT)),
            'payee' => $this->payee_name ?? ($this->payee ? $this->payee->name ?? $this->payee->first_name ?? null : null),
            'amount' => $this->amount,
            'bank_account' => $this->bankAccount ? $this->bankAccount->bank_name . ' (**** ' . substr($this->bankAccount->account_number, -4) . ')' : 'Unknown',
            'status' => 'Printed', // Replace with real status if DB has it
            'category' => $this->items && count($this->items) > 1 ? 'Split (' . count($this->items) . ' lines)' : ($this->items[0]->description ?? 'Uncategorized'),
            'items' => $this->whenLoaded('items')
        ];
    }
}
