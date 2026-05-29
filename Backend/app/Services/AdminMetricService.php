<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;

class AdminMetricService
{
    /**
     * Get admin dashboard metrics.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(?string $from, ?string $to): array
    {
        $prescriptionQuery = Prescription::query();

        if ($from !== null) {
            $prescriptionQuery->whereDate('prescriptions.created_at', '>=', $from);
        }

        if ($to !== null) {
            $prescriptionQuery->whereDate('prescriptions.created_at', '<=', $to);
        }

        return [
            'totals' => [
                'doctors' => Doctor::count(),
                'patients' => Patient::count(),
                'prescriptions' => (clone $prescriptionQuery)->count(),
            ],
            'by_status' => [
                'pending' => (clone $prescriptionQuery)->where('status', 'pending')->count(),
                'consumed' => (clone $prescriptionQuery)->where('status', 'consumed')->count(),
            ],
            'by_day' => (clone $prescriptionQuery)
                ->selectRaw('DATE(prescriptions.created_at) as date, count(*) as count')
                ->groupByRaw('DATE(prescriptions.created_at)')
                ->orderByRaw('date')
                ->get(),
            'top_doctors' => (clone $prescriptionQuery)
                ->selectRaw('doctors.id as doctor_id, users.name as doctor_name, count(*) as count')
                ->join('doctors', 'prescriptions.doctor_id', '=', 'doctors.id')
                ->join('users', 'doctors.user_id', '=', 'users.id')
                ->groupBy('doctors.id', 'users.name')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
        ];
    }
}
