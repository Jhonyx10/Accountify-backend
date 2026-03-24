<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'bank_name'       => $this->bank_name,
            'holder_name'     => $this->holder_name,
            'account_number'  => $this->account_number,
            'opening_balance' => (float) $this->opening_balance,
            'current_balance' => $this->whenLoaded('chartAccount', function () {
                $ledgerBalance = $this->chartAccount ? (float) $this->chartAccount->balance : 0;
                return (float) $this->opening_balance + $ledgerBalance;
            }, (float) $this->opening_balance),
            'contact_number'  => $this->contact_number,
            'bank_address'    => $this->bank_address,
            'chart_account_id' => $this->chart_account_id,
            'chart_account'   => $this->whenLoaded('chartAccount', function () {
                return $this->chartAccount ? [
                    'id'   => $this->chartAccount->id,
                    'code' => $this->chartAccount->code,
                    'name' => $this->chartAccount->name,
                ] : null;
            }),
            'created_by'  => $this->created_by,
            'created_at'  => $this->created_at?->format('Y-m-d'),
            'updated_at'  => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
