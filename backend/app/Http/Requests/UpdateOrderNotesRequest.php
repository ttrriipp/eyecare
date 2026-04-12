<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminOrStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
