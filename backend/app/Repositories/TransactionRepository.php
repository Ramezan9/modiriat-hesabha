<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class TransactionRepository
{
    public function findById(
        int $id,
        int $workspaceId
    ): ?Transaction {
        return Transaction::where('id', $id)
            ->where('workspace_id', $workspaceId)
            ->first();
    }

    public function findForCustomer(
        int $customerId,
        int $workspaceId
    ): Collection {
        return Transaction::where('customer_id', $customerId)
            ->where('workspace_id', $workspaceId)
            ->latest('transaction_date')
            ->get();
    }

    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function update(
        Transaction $transaction,
        array $data
    ): Transaction {
        $transaction->update($data);

        return $transaction->fresh();
    }

    public function delete(Transaction $transaction): bool
    {
        return (bool) $transaction->delete();
    }

    public function getWorkspaceTransactions(
        int $workspaceId
    ): Collection {
        return Transaction::where('workspace_id', $workspaceId)
            ->latest('transaction_date')
            ->get();
    }
}
