package com.example.opticalsystem

import android.Manifest
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import com.example.opticalsystem.navigation.AppRoutes
import com.example.opticalsystem.navigation.EyeCareApp
import com.example.opticalsystem.notifications.OrderStatusNotifier
import com.example.opticalsystem.util.TokenManager
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.channels.Channel
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import kotlinx.coroutines.runBlocking
import javax.inject.Inject

@AndroidEntryPoint
class MainActivity : AppCompatActivity() {

    @Inject
    lateinit var tokenManager: TokenManager

    @Inject
    lateinit var orderStatusNotifier: OrderStatusNotifier

    private val notificationPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestPermission(),
    ) { /* no-op: notifier checks permission before sending */ }

    private val openOrderDetailChannel = Channel<Int>(Channel.CONFLATED)

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        val loggedIn = runBlocking { tokenManager.isLoggedIn() }
        val startDestination = if (loggedIn) AppRoutes.MAIN_GRAPH else AppRoutes.LOGIN

        setContent {
            EyeCareApp(
                startDestination = startDestination,
                orderStatusNotifier = orderStatusNotifier,
                openOrderDetailChannel = openOrderDetailChannel,
            )
        }

        orderStatusNotifier.createNotificationChannel()
        requestNotificationPermissionIfNeeded()

        dispatchOrderDetailNavigationIntent()
        startOrderStatusMonitoring()
        maybeShowStatusPopupFromNotification()
    }

    override fun onNewIntent(intent: android.content.Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        dispatchOrderDetailNavigationIntent()
        maybeShowStatusPopupFromNotification()
    }

    private fun dispatchOrderDetailNavigationIntent() {
        val orderId = intent?.getIntExtra(OrderStatusNotifier.EXTRA_ORDER_ID, -1) ?: -1
        if (orderId <= 0) return
        lifecycleScope.launch {
            openOrderDetailChannel.send(orderId)
        }
        intent?.removeExtra(OrderStatusNotifier.EXTRA_ORDER_ID)
    }

    private fun startOrderStatusMonitoring() {
        lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                while (isActive) {
                    val changes = orderStatusNotifier.checkForStatusChanges()
                    val newest = changes.firstOrNull()
                    if (newest != null) {
                        showOrderStatusPopup(
                            orderNumber = newest.orderNumber,
                            statusLabel = newest.statusLabel,
                        )
                    }
                    delay(15_000)
                }
            }
        }
    }

    private fun maybeShowStatusPopupFromNotification() {
        val orderNumber = intent?.getStringExtra(OrderStatusNotifier.EXTRA_ORDER_NUMBER) ?: return
        val statusLabel = intent?.getStringExtra(OrderStatusNotifier.EXTRA_ORDER_STATUS_LABEL) ?: return
        showOrderStatusPopup(orderNumber, statusLabel)
        intent?.removeExtra(OrderStatusNotifier.EXTRA_ORDER_NUMBER)
        intent?.removeExtra(OrderStatusNotifier.EXTRA_ORDER_STATUS_LABEL)
    }

    private fun showOrderStatusPopup(orderNumber: String, statusLabel: String) {
        if (isFinishing || isDestroyed) return
        AlertDialog.Builder(this)
            .setTitle(getString(R.string.order_status_popup_title))
            .setMessage(getString(R.string.order_status_popup_message, orderNumber, statusLabel))
            .setPositiveButton(android.R.string.ok, null)
            .show()
    }

    private fun requestNotificationPermissionIfNeeded() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU) return
        val granted = ContextCompat.checkSelfPermission(
            this,
            Manifest.permission.POST_NOTIFICATIONS,
        ) == PackageManager.PERMISSION_GRANTED
        if (!granted) {
            notificationPermissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
        }
    }
}
