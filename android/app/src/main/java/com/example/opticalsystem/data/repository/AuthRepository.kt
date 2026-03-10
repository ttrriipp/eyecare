package com.example.opticalsystem.data.repository

import com.example.opticalsystem.data.api.AuthApi
import com.example.opticalsystem.data.model.LoginRequest
import com.example.opticalsystem.data.model.RegisterRequest
import com.example.opticalsystem.data.model.User
import com.example.opticalsystem.util.Resource
import com.example.opticalsystem.util.TokenManager
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    private val authApi: AuthApi,
    private val tokenManager: TokenManager,
) {
    suspend fun login(email: String, password: String): Resource<User> {
        return try {
            val response = authApi.login(LoginRequest(email, password))
            if (response.isSuccessful && response.body() != null) {
                val body = response.body()!!
                tokenManager.saveToken(body.token)
                Resource.Success(body.user)
            } else {
                Resource.Error(response.errorBody()?.string() ?: "Login failed")
            }
        } catch (e: Exception) {
            Resource.Error(e.message ?: "Network error")
        }
    }

    suspend fun register(
        name: String,
        email: String,
        phone: String?,
        password: String,
        passwordConfirmation: String,
    ): Resource<User> {
        return try {
            val response = authApi.register(
                RegisterRequest(name, email, phone, password, passwordConfirmation)
            )
            if (response.isSuccessful && response.body() != null) {
                val body = response.body()!!
                tokenManager.saveToken(body.token)
                Resource.Success(body.user)
            } else {
                Resource.Error(response.errorBody()?.string() ?: "Registration failed")
            }
        } catch (e: Exception) {
            Resource.Error(e.message ?: "Network error")
        }
    }

    suspend fun logout(): Resource<Unit> {
        return try {
            authApi.logout()
            tokenManager.clearToken()
            Resource.Success(Unit)
        } catch (e: Exception) {
            tokenManager.clearToken()
            Resource.Success(Unit)
        }
    }

    suspend fun isLoggedIn(): Boolean {
        return tokenManager.isLoggedIn()
    }
}
