<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->input('status') === OrderStatus::Cancelled->value) {
            return $this->user() !== null;
        }

        return $this->user()?->isAdminOrStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_column(OrderStatus::cases(), 'value'))],
        ];
    }
}
