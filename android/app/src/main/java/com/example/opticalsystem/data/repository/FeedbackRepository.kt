package com.example.opticalsystem.data.repository

import android.util.Log
import com.example.opticalsystem.data.api.FeedbackApi
import com.example.opticalsystem.data.model.Feedback
import com.example.opticalsystem.data.model.FeedbackListResponse
import com.example.opticalsystem.data.model.StoreFeedbackRequest
import com.example.opticalsystem.data.model.StoreFeedbackResponse
import com.example.opticalsystem.util.Resource
import org.json.JSONObject
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class FeedbackRepository @Inject constructor(
    private val feedbackApi: FeedbackApi,
) {
    companion object {
        private const val TAG = "FeedbackRepository"
    }

    private fun networkError(e: Exception, fallback: String): String {
        val msg = e.message?.trim()
        return if (!msg.isNullOrEmpty()) {
            "Network error (${e.javaClass.simpleName}): $msg"
        } else {
            "Network error (${e.javaClass.simpleName})"
        }.ifBlank { fallback }
    }

    private fun parseError(code: Int, rawBody: String?, fallback: String): String {
        val body = rawBody?.trim().orEmpty()
        if (body.isNotEmpty()) {
            runCatching {
                val json = JSONObject(body)

                val errorsObj = json.optJSONObject("errors")
                if (errorsObj != null) {
                    // Prefer specific validation keys that are most useful to end users.
                    val preferredKeys = listOf("product_id", "rating", "comment")
                    for (key in preferredKeys) {
                        val arr = errorsObj.optJSONArray(key)
                        if (arr != null && arr.length() > 0) {
                            val first = arr.optString(0)
                            if (first.isNotBlank()) return first
                        }
                    }

                    val keys = errorsObj.keys()
                    while (keys.hasNext()) {
                        val key = keys.next()
                        val arr = errorsObj.optJSONArray(key)
                        if (arr != null && arr.length() > 0) {
                            val first = arr.optString(0)
                            if (first.isNotBlank()) return first
                        }
                    }
                }

                val message = json.optString("message").trim()
                if (message.isNotBlank()) return message
            }
        }

        return if (code >= 500) {
            "Server error ($code). Check backend logs."
        } else {
            fallback
        }
    }

    suspend fun getFeedbacks(
        productId: Int,
        page: Int = 1,
        perPage: Int = 5,
        rating: Int? = null,
    ): Resource<FeedbackListResponse> {
        return try {
            val response = feedbackApi.getFeedbacks(
                productId = productId,
                page = page,
                perPage = perPage,
                rating = rating,
            )

            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!)
            } else {
                Resource.Error(
                    parseError(
                        code = response.code(),
                        rawBody = response.errorBody()?.string(),
                        fallback = "Failed to load reviews",
                    ),
                )
            }
        } catch (e: Exception) {
            Log.e(TAG, "getFeedbacks failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }

    suspend fun submitFeedback(
        productId: Int,
        rating: Int,
        comment: String?,
    ): Resource<Feedback> {
        return try {
            val response: retrofit2.Response<StoreFeedbackResponse> = feedbackApi.submitFeedback(
                productId = productId,
                request = StoreFeedbackRequest(
                    rating = rating,
                    comment = comment,
                ),
            )

            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.feedback)
            } else {
                Resource.Error(
                    parseError(
                        code = response.code(),
                        rawBody = response.errorBody()?.string(),
                        fallback = "Failed to submit review",
                    ),
                )
            }
        } catch (e: Exception) {
            Log.e(TAG, "submitFeedback failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }

    suspend fun updateFeedback(
        feedbackId: Int,
        rating: Int,
        comment: String?,
    ): Resource<Feedback> {
        return try {
            val response: retrofit2.Response<StoreFeedbackResponse> = feedbackApi.updateFeedback(
                feedbackId = feedbackId,
                request = StoreFeedbackRequest(
                    rating = rating,
                    comment = comment,
                ),
            )

            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.feedback)
            } else {
                Resource.Error(
                    parseError(
                        code = response.code(),
                        rawBody = response.errorBody()?.string(),
                        fallback = "Failed to update review",
                    ),
                )
            }
        } catch (e: Exception) {
            Log.e(TAG, "updateFeedback failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }
}

