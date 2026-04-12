package com.example.opticalsystem.data.api

import com.example.opticalsystem.data.model.AuthResponse
import com.example.opticalsystem.data.model.LoginRequest
import com.example.opticalsystem.data.model.ProfileResponse
import com.example.opticalsystem.data.model.RegisterRequest
import com.example.opticalsystem.data.model.UpdateProfileRequest
import com.example.opticalsystem.data.model.UpdateProfileResponse
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT

interface AuthApi {

    @POST("register")
    suspend fun register(@Body request: RegisterRequest): Response<AuthResponse>

    @POST("login")
    suspend fun login(@Body request: LoginRequest): Response<AuthResponse>

    @POST("logout")
    suspend fun logout(): Response<Unit>

    @GET("profile")
    suspend fun profile(): Response<ProfileResponse>

    @PUT("profile")
    suspend fun updateProfile(@Body body: UpdateProfileRequest): Response<UpdateProfileResponse>
}
