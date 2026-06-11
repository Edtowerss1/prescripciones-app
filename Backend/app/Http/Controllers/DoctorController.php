<?php

namespace App\Http\Controllers;

use App\Http\Requests\Doctor\DoctorFilterRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;

class DoctorController extends Controller
{
    /**
     * List doctors with optional search.
     *
     * GET /api/doctors
     */
    public function index(DoctorFilterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $limit = min((int) ($validated['limit'] ?? 15), 100);

        $doctors = Doctor::with('user')
            ->withCount('prescriptions')
            ->when(! empty($validated['query']), fn ($q) => $q->whereHas(
                'user',
                fn ($uq) => $uq->where('name', 'like', '%'.$validated['query'].'%')
            ))
            ->orderByDesc('created_at')
            ->paginate($limit);

        return DoctorResource::collection($doctors)->response();
    }
}
