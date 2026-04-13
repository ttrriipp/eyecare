<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->isCustomer()) {
            return false;
        }

        $appointment = $this->route('appointment');
        if (! $appointment instanceof Appointment) {
            return false;
        }

        if ((int) $appointment->user_id !== (int) $this->user()->id) {
            return false;
        }

        return $appointment->status === AppointmentStatus::Completed;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.min' => 'Rating must be between 1 and 5.',
            'rating.max' => 'Rating must be between 1 and 5.',
        ];
    }
}
