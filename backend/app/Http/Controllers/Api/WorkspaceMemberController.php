<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceMemberController extends Controller
{
    public function index(
        Request $request,
        int $workspaceId
    ): JsonResponse {
        $members = WorkspaceMember::where(
            'workspace_id',
            $workspaceId
        )
            ->with('user')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    public function store(
        Request $request,
        int $workspaceId
    ): JsonResponse {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'role' => ['required', 'in:manager,employee'],
        ]);

        $member = WorkspaceMember::create([
            'workspace_id' => $workspaceId,
            'user_id' => $data['user_id'],
            'role' => $data['role'],
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'عضو با موفقیت اضافه شد.',
            'data' => $member->load('user'),
        ], 201);
    }

    public function update(
        Request $request,
        WorkspaceMember $member
    ): JsonResponse {
        $data = $request->validate([
            'role' => ['sometimes', 'in:manager,employee'],
            'status' => [
                'sometimes',
                'in:active,inactive,pending',
            ],
        ]);

        $member->update($data);

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات عضو ویرایش شد.',
            'data' => $member->fresh()->load('user'),
        ]);
    }

    public function destroy(
        Request $request,
        WorkspaceMember $member
    ): JsonResponse {
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'عضو از فضای کاری حذف شد.',
        ]);
    }
}
