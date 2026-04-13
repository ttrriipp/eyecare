package com.example.opticalsystem.data.api

import com.example.opticalsystem.data.model.FeedbackListResponse
import com.example.opticalsystem.data.model.StoreFeedbackResponse
import com.example.opticalsystem.data.model.StoreFeedbackRequest
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query

interface FeedbackApi {

    @GET("products/{product}/feedbacks")
    suspend fun getFeedbacks(
        @Path("product") productId: Int,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 5,
        @Query("rating") rating: Int? = null,
        @Query("sort_by") sortBy: String? = null,
        @Query("sort_dir") sortDir: String? = null,
    ): Response<FeedbackListResponse>

    @POST("products/{product}/feedbacks")
    suspend fun submitFeedback(
        @Path("product") productId: Int,
        @Body request: StoreFeedbackRequest,
    ): Response<StoreFeedbackResponse>

    @PUT("feedbacks/{feedback}")
    suspend fun updateFeedback(
        @Path("feedback") feedbackId: Int,
        @Body request: StoreFeedbackRequest,
    ): Response<StoreFeedbackResponse>

    @DELETE("feedbacks/{feedback}")
    suspend fun deleteFeedback(
        @Path("feedback") feedbackId: Int,
    ): Response<Unit>
}

