package com.example.opticalsystem.data.repository

import android.util.Log
import com.example.opticalsystem.data.api.BillApi
import com.example.opticalsystem.data.model.Bill
import com.example.opticalsystem.util.Resource
import org.json.JSONObject
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class BillRepository @Inject constructor(
    private val billApi: BillApi,
) {
    companion object {
        private const val TAG = "BillRepository"
    }

    private fun networkError(e: Exception, fallback: String): String {
        val msg = e.message?.trim()
        return if (!msg.isNullOrEmpty()) {
            "Network error (${e.javaClass.simpleName}): $msg"
        } else {
            "Network error (${e.javaClass.simpleName})"
        }.ifBlank { fallback }
    }

    private fun parseError(rawBody: String?, fallback: String): String {
        val body = rawBody?.trim().orEmpty()
        if (body.isNotEmpty()) {
            runCatching {
                val json = JSONObject(body)
                val message = json.optString("message").trim()
                if (message.isNotEmpty()) return message
            }
        }
        return fallback
    }

    suspend fun getBills(
        page: Int = 1,
        perPage: Int = 15,
        paymentStatus: String? = null,
    ): Resource<Pair<List<Bill>, Int>> {
        return try {
            val response = billApi.getBills(
                page = page,
                perPage = perPage,
                paymentStatus = paymentStatus,
                sortBy = "created_at",
                sortDir = "desc",
            )
            if (response.isSuccessful && response.body() != null) {
                val body = response.body()!!
                val total = body.meta?.total ?: body.data.size
                Resource.Success(Pair(body.data, total))
            } else {
                Resource.Error(parseError(response.errorBody()?.string(), "Failed to load bills"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "getBills failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }

    suspend fun getBill(id: Int): Resource<Bill> {
        return try {
            val response = billApi.getBill(id)
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.data)
            } else {
                Resource.Error(parseError(response.errorBody()?.string(), "Bill not found"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "getBill failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }
}
