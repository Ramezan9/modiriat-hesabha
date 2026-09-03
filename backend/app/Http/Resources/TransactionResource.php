<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'customer_id' => $this->customer_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'amount_in_words' => $this->amount_in_words,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'receipts' => ReceiptResource::collection($this->whenLoaded('receipts')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
