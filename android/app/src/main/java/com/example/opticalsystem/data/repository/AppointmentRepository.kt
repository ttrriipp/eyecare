package com.example.opticalsystem.data.repository

import android.util.Log
import com.example.opticalsystem.data.api.AppointmentApi
import com.example.opticalsystem.data.model.Appointment
import com.example.opticalsystem.util.Resource
import org.json.JSONObject
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AppointmentRepository @Inject constructor(
    private val appointmentApi: AppointmentApi,
) {
    companion object {
        private const val TAG = "AppointmentRepository"
    }

    suspend fun getUpcomingAppointments(): Resource<List<Appointment>> {
        return try {
            val response = appointmentApi.getAppointments()
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.data)
            } else {
                Resource.Error(parseError(response.errorBody()?.string(), "Failed to load appointments"))
            }
        } catch (e: Exception) {
            Log.e(TAG, "getUpcomingAppointments failed", e)
            Resource.Error("Network error (${e.javaClass.simpleName}): ${e.message ?: "Unknown error"}")
        }
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
}
