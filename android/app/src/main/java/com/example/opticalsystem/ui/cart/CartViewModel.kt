package com.example.opticalsystem.ui.cart

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.local.CartItem
import com.example.opticalsystem.data.local.CartManager
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class CartViewModel @Inject constructor(
    private val cartManager: CartManager,
) : ViewModel() {

    val cartItems: StateFlow<List<CartItem>> = cartManager.cartItems
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val itemCount: StateFlow<Int> = cartManager.cartItems
        .map { items -> items.sumOf { it.quantity } }
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), 0)

    val totalPrice: StateFlow<Double> = cartManager.cartItems
        .map { items ->
            items.sumOf { item ->
                val price = item.productPrice.toDoubleOrNull() ?: 0.0
                price * item.quantity
            }
        }
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), 0.0)

    fun increase(item: CartItem) {
        viewModelScope.launch {
            cartManager.updateQuantity(item.productId, item.productVariantId, item.quantity + 1)
        }
    }

    fun decrease(item: CartItem) {
        viewModelScope.launch {
            if (item.quantity > 1) {
                cartManager.updateQuantity(item.productId, item.productVariantId, item.quantity - 1)
            } else {
                cartManager.removeFromCart(item.productId, item.productVariantId)
            }
        }
    }

    fun remove(item: CartItem) {
        viewModelScope.launch {
            cartManager.removeFromCart(item.productId, item.productVariantId)
        }
    }

    fun clearCart() {
        viewModelScope.launch {
            cartManager.clearCart()
        }
    }
}
