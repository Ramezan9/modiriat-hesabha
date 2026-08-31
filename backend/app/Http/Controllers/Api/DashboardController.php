<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceMember;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
    }

    /**
     * بررسی عضویت فعال کاربر در Workspace
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

    public function index(Request $request): JsonResponse
    {
        $workspaceId = $request->query('workspace_id');

        if (!$workspaceId) {
            return response()->json([
                'success' => false,
                'message' => 'workspace_id الزامی است.',
            ], 422);
        }

        $workspaceId = (int) $workspaceId;

        $this->ensureMember(
            $request,
            $workspaceId
        );

        $summary = $this->dashboardService
            ->getSummary($workspaceId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
