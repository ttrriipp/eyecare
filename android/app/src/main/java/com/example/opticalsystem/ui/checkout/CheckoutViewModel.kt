package com.example.opticalsystem.ui.checkout

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.local.CartItem
import com.example.opticalsystem.data.local.CartManager
import com.example.opticalsystem.data.model.CreateOrderRequest
import com.example.opticalsystem.data.model.Order
import com.example.opticalsystem.data.model.OrderItemRequest
import com.example.opticalsystem.data.repository.OrderRepository
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class CheckoutViewModel @Inject constructor(
    private val cartManager: CartManager,
    private val orderRepository: OrderRepository,
) : ViewModel() {

    val cartItems: StateFlow<List<CartItem>> = cartManager.cartItems
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), emptyList())

    val totalPrice: StateFlow<Double> = cartManager.cartItems
        .map { items ->
            items.sumOf { item ->
                val price = item.productPrice.toDoubleOrNull() ?: 0.0
                price * item.quantity
            }
        }
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), 0.0)

    val itemCount: StateFlow<Int> = cartManager.cartItems
        .map { items -> items.sumOf { it.quantity } }
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), 0)

    private val _orderResult = MutableLiveData<Resource<Order>>()
    val orderResult: LiveData<Resource<Order>> = _orderResult

    fun placeOrder(notes: String?) {
        val items = cartItems.value
        if (items.isEmpty()) return

        _orderResult.value = Resource.Loading
        viewModelScope.launch {
            val request = CreateOrderRequest(
                items = items.map { OrderItemRequest(it.productId, it.quantity) },
                notes = notes?.takeIf { it.isNotBlank() },
            )
            val result = orderRepository.createOrder(request)
            if (result is Resource.Success) {
                cartManager.clearCart()
            }
            _orderResult.value = result
        }
    }
}
