package com.example.opticalsystem.data.model

import com.google.gson.annotations.SerializedName

data class LoginRequest(
    val email: String,
    val password: String,
)

data class RegisterRequest(
    val name: String,
    val email: String,
    val phone: String?,
    val password: String,
    @SerializedName("password_confirmation")
    val passwordConfirmation: String,
)

data class AuthResponse(
    val message: String,
    val user: User,
    val token: String,
)

data class ProfileResponse(
    val user: User,
)

data class User(
    val id: Int,
    val name: String,
    val role: String,
    val email: String,
    val phone: String?,
    @SerializedName("avatar_url")
    val avatarUrl: String?,
    @SerializedName("email_verified_at")
    val emailVerifiedAt: String?,
    @SerializedName("created_at")
    val createdAt: String,
    @SerializedName("updated_at")
    val updatedAt: String,
)
