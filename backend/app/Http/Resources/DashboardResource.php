<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customers_count' => $this->resource['customers_count'] ?? 0,

            'balances' => $this->resource['balances'] ?? [],

            'receivables' => $this->resource['receivables'] ?? [],

            'payables' => $this->resource['payables'] ?? [],

            'withdrawals' => $this->resource['withdrawals'] ?? [],

            'recent_transactions' => TransactionResource::collection(
                $this->resource['recent_transactions'] ?? []
            ),
        ];
    }
}
