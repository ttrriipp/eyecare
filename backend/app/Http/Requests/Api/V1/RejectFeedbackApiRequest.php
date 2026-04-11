<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RejectFeedbackApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdminOrStaff();
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
