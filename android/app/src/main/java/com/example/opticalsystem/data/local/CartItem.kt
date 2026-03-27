package com.example.opticalsystem.data.local

data class CartItem(
    val productId: Int,
    val productName: String,
    val productBrand: String?,
    val productPrice: String,
    val productImageUrl: String?,
    val quantity: Int = 1,
)
