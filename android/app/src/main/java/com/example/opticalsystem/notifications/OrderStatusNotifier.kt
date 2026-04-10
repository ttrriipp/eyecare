package com.example.opticalsystem.notifications

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import com.example.opticalsystem.MainActivity
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Order
import com.example.opticalsystem.data.repository.OrderRepository
import com.example.opticalsystem.util.Resource
import com.example.opticalsystem.util.TokenManager
import dagger.hilt.android.qualifiers.ApplicationContext
import org.json.JSONArray
import org.json.JSONObject
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class OrderStatusNotifier @Inject constructor(
    @ApplicationContext private val context: Context,
    private val orderRepository: OrderRepository,
    private val tokenManager: TokenManager,
) {
    data class InAppNotification(
        val orderId: Int,
        val orderNumber: String,
        val statusLabel: String,
        val changedAtMillis: Long,
    )

    data class StatusChange(
        val orderId: Int,
        val orderNumber: String,
        val fromStatus: String,
        val toStatus: String,
        val statusLabel: String,
    )

    private val prefs = context.getSharedPreferences("order_status_notifications", Context.MODE_PRIVATE)

    fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
        val channel = NotificationChannel(
            CHANNEL_ID,
            context.getString(R.string.order_status_channel_name),
            NotificationManager.IMPORTANCE_DEFAULT,
        ).apply {
            description = context.getString(R.string.order_status_channel_description)
        }
        val manager = context.getSystemService(NotificationManager::class.java)
        manager.createNotificationChannel(channel)
    }

    suspend fun checkForStatusChanges(): List<StatusChange> {
        if (!tokenManager.isLoggedIn()) return emptyList()

        val allOrders = fetchAllOrders()
        if (allOrders.isEmpty()) return emptyList()

        val changes = mutableListOf<StatusChange>()
        val editor = prefs.edit()

        allOrders.forEach { order ->
            val key = statusKey(order.id)
            val previous = prefs.getString(key, null)
            val current = order.status
            val shouldSuppress = consumeUserInitiatedStatusSuppression(order.id, current)

            if (shouldSuppress) {
                editor.putString(key, current)
                return@forEach
            }

            val currentNormalized = current.trim().lowercase()
            val previousNormalized = previous?.trim()?.lowercase()

            val shouldNotify = when {
                previousNormalized != null && previousNormalized != currentNormalized -> true
                // First time seeing an order: do not notify for user-origin statuses.
                previousNormalized == null && currentNormalized !in NON_NOTIFY_INITIAL_STATUSES -> true
                else -> false
            }

            if (shouldNotify) {
                changes += StatusChange(
                    orderId = order.id,
                    orderNumber = order.orderNumber,
                    fromStatus = previous ?: DEFAULT_INITIAL_STATUS,
                    toStatus = current,
                    statusLabel = order.statusLabel,
                )
                addInAppNotification(
                    InAppNotification(
                        orderId = order.id,
                        orderNumber = order.orderNumber,
                        statusLabel = order.statusLabel,
                        changedAtMillis = System.currentTimeMillis(),
                    ),
                )
                notifyStatusChanged(order)
            }

            editor.putString(key, current)
        }

        editor.apply()
        return changes
    }

    fun suppressNextUserInitiatedStatus(orderId: Int, status: String) {
        prefs.edit().putString(userInitiatedSuppressionKey(orderId), status).apply()
    }

    private suspend fun fetchAllOrders(): List<Order> {
        val collected = mutableListOf<Order>()
        var page = 1
        val perPage = 50
        var total = Int.MAX_VALUE

        while (collected.size < total && page <= MAX_PAGES_TO_SCAN) {
            when (val result = orderRepository.getOrders(page = page, perPage = perPage, status = null, search = null)) {
                is Resource.Success -> {
                    val orders = result.data.first
                    total = result.data.second
                    if (orders.isEmpty()) break
                    collected.addAll(orders)
                }
                else -> break
            }
            page++
        }

        return collected.distinctBy { it.id }
    }

    fun getRecentNotifications(limit: Int = 50): List<InAppNotification> {
        val raw = prefs.getString(KEY_NOTIFICATION_HISTORY, null) ?: return emptyList()
        return runCatching {
            val array = JSONArray(raw)
            buildList {
                for (i in 0 until array.length()) {
                    val obj = array.optJSONObject(i) ?: continue
                    add(
                        InAppNotification(
                            orderId = obj.optInt("order_id"),
                            orderNumber = obj.optString("order_number"),
                            statusLabel = obj.optString("status_label"),
                            changedAtMillis = obj.optLong("changed_at_millis"),
                        ),
                    )
                }
            }
        }.getOrDefault(emptyList())
            .sortedByDescending { it.changedAtMillis }
            .take(limit)
    }

    fun hasUnreadNotifications(): Boolean {
        return prefs.getBoolean(KEY_HAS_UNREAD, false)
    }

    fun markNotificationsRead() {
        prefs.edit().putBoolean(KEY_HAS_UNREAD, false).apply()
    }

    fun clearAllNotifications() {
        prefs.edit()
            .remove(KEY_NOTIFICATION_HISTORY)
            .putBoolean(KEY_HAS_UNREAD, false)
            .apply()
    }

    private fun addInAppNotification(entry: InAppNotification) {
        val existing = getRecentNotifications(limit = 200).toMutableList()
        existing.add(0, entry)
        val trimmed = existing.distinctBy { "${it.orderId}:${it.statusLabel}:${it.changedAtMillis}" }.take(200)

        val array = JSONArray()
        trimmed.forEach { item ->
            array.put(
                JSONObject().apply {
                    put("order_id", item.orderId)
                    put("order_number", item.orderNumber)
                    put("status_label", item.statusLabel)
                    put("changed_at_millis", item.changedAtMillis)
                },
            )
        }
        prefs.edit()
            .putString(KEY_NOTIFICATION_HISTORY, array.toString())
            .putBoolean(KEY_HAS_UNREAD, true)
            .apply()
    }

    private fun notifyStatusChanged(order: Order) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            val granted = ContextCompat.checkSelfPermission(
                context,
                Manifest.permission.POST_NOTIFICATIONS,
            ) == PackageManager.PERMISSION_GRANTED
            if (!granted) return
        }

        val intent = Intent(context, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            putExtra(EXTRA_ORDER_ID, order.id)
            putExtra(EXTRA_ORDER_NUMBER, order.orderNumber)
            putExtra(EXTRA_ORDER_STATUS_LABEL, order.statusLabel)
        }
        val pendingIntent = PendingIntent.getActivity(
            context,
            order.id,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )

        val notification = NotificationCompat.Builder(context, CHANNEL_ID)
            .setSmallIcon(R.drawable.ic_order_status_notification)
            .setContentTitle(context.getString(R.string.order_status_changed_title))
            .setContentText(
                context.getString(
                    R.string.order_status_changed_message,
                    order.orderNumber,
                    order.statusLabel,
                ),
            )
            .setStyle(
                NotificationCompat.BigTextStyle().bigText(
                    context.getString(
                        R.string.order_status_changed_message,
                        order.orderNumber,
                        order.statusLabel,
                    ),
                ),
            )
            .setPriority(NotificationCompat.PRIORITY_DEFAULT)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .build()

        NotificationManagerCompat.from(context).notify(order.id + NOTIFICATION_ID_OFFSET, notification)
    }

    private fun statusKey(orderId: Int): String = "order_status_$orderId"

    private fun userInitiatedSuppressionKey(orderId: Int): String = "user_initiated_status_suppression_$orderId"

    private fun consumeUserInitiatedStatusSuppression(orderId: Int, currentStatus: String): Boolean {
        val key = userInitiatedSuppressionKey(orderId)
        val suppressedStatus = prefs.getString(key, null) ?: return false
        val shouldSuppress = suppressedStatus.equals(currentStatus, ignoreCase = true)
        if (shouldSuppress) {
            prefs.edit().remove(key).apply()
        }
        return shouldSuppress
    }

    companion object {
        const val CHANNEL_ID = "order_status_updates"
        const val EXTRA_ORDER_ID = "extra_order_id"
        const val EXTRA_ORDER_NUMBER = "extra_order_number"
        const val EXTRA_ORDER_STATUS_LABEL = "extra_order_status_label"
        private const val NOTIFICATION_ID_OFFSET = 5000
        private const val KEY_NOTIFICATION_HISTORY = "notification_history"
        private const val KEY_HAS_UNREAD = "has_unread"
        private const val MAX_PAGES_TO_SCAN = 20
        private const val DEFAULT_INITIAL_STATUS = "pending"
        private val NON_NOTIFY_INITIAL_STATUSES = setOf("pending", "requested", "cancelled")
    }
}
