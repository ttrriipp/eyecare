package com.example.opticalsystem.ui.checkout

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.model.Appointment
import com.example.opticalsystem.data.local.CartItem
import com.example.opticalsystem.data.local.CartManager
import com.example.opticalsystem.data.model.CreateOrderRequest
import com.example.opticalsystem.data.model.Order
import com.example.opticalsystem.data.model.OrderItemRequest
import com.example.opticalsystem.data.model.User
import com.example.opticalsystem.data.repository.AppointmentRepository
import com.example.opticalsystem.data.repository.AuthRepository
import com.example.opticalsystem.data.repository.OrderRepository
import com.example.opticalsystem.notifications.OrderStatusNotifier
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class CheckoutViewModel @Inject constructor(
    private val cartManager: CartManager,
    private val orderRepository: OrderRepository,
    private val authRepository: AuthRepository,
    private val appointmentRepository: AppointmentRepository,
    private val orderStatusNotifier: OrderStatusNotifier,
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

    private val _orderResult = MutableLiveData<Resource<Order>?>(null)
    val orderResult: LiveData<Resource<Order>?> = _orderResult
    private val _upcomingAppointments = MutableLiveData<Resource<List<Appointment>>>()
    val upcomingAppointments: LiveData<Resource<List<Appointment>>> = _upcomingAppointments
    private val _profile = MutableLiveData<Resource<User>>()
    val profile: LiveData<Resource<User>> = _profile
    val selectedAppointmentId = MutableStateFlow<Int?>(null)
    val orderNotes = MutableStateFlow("")

    fun loadOrderDetailsData() {
        viewModelScope.launch {
            _profile.value = Resource.Loading
            _profile.value = authRepository.getProfile()
        }
        viewModelScope.launch {
            _upcomingAppointments.value = Resource.Loading
            _upcomingAppointments.value = appointmentRepository.getUpcomingAppointments()
        }
    }

    fun selectAppointment(appointmentId: Int?) {
        selectedAppointmentId.value = appointmentId
    }

    fun setOrderNotes(notes: String) {
        orderNotes.value = notes
    }

    fun placeOrder(notes: String?) {
        val items = cartItems.value
        if (items.isEmpty()) return

        _orderResult.value = Resource.Loading
        viewModelScope.launch {
            val request = CreateOrderRequest(
                items = items.map { OrderItemRequest(it.productVariantId, it.quantity) },
                appointmentId = selectedAppointmentId.value,
                notes = notes?.takeIf { it.isNotBlank() },
            )
            val result = orderRepository.createOrder(request)
            if (result is Resource.Success) {
                orderStatusNotifier.suppressNextUserInitiatedStatus(
                    orderId = result.data.id,
                    status = result.data.status,
                )
                cartManager.clearCart()
            }
            _orderResult.value = result
        }
    }

    fun clearOrderResult() {
        _orderResult.value = null
    }
}
