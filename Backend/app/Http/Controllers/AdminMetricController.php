<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminMetricResource;
use App\Services\AdminMetricService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMetricController extends Controller
{
    /**
     * Get admin dashboard metrics.
     *
     * GET /api/admin/metrics
     */
    public function index(Request $request, AdminMetricService $service): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $metrics = $service->getMetrics(
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        return (new AdminMetricResource((object) $metrics))->response();
    }
}
