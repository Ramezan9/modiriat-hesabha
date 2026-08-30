<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    private function ensureMember(
        Request $request,
        Workspace $workspace
    ): WorkspaceMember {
        return WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $workspaces = Workspace::whereHas('members', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id)
                ->where('status', 'active');
        })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $workspaces,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $workspace = Workspace::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'owner_id' => $request->user()->id,
            'invite_code' => strtoupper(Str::random(8)),
            'is_active' => true,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'role' => 'manager',
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'فضای کاری با موفقیت ایجاد شد.',
            'data' => $workspace,
        ], 201);
    }

    public function show(
        Request $request,
        Workspace $workspace
    ): JsonResponse {
        $this->ensureMember($request, $workspace);

        $workspace->load('members.user');

        return response()->json([
            'success' => true,
            'data' => $workspace,
        ]);
    }

    public function update(
        Request $request,
        Workspace $workspace
    ): JsonResponse {
        $member = $this->ensureMember($request, $workspace);

        abort_unless(
            $member->role === 'manager',
            403,
            'فقط مدیر فضای کاری اجازه ویرایش را دارد.'
        );

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $workspace->update($data);

        return response()->json([
            'success' => true,
            'message' => 'فضای کاری ویرایش شد.',
            'data' => $workspace->fresh(),
        ]);
    }

    public function destroy(
        Request $request,
        Workspace $workspace
    ): JsonResponse {
        $member = $this->ensureMember($request, $workspace);

        abort_unless(
            $member->role === 'manager',
            403,
            'فقط مدیر فضای کاری اجازه حذف را دارد.'
        );

        $workspace->delete();

        return response()->json([
            'success' => true,
            'message' => 'فضای کاری حذف شد.',
        ]);
    }
}
