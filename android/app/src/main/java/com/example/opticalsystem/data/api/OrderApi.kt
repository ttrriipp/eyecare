package com.example.opticalsystem.data.api

import com.example.opticalsystem.data.model.CreateOrderRequest
import com.example.opticalsystem.data.model.CreateOrderResponse
import com.example.opticalsystem.data.model.OrderDetailResponse
import com.example.opticalsystem.data.model.OrderListResponse
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface OrderApi {

    @GET("orders")
    suspend fun getOrders(
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 15,
        @Query("status") status: String? = null,
        @Query("search") search: String? = null,
        @Query("sort_by") sortBy: String? = null,
        @Query("sort_dir") sortDir: String? = null,
    ): Response<OrderListResponse>

    @GET("orders/{id}")
    suspend fun getOrder(@Path("id") id: Int): Response<OrderDetailResponse>

    @POST("orders")
    suspend fun createOrder(@Body request: CreateOrderRequest): Response<CreateOrderResponse>

    @POST("orders/{id}/cancel")
    suspend fun cancelOrder(@Path("id") id: Int): Response<CreateOrderResponse>
}
