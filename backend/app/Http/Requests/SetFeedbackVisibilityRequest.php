<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetFeedbackVisibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminOrStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'is_visible' => ['required', 'boolean'],
        ];
    }
}
