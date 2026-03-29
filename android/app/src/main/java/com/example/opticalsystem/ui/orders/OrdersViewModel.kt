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
class OrdersViewModel @Inject constructor(
    private val orderRepository: OrderRepository,
) : ViewModel() {

    private val _orders = MutableLiveData<Resource<List<Order>>>()
    val orders: LiveData<Resource<List<Order>>> = _orders

    private var currentStatus: String? = null

    init {
        loadOrders()
    }

    fun loadOrders(status: String? = currentStatus) {
        currentStatus = status
        _orders.value = Resource.Loading
        viewModelScope.launch {
            when (val result = orderRepository.getOrders(status = status)) {
                is Resource.Success -> _orders.value = Resource.Success(result.data.first)
                is Resource.Error -> _orders.value = Resource.Error(result.message)
                is Resource.Loading -> {}
            }
        }
    }

    fun filterByStatus(status: String?) {
        loadOrders(status)
    }

    fun refresh() {
        loadOrders()
    }
}
