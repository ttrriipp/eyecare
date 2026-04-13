<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillOfficialReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminOrStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'official_receipt_number' => ['nullable', 'string', 'max:120'],
        ];
    }
}
