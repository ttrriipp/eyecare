package com.example.opticalsystem.data.repository
import android.util.Log
import com.example.opticalsystem.data.api.AuthApi
import com.example.opticalsystem.data.model.LoginRequest
import com.example.opticalsystem.data.model.RegisterRequest
import com.example.opticalsystem.data.model.UpdateProfileRequest
import com.example.opticalsystem.data.model.User
import com.example.opticalsystem.util.Resource
import com.example.opticalsystem.util.TokenManager
import org.json.JSONObject
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    private val authApi: AuthApi,
    private val tokenManager: TokenManager,
) {
    companion object {
        private const val TAG = "AuthRepository"
    }

    private fun networkError(e: Exception, fallback: String): String {
        val msg = e.message?.trim()
        return if (!msg.isNullOrEmpty()) {
            "Network error (${e.javaClass.simpleName}): $msg"
        } else {
            "Network error (${e.javaClass.simpleName})"
        }.ifBlank { fallback }
    }

    suspend fun login(email: String, password: String): Resource<User> {
        return try {
            val response = authApi.login(LoginRequest(email, password))
            if (response.isSuccessful && response.body() != null) {
                val body = response.body()!!
                tokenManager.saveToken(body.token)
                Resource.Success(body.user)
            } else {
                Resource.Error(parseError(response.code(), response.errorBody()?.string(), "Login failed"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "login failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
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
                Resource.Error(parseError(response.code(), response.errorBody()?.string(), "Registration failed"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "register failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
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

    suspend fun getProfile(): Resource<User> {
        return try {
            val response = authApi.profile()
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.user)
            } else {
                Resource.Error(parseError(response.code(), response.errorBody()?.string(), "Failed to load profile"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "profile failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }

    suspend fun updateProfile(
        name: String,
        phone: String,
        dateOfBirth: String?,
        address: String?,
    ): Resource<User> {
        return try {
            val body = UpdateProfileRequest(
                name = name.trim(),
                phone = phone.trim(),
                dateOfBirth = dateOfBirth?.trim()?.ifBlank { null },
                address = address?.trim()?.ifBlank { null },
            )
            val response = authApi.updateProfile(body)
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.user)
            } else {
                Resource.Error(
                    parseError(
                        response.code(),
                        response.errorBody()?.string(),
                        "Failed to update profile",
                    ),
                )
            }
        } catch (e: Exception) {
            Log.e(TAG, "updateProfile failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }

    private fun parseError(code: Int, rawBody: String?, fallback: String): String {
        val body = rawBody?.trim().orEmpty()
        if (body.isNotEmpty()) {
            // Laravel commonly returns { "message": "..." } (and sometimes nested errors)
            runCatching {
                val json = JSONObject(body)
                val message = json.optString("message").trim()
                if (message.isNotEmpty()) return message
            }
        }
        return if (code >= 500) "Server error ($code). Check backend logs." else fallback
    }
}
