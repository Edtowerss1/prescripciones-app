<?php

namespace App\Http\Controllers;

use App\Http\Requests\Patient\PatientFilterRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    /**
     * Search and list patients.
     *
     * GET /api/patients
     */
    public function index(PatientFilterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $limit = min((int) ($validated['limit'] ?? 15), 100);

        $patients = Patient::with('user')
            ->when(! empty($validated['query']), fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', '%'.$validated['query'].'%')
            )
            )
            ->orderByDesc('created_at')
            ->paginate($limit);

        return PatientResource::collection($patients)->response();
    }
}
