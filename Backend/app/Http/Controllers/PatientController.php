<?php

namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Search and list patients.
     *
     * GET /api/patients
     */
    public function index(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 15), 100);

        $patients = Patient::with('user')
            ->when($request->filled('query'), fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', '%'.$request->query('query').'%')
            )
            )
            ->orderByDesc('created_at')
            ->paginate($limit);

        return PatientResource::collection($patients)->response();
    }
}
