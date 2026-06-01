<?php

namespace App\Http\Controllers;

use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * List doctors with optional search.
     *
     * GET /api/doctors
     */
    public function index(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 15), 100);

        $doctors = Doctor::with('user')
            ->withCount('prescriptions')
            ->when($request->filled('query'), fn ($q) => $q->whereHas(
                'user',
                fn ($uq) => $uq->where('name', 'like', '%'.$request->query('query').'%')
            ))
            ->orderByDesc('created_at')
            ->paginate($limit);

        return DoctorResource::collection($doctors)->response();
    }
}
