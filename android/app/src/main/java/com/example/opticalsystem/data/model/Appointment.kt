package com.example.opticalsystem.data.model

import com.google.gson.annotations.SerializedName

data class AppointmentListResponse(
    val data: List<Appointment>,
)

data class Appointment(
    val id: Int,
    @SerializedName("scheduled_at")
    val scheduledAt: String? = null,
    val status: String? = null,
    @SerializedName("appointment_type")
    val appointmentType: String? = null,
    @SerializedName("doctor_name")
    val doctorName: String? = null,
)
