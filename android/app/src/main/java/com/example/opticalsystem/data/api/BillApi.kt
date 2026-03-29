package com.example.opticalsystem.data.api

import com.example.opticalsystem.data.model.BillDetailResponse
import com.example.opticalsystem.data.model.BillListResponse
import retrofit2.Response
import retrofit2.http.GET
import retrofit2.http.Path
import retrofit2.http.Query

interface BillApi {

    @GET("bills")
    suspend fun getBills(
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 15,
        @Query("payment_status") paymentStatus: String? = null,
        @Query("sort_by") sortBy: String? = null,
        @Query("sort_dir") sortDir: String? = null,
    ): Response<BillListResponse>

    @GET("bills/{id}")
    suspend fun getBill(@Path("id") id: Int): Response<BillDetailResponse>
}
