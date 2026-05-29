<?php

namespace App\Services;

use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfService
{
    /**
     * Generate a downloadable PDF for the given prescription.
     */
    public function generatePrescriptionPdf(Prescription $prescription): Response
    {
        $prescription->load(['doctor.user', 'patient.user', 'items']);

        $pdf = Pdf::loadView('pdf.prescription', ['prescription' => $prescription]);

        return $pdf->download("prescription-{$prescription->code}.pdf");
    }
}
