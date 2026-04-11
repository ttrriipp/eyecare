<?php

namespace App\Http\Resources\V1;

use App\Enums\FeedbackApprovalStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isOwner = $user !== null && (int) $user->id === (int) $this->user_id;
        $isStaff = $user !== null && $user->isAdminOrStaff();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'appointment_id' => $this->appointment_id,
            'feedback_type' => $this->feedback_type?->value ?? $this->feedback_type,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'is_verified_purchase' => $this->is_verified_purchase,
            'is_visible' => $this->is_visible,
            'approval_status' => $this->approval_status?->value ?? $this->approval_status,
            'approval_reviewed_at' => $this->approval_reviewed_at?->toISOString(),
            'rejection_reason' => ($isOwner || $isStaff) ? $this->rejection_reason : null,
            'admin_reply' => $this->admin_reply,
            'moderated_by' => $this->moderated_by,
            'moderated_at' => $this->moderated_at?->toISOString(),
            'hidden_from_public_message' => $this->when(
                $this->includeHiddenFromPublicMessage($request),
                fn () => __('The clinic hid your rating and comment on this product because they did not meet our community standards (for example, offensive or inappropriate language). Other customers no longer see this review; you can still update it below or contact us if you think this was a mistake.'),
            ),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    private function includeHiddenFromPublicMessage(Request $request): bool
    {
        if ($this->is_visible) {
            return false;
        }

        $user = $request->user();
        if ($user === null || (int) $this->user_id !== (int) $user->id) {
            return false;
        }

        return $this->approval_status === FeedbackApprovalStatus::Approved;
    }
}
