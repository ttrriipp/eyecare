package com.example.opticalsystem.data.api

import com.example.opticalsystem.data.model.AppointmentListResponse
import retrofit2.Response
import retrofit2.http.GET
import retrofit2.http.Query

interface AppointmentApi {

    @GET("appointments")
    suspend fun getAppointments(
        @Query("status") status: String? = "upcoming",
        @Query("per_page") perPage: Int = 30,
    ): Response<AppointmentListResponse>
}
