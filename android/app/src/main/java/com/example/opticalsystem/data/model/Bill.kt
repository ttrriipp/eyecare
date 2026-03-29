package com.example.opticalsystem.data.model

import com.google.gson.annotations.SerializedName

data class Bill(
    val id: Int,
    @SerializedName("order_id")
    val orderId: Int?,
    val order: Order?,
    @SerializedName("appointment_id")
    val appointmentId: Int?,
    @SerializedName("invoice_number")
    val invoiceNumber: String,
    val amount: String,
    @SerializedName("payment_status")
    val paymentStatus: String,
    @SerializedName("payment_status_label")
    val paymentStatusLabel: String,
    @SerializedName("payment_method")
    val paymentMethod: String?,
    @SerializedName("paid_at")
    val paidAt: String?,
    @SerializedName("created_at")
    val createdAt: String,
    @SerializedName("updated_at")
    val updatedAt: String,
)

data class BillListResponse(
    val data: List<Bill>,
    val meta: PaginationMeta?,
)

data class BillDetailResponse(
    val data: Bill,
)
