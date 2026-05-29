<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Prescription;
use App\Services\PrescriptionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PrescriptionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Create a new prescription.
     *
     * POST /api/prescriptions
     */
    public function store(Request $request, PrescriptionService $svc): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.dosage' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->authorize('create', Prescription::class);

        $doctor = $request->user()->doctor;
        $patient = Patient::findOrFail($validated['patient_id']);

        $prescription = $svc->createPrescription(
            $doctor,
            $patient,
            $validated['notes'] ?? null,
            $validated['items'],
        );

        return response()->json($prescription->toArray(), 201);
    }

    /**
     * List doctor's prescriptions.
     *
     * GET /api/prescriptions
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,consumed'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $limit = min((int) $request->input('limit', 15), 100);
        $doctor = $request->user()->doctor;

        $query = $doctor->prescriptions();

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $prescriptions = $query->orderByDesc('created_at')->paginate($limit);

        return response()->json($prescriptions);
    }

    /**
     * Show a prescription with items.
     *
     * GET /api/prescriptions/{prescription}
     */
    public function show(Prescription $prescription): JsonResponse
    {
        if (! Gate::allows('view', $prescription)) {
            abort(404);
        }

        return response()->json($prescription->load('items')->toArray());
    }

    /**
     * Mark a prescription as consumed.
     *
     * PUT /api/prescriptions/{prescription}/consume
     */
    public function consume(Prescription $prescription, PrescriptionService $svc): JsonResponse
    {
        if (! Gate::allows('consume', $prescription)) {
            abort(404);
        }

        $prescription = $svc->consumePrescription($prescription);

        return response()->json($prescription->toArray());
    }

    /**
     * List patient's own prescriptions.
     *
     * GET /api/me/prescriptions
     */
    public function myPrescriptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,consumed'],
        ]);

        $limit = min((int) $request->input('limit', 15), 100);
        $patient = $request->user()->patient;

        $query = $patient->prescriptions();

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $prescriptions = $query->orderByDesc('created_at')->paginate($limit);

        return response()->json($prescriptions);
    }
}
