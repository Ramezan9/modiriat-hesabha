<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customers_count' => $this->customers_count,

            'balances' => $this->balances,

            'recent_transactions' => TransactionResource::collection(
                $this->recent_transactions
            ),
        ];
    }
}
