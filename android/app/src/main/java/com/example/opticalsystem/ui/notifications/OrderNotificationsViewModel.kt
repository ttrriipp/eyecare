package com.example.opticalsystem.ui.notifications

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import com.example.opticalsystem.notifications.OrderStatusNotifier
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject

@HiltViewModel
class OrderNotificationsViewModel @Inject constructor(
    private val orderStatusNotifier: OrderStatusNotifier,
) : ViewModel() {

    private val _notifications = MutableLiveData<List<OrderStatusNotifier.InAppNotification>>(emptyList())
    val notifications: LiveData<List<OrderStatusNotifier.InAppNotification>> = _notifications

    fun loadNotifications() {
        orderStatusNotifier.markNotificationsRead()
        _notifications.value = orderStatusNotifier.getRecentNotifications()
    }

    fun clearAllNotifications() {
        orderStatusNotifier.clearAllNotifications()
        _notifications.value = emptyList()
    }
}
