<?php

namespace App\Http\Requests\Prescriptions;

use App\Models\Prescription;
use Illuminate\Foundation\Http\FormRequest;

class ConsumePrescriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Delegates to PrescriptionPolicy::consume.
     */
    public function authorize(): bool
    {
        /** @var Prescription $prescription */
        $prescription = $this->route('prescription');

        return $this->user()->can('consume', $prescription);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
