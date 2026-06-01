<?php

namespace App\Http\Controllers;

use App\Http\Requests\Prescriptions\ConsumePrescriptionRequest;
use App\Http\Requests\Prescriptions\PrescriptionFilterRequest;
use App\Http\Requests\Prescriptions\StorePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\PdfService;
use App\Services\PrescriptionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PrescriptionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Create a new prescription.
     *
     * POST /api/prescriptions
     */
    public function store(StorePrescriptionRequest $request, PrescriptionService $svc): JsonResponse
    {
        $doctor = $request->user()->doctor;
        $patient = Patient::findOrFail($request->patient_id);

        $prescription = $svc->createPrescription(
            $doctor,
            $patient,
            $request->notes ?? null,
            $request->items,
        );

        return (new PrescriptionResource(
            $prescription->load(['items', 'doctor.user', 'patient.user'])
        ))->response()->setStatusCode(201);
    }

    /**
     * Admin lists all prescriptions with filters.
     *
     * GET /api/admin/prescriptions
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,consumed'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $limit = min((int) $request->input('limit', 15), 100);

        $query = Prescription::with(['doctor.user', 'patient.user']);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['doctor_id'])) {
            $query->where('doctor_id', $validated['doctor_id']);
        }

        if (! empty($validated['patient_id'])) {
            $query->where('patient_id', $validated['patient_id']);
        }

        if (! empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $prescriptions = $query->orderByDesc('created_at')->paginate($limit);

        return PrescriptionResource::collection($prescriptions)->response();
    }

    /**
     * List doctor's prescriptions.
     *
     * GET /api/prescriptions
     */
    public function index(PrescriptionFilterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $limit = min((int) $request->input('limit', 15), 100);
        $doctor = $request->user()->doctor;

        $query = $doctor->prescriptions()->with(['doctor.user', 'patient.user']);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $prescriptions = $query->orderBy('created_at', $validated['order'] ?? 'desc')->paginate($limit);

        return PrescriptionResource::collection($prescriptions)->response();
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

        return (new PrescriptionResource(
            $prescription->load(['items', 'doctor.user', 'patient.user'])
        ))->response();
    }

    /**
     * Download a prescription as PDF.
     *
     * GET /api/prescriptions/{prescription}/pdf
     */
    public function pdf(Prescription $prescription, PdfService $pdfService): Response
    {
        if (! Gate::allows('view', $prescription)) {
            abort(404);
        }

        return $pdfService->generatePrescriptionPdf($prescription);
    }

    /**
     * Mark a prescription as consumed.
     *
     * PUT /api/prescriptions/{prescription}/consume
     */
    public function consume(ConsumePrescriptionRequest $request, Prescription $prescription, PrescriptionService $svc): JsonResponse
    {
        if (! Gate::allows('consume', $prescription)) {
            abort(404);
        }

        $prescription = $svc->consumePrescription($prescription);

        return (new PrescriptionResource($prescription))->response();
    }

    /**
     * List patient's own prescriptions.
     *
     * GET /api/me/prescriptions
     */
    public function myPrescriptions(PrescriptionFilterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $limit = min((int) $request->input('limit', 15), 100);
        $patient = $request->user()->patient;

        $query = $patient->prescriptions()->with(['doctor.user', 'patient.user']);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $prescriptions = $query->orderByDesc('created_at')->paginate($limit);

        return PrescriptionResource::collection($prescriptions)->response();
    }
}
