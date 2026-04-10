package com.example.opticalsystem

import android.Manifest
import android.content.pm.PackageManager
import android.os.Bundle
import android.os.Build
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.appcompat.app.AlertDialog
import androidx.core.os.bundleOf
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.navigation.fragment.NavHostFragment
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.lifecycle.Lifecycle
import com.example.opticalsystem.notifications.OrderStatusNotifier
import com.example.opticalsystem.util.TokenManager
import dagger.hilt.android.AndroidEntryPoint
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

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContentView(R.layout.activity_main)
        orderStatusNotifier.createNotificationChannel()
        requestNotificationPermissionIfNeeded()
        // Top/side insets only. Do not pad the root bottom — that leaves a strip above the
        // system nav and makes BottomNavigationView look "floating". Navigation bar inset is
        // applied on BottomNavigationView in MainFragment (and auth screens handle bottom inset).
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main)) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, 0)
            insets
        }

        // Auto-login: choose nav graph start destination before first draw
        // so the login screen doesn't flash when reopening the app.
        if (savedInstanceState == null) {
            val navHostFragment = supportFragmentManager
                .findFragmentById(R.id.nav_host_fragment) as NavHostFragment
            val navController = navHostFragment.navController

            val loggedIn = runBlocking { tokenManager.isLoggedIn() }
            val navGraph = navController.navInflater.inflate(R.navigation.nav_graph)
            // Navigation graph uses `loginFragment -> mainFragment` action when you navigate,
            // but for app reopen we just choose the correct start destination up-front.
            navGraph.setStartDestination(if (loggedIn) R.id.mainFragment else R.id.loginFragment)
            navController.graph = navGraph
        }

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
        supportFragmentManager.setFragmentResult(
            REQUEST_OPEN_ORDER_DETAIL,
            bundleOf(KEY_ORDER_ID to orderId),
        )
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

    private companion object {
        const val REQUEST_OPEN_ORDER_DETAIL = "request_open_order_detail"
        const val KEY_ORDER_ID = "order_id"
    }
}

