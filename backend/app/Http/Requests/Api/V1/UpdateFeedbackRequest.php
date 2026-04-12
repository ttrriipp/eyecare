<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only the customer who wrote the review can update it
        return $this->user()->isCustomer()
            && $this->route('feedback')->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            // Always send the full star value on update so the DB rating cannot stay stale (e.g. client omitted key).
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
