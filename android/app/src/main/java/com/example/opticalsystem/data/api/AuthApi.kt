package com.example.opticalsystem.data.api

import com.example.opticalsystem.data.model.AuthResponse
import com.example.opticalsystem.data.model.LoginRequest
import com.example.opticalsystem.data.model.ProfileResponse
import com.example.opticalsystem.data.model.RegisterRequest
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST

interface AuthApi {

    @POST("register")
    suspend fun register(@Body request: RegisterRequest): Response<AuthResponse>

    @POST("login")
    suspend fun login(@Body request: LoginRequest): Response<AuthResponse>

    @POST("logout")
    suspend fun logout(): Response<Unit>

    @GET("profile")
    suspend fun profile(): Response<ProfileResponse>
}
