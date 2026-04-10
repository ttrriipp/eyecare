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

    private val _activeCount = MutableLiveData(0)
    val activeCount: LiveData<Int> = _activeCount

    private val _pastCount = MutableLiveData(0)
    val pastCount: LiveData<Int> = _pastCount

    private var allOrders: List<Order> = emptyList()
    var showingActive: Boolean = true
        private set

    private companion object {
        val ACTIVE_STATUSES = setOf("pending", "confirmed", "ready_for_pickup")
        val PAST_STATUSES = setOf("completed", "cancelled")
    }

    init {
        loadOrders()
    }

    fun loadOrders() {
        _orders.value = Resource.Loading
        viewModelScope.launch {
            when (val result = orderRepository.getOrders(status = null)) {
                is Resource.Success -> {
                    allOrders = result.data.first
                    _activeCount.value = allOrders.count { it.status in ACTIVE_STATUSES }
                    _pastCount.value = allOrders.count { it.status in PAST_STATUSES }
                    applyFilter()
                }
                is Resource.Error -> _orders.value = Resource.Error(result.message)
                is Resource.Loading -> {}
            }
        }
    }

    fun showActive() {
        showingActive = true
        applyFilter()
    }

    fun showPast() {
        showingActive = false
        applyFilter()
    }

    fun refresh() {
        loadOrders()
    }

    private fun applyFilter() {
        val filtered = if (showingActive) {
            allOrders.filter { it.status in ACTIVE_STATUSES }
        } else {
            allOrders.filter { it.status in PAST_STATUSES }
        }
        _orders.value = Resource.Success(filtered)
    }
}
