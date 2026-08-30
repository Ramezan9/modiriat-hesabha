<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private function ensureMember(
        Request $request,
        int $workspaceId
    ): WorkspaceMember {
        return WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $workspaceId = $request->query('workspace_id');

        if (!$workspaceId) {
            return response()->json([
                'success' => false,
                'message' => 'workspace_id الزامی است.',
            ], 422);
        }

        $this->ensureMember($request, (int) $workspaceId);

        $customers = Customer::where('workspace_id', $workspaceId)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'profile_photo' => ['nullable', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $member = $this->ensureMember(
            $request,
            (int) $data['workspace_id']
        );

        abort_unless(
            $member->role === 'manager',
            403,
            'فقط مدیر فضای کاری اجازه اضافه کردن مشتری را دارد.'
        );

        $customer = Customer::create($data);

        return response()->json([
            'success' => true,
            'message' => 'مشتری با موفقیت اضافه شد.',
            'data' => $customer,
        ], 201);
    }

    public function show(
        Request $request,
        Customer $customer
    ): JsonResponse {
        $this->ensureMember(
            $request,
            $customer->workspace_id
        );

        $customer->load('transactions');

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    public function update(
        Request $request,
        Customer $customer
    ): JsonResponse {
        $member = $this->ensureMember(
            $request,
            $customer->workspace_id
        );

        abort_unless(
            $member->role === 'manager',
            403,
            'فقط مدیر فضای کاری اجازه ویرایش مشتری را دارد.'
        );

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'profile_photo' => ['nullable', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $customer->update($data);

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات مشتری ویرایش شد.',
            'data' => $customer->fresh(),
        ]);
    }

    public function destroy(
        Request $request,
        Customer $customer
    ): JsonResponse {
        $member = $this->ensureMember(
            $request,
            $customer->workspace_id
        );

        abort_unless(
            $member->role === 'manager',
            403,
            'فقط مدیر فضای کاری اجازه حذف مشتری را دارد.'
        );

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'مشتری حذف شد.',
        ]);
    }
}
