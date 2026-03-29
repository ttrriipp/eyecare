package com.example.opticalsystem.ui.orders

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.model.Order
import com.example.opticalsystem.data.repository.OrderRepository
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class OrderDetailViewModel @Inject constructor(
    private val orderRepository: OrderRepository,
) : ViewModel() {

    private val _order = MutableLiveData<Resource<Order>>()
    val order: LiveData<Resource<Order>> = _order

    private val _cancelResult = MutableLiveData<Resource<Order>>()
    val cancelResult: LiveData<Resource<Order>> = _cancelResult

    private var orderId: Int = -1

    fun loadOrder(id: Int) {
        orderId = id
        _order.value = Resource.Loading
        viewModelScope.launch {
            _order.value = orderRepository.getOrder(orderId)
        }
    }

    fun cancelOrder() {
        if (orderId == -1) return
        _cancelResult.value = Resource.Loading
        viewModelScope.launch {
            val result = orderRepository.cancelOrder(orderId)
            _cancelResult.value = result
            if (result is Resource.Success) {
                _order.value = result
            }
        }
    }
}
