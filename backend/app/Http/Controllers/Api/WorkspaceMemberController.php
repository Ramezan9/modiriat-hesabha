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

    /**
     * نمایش اعضای Workspace
     */
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

    /**
     * اضافه کردن عضو جدید
     */
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
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'role' => [
                'required',
                'in:manager,employee',
            ],
        ]);

        $existingMember = WorkspaceMember::where(
            'workspace_id',
            $workspaceId
        )
            ->where('user_id', $data['user_id'])
            ->first();

        abort_if(
            $existingMember !== null,
            422,
            'این کاربر قبلاً عضو این فضای کاری است.'
        );

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

    /**
     * ویرایش نقش یا وضعیت عضو
     */
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
            'role' => [
                'sometimes',
                'in:manager,employee',
            ],
            'status' => [
                'sometimes',
                'in:active,inactive,pending',
            ],
        ]);

        /*
         * مدیر نمی‌تواند خودش را غیرفعال کند.
         */
        if (
            $member->user_id === $request->user()->id
            && isset($data['status'])
            && $data['status'] !== 'active'
        ) {
            abort(
                422,
                'مدیر نمی‌تواند حساب عضویت خودش را غیرفعال کند.'
            );
        }

        /*
         * آخرین مدیر فعال نباید به کارمند تبدیل یا غیرفعال شود.
         */
        if (
            $member->role === 'manager'
            && $member->status === 'active'
            && (
                (isset($data['role']) && $data['role'] === 'employee')
                || (isset($data['status']) && $data['status'] !== 'active')
            )
        ) {
            $activeManagersCount = WorkspaceMember::where(
                'workspace_id',
                $member->workspace_id
            )
                ->where('role', 'manager')
                ->where('status', 'active')
                ->count();

            abort_if(
                $activeManagersCount <= 1,
                422,
                'آخرین مدیر فعال فضای کاری نمی‌تواند حذف یا به کارمند تبدیل شود.'
            );
        }

        $member->update($data);

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات عضو ویرایش شد.',
            'data' => $member->fresh()->load('user'),
        ]);
    }

    /**
     * حذف عضو از Workspace
     */
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

        /*
         * مدیر نمی‌تواند عضویت خودش را حذف کند.
         */
        abort_if(
            $member->user_id === $request->user()->id,
            422,
            'مدیر نمی‌تواند عضویت خودش را حذف کند.'
        );

        /*
         * آخرین مدیر فعال نباید حذف شود.
         */
        if (
            $member->role === 'manager'
            && $member->status === 'active'
        ) {
            $activeManagersCount = WorkspaceMember::where(
                'workspace_id',
                $member->workspace_id
            )
                ->where('role', 'manager')
                ->where('status', 'active')
                ->count();

            abort_if(
                $activeManagersCount <= 1,
                422,
                'آخرین مدیر فعال فضای کاری نمی‌تواند حذف شود.'
            );
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'عضو از فضای کاری حذف شد.',
        ]);
    }
}
