package com.example.opticalsystem.data.model

import com.google.gson.annotations.SerializedName

data class Order(
    val id: Int,
    @SerializedName("user_id")
    val userId: Int?,
    @SerializedName("order_number")
    val orderNumber: String,
    val status: String,
    @SerializedName("status_label")
    val statusLabel: String,
    @SerializedName("total_amount")
    val totalAmount: String,
    val notes: String?,
    val items: List<OrderItem>?,
    @SerializedName("is_walk_in")
    val isWalkIn: Boolean,
    @SerializedName("walk_in_name")
    val walkInName: String?,
    @SerializedName("walk_in_phone")
    val walkInPhone: String?,
    @SerializedName("created_at")
    val createdAt: String,
    @SerializedName("updated_at")
    val updatedAt: String,
)

data class OrderItem(
    val id: Int,
    @SerializedName("product_id")
    val productId: Int,
    val product: Product?,
    val quantity: Int,
    @SerializedName("unit_price")
    val unitPrice: String,
    val subtotal: String,
)

data class CreateOrderRequest(
    val items: List<OrderItemRequest>,
    @SerializedName("appointment_id")
    val appointmentId: Int? = null,
    val notes: String?,
)

data class OrderItemRequest(
    @SerializedName("product_variant_id")
    val productVariantId: Int,
    val quantity: Int,
)

data class OrderListResponse(
    val data: List<Order>,
    val meta: PaginationMeta?,
)

data class OrderDetailResponse(
    val data: Order,
)

data class CreateOrderResponse(
    val message: String,
    val order: Order,
)
