package com.example.opticalsystem.data.repository

import android.util.Log
import com.example.opticalsystem.data.api.OrderApi
import com.example.opticalsystem.data.model.CreateOrderRequest
import com.example.opticalsystem.data.model.Order
import com.example.opticalsystem.util.Resource
import org.json.JSONArray
import org.json.JSONObject
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class OrderRepository @Inject constructor(
    private val orderApi: OrderApi,
) {
    companion object {
        private const val TAG = "OrderRepository"
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
                val errors = json.optJSONObject("errors")
                if (errors != null && errors.keys().hasNext()) {
                    val firstKey = errors.keys().next()
                    val firstValue = errors.opt(firstKey)
                    val firstMessage = when (firstValue) {
                        is JSONArray -> firstValue.optString(0)
                        is String -> firstValue
                        else -> null
                    }?.trim()
                    if (!firstMessage.isNullOrEmpty()) return firstMessage
                }
                val message = json.optString("message").trim()
                if (message.isNotEmpty()) return message
            }
        }
        return fallback
    }

    suspend fun getOrders(
        page: Int = 1,
        perPage: Int = 15,
        status: String? = null,
        search: String? = null,
    ): Resource<Pair<List<Order>, Int>> {
        return try {
            val response = orderApi.getOrders(
                page = page,
                perPage = perPage,
                status = status,
                search = search,
                sortBy = "created_at",
                sortDir = "desc",
            )
            if (response.isSuccessful && response.body() != null) {
                val body = response.body()!!
                val total = body.meta?.total ?: body.data.size
                Resource.Success(Pair(body.data, total))
            } else {
                Resource.Error(parseError(response.errorBody()?.string(), "Failed to load orders"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "getOrders failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }

    suspend fun getOrder(id: Int): Resource<Order> {
        return try {
            val response = orderApi.getOrder(id)
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.data)
            } else {
                Resource.Error(parseError(response.errorBody()?.string(), "Order not found"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "getOrder failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }

    suspend fun createOrder(request: CreateOrderRequest): Resource<Order> {
        return try {
            val response = orderApi.createOrder(request)
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.order)
            } else {
                Resource.Error(parseError(response.errorBody()?.string(), "Failed to create order"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "createOrder failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }

    suspend fun cancelOrder(id: Int): Resource<Order> {
        return try {
            val response = orderApi.cancelOrder(id)
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.order)
            } else {
                Resource.Error(parseError(response.errorBody()?.string(), "Failed to cancel order"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "cancelOrder failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }
}
