<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'date'             => $this->date?->format('Y-m-d'),
            'reference'        => $this->reference,
            'amount'           => (float) $this->amount,
            'status'           => $this->status ?? 'Completed',
            'description'      => $this->description,
            'payment_method'   => $this->payment_method,
            'journal_entry_id' => $this->journal_entry_id,
            'from_account'     => $this->from_account,
            'to_account'       => $this->to_account,
            'from_account_name' => $this->whenLoaded('fromBankAccount', function () {
                $acc = $this->fromBankAccount;
                return $acc ? "{$acc->bank_name} – {$acc->holder_name}" : null;
            }),
            'to_account_name' => $this->whenLoaded('toBankAccount', function () {
                $acc = $this->toBankAccount;
                return $acc ? "{$acc->bank_name} – {$acc->holder_name}" : null;
            }),
            'from_bank_account' => $this->whenLoaded('fromBankAccount', function () {
                $acc = $this->fromBankAccount;
                return $acc ? [
                    'id'             => $acc->id,
                    'bank_name'      => $acc->bank_name,
                    'holder_name'    => $acc->holder_name,
                    'account_number' => $acc->account_number,
                ] : null;
            }),
            'to_bank_account' => $this->whenLoaded('toBankAccount', function () {
                $acc = $this->toBankAccount;
                return $acc ? [
                    'id'             => $acc->id,
                    'bank_name'      => $acc->bank_name,
                    'holder_name'    => $acc->holder_name,
                    'account_number' => $acc->account_number,
                ] : null;
            }),
            'created_by'  => $this->created_by,
            'created_at'  => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'  => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

