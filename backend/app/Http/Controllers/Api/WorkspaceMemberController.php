<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceMemberController extends Controller
{
    /**
     * بررسی عضویت کاربر در Workspace
     */
    private function ensureMember(
        Request $request,
        int $workspaceId
    ): WorkspaceMember {
        return WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();
    }

    public function index(
        Request $request,
        int $workspaceId
    ): JsonResponse {
        $this->ensureMember($request, $workspaceId);

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
        $currentMember = $this->ensureMember(
            $request,
            $workspaceId
        );

        abort_unless(
            $currentMember->role === 'manager',
            403,
            'فقط مدیر فضای کاری اجازه اضافه کردن عضو را دارد.'
        );

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
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
        $currentMember = $this->ensureMember(
            $request,
            $member->workspace_id
        );

        abort_unless(
            $currentMember->role === 'manager',
            403,
            'فقط مدیر فضای کاری اجازه ویرایش عضو را دارد.'
        );

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
        $currentMember = $this->ensureMember(
            $request,
            $member->workspace_id
        );

        abort_unless(
            $currentMember->role === 'manager',
            403,
            'فقط مدیر فضای کاری اجازه حذف عضو را دارد.'
        );

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'عضو از فضای کاری حذف شد.',
        ]);
    }
}
