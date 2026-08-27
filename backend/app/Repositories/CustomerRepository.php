<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository
{
    public function findById(
        int $id,
        int $workspaceId
    ): ?Customer {
        return Customer::where('id', $id)
            ->where('workspace_id', $workspaceId)
            ->first();
    }

    public function getAll(
        int $workspaceId
    ): Collection {
        return Customer::where('workspace_id', $workspaceId)
            ->latest()
            ->get();
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(
        Customer $customer,
        array $data
    ): Customer {
        $customer->update($data);

        return $customer->fresh();
    }

    public function delete(Customer $customer): bool
    {
        return (bool) $customer->delete();
    }

    public function search(
        int $workspaceId,
        string $keyword
    ): Collection {
        return Customer::where('workspace_id', $workspaceId)
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");
            })
            ->latest()
            ->get();
    }

    public function pinned(
        int $workspaceId
    ): Collection {
        return Customer::where('workspace_id', $workspaceId)
            ->where('is_pinned', true)
            ->latest()
            ->get();
    }
}
