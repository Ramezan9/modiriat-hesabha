<?php

namespace App\Repositories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

class WorkspaceRepository
{
    public function findById(
        int $id,
        int $userId
    ): ?Workspace {
        return Workspace::where('id', $id)
            ->whereHas('members', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('status', 'active');
            })
            ->first();
    }

    public function findForUser(
        int $workspaceId,
        int $userId
    ): ?Workspace {
        return Workspace::where('id', $workspaceId)
            ->whereHas('members', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('status', 'active');
            })
            ->first();
    }

    public function getUserWorkspaces(
        int $userId
    ): Collection {
        return Workspace::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->where('status', 'active');
        })
            ->latest()
            ->get();
    }

    public function create(array $data): Workspace
    {
        return Workspace::create($data);
    }

    public function update(
        Workspace $workspace,
        array $data
    ): Workspace {
        $workspace->update($data);

        return $workspace->fresh();
    }

    public function delete(Workspace $workspace): bool
    {
        return (bool) $workspace->delete();
    }
}
