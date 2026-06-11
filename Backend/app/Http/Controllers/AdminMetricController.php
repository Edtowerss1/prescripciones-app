<?php

namespace App\Http\Controllers;

use App\Http\Requests\Metrics\AdminMetricsFilterRequest;
use App\Http\Resources\AdminMetricResource;
use App\Services\AdminMetricService;
use Illuminate\Http\JsonResponse;

class AdminMetricController extends Controller
{
    /**
     * Get admin dashboard metrics.
     *
     * GET /api/admin/metrics
     */
    public function index(AdminMetricsFilterRequest $request, AdminMetricService $service): JsonResponse
    {
        $validated = $request->validated();

        $metrics = $service->getMetrics(
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        return response()->json(new AdminMetricResource((object) $metrics));
    }
}
