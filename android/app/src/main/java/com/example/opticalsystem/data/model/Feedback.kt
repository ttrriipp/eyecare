package com.example.opticalsystem.data.model

import com.google.gson.annotations.SerializedName

data class Feedback(
    val id: Int,
    @SerializedName("user_id")
    val userId: Int,
    val user: User?,
    @SerializedName("product_id")
    val productId: Int?,
    @SerializedName("feedback_type")
    val feedbackType: String? = null,
    val rating: Int,
    val comment: String?,
    @SerializedName("is_visible")
    val isVisible: Boolean = true,
    @SerializedName("approval_status")
    val approvalStatus: String? = null,
    @SerializedName("rejection_reason")
    val rejectionReason: String? = null,
    @SerializedName("admin_reply")
    val adminReply: String? = null,
    @SerializedName("hidden_from_public_message")
    val hiddenFromPublicMessage: String? = null,
    @SerializedName("created_at")
    val createdAt: String,
    @SerializedName("updated_at")
    val updatedAt: String?,
)

data class FeedbackListResponse(
    val data: List<Feedback>,
    @SerializedName("average_rating")
    val averageRating: Float?,
    @SerializedName("can_review")
    val canReview: Boolean?,
    @SerializedName("my_feedback")
    val myFeedback: Feedback?,
    val meta: PaginationMeta?,
)

data class StoreFeedbackRequest(
    val rating: Int,
    val comment: String?,
)

data class StoreFeedbackResponse(
    val message: String,
    val feedback: Feedback,
)

