<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $workspaceId = $request->user()->workspace_id;

        $summary = $this->dashboardService
            ->getSummary($workspaceId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
