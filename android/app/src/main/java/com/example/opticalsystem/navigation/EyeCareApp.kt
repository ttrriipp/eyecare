package com.example.opticalsystem.navigation

import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Scaffold
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.lifecycle.compose.LocalLifecycleOwner
import androidx.fragment.app.FragmentActivity
import androidx.fragment.app.FragmentResultListener
import androidx.navigation.NavHostController
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.example.opticalsystem.notifications.OrderStatusNotifier
import com.example.opticalsystem.ui.main.MainBottomBar
import com.example.opticalsystem.ui.main.isMainTabRoute
import com.example.opticalsystem.ui.theme.EyeCareTheme
import kotlinx.coroutines.channels.Channel

@Composable
fun EyeCareApp(
    startDestination: String,
    orderStatusNotifier: OrderStatusNotifier,
    openOrderDetailChannel: Channel<Int>,
) {
    EyeCareTheme {
        val navController = rememberNavController()
        val activity = LocalContext.current as FragmentActivity
        val fragmentManager = activity.supportFragmentManager
        val lifecycleOwner = LocalLifecycleOwner.current

        LaunchedEffect(navController) {
            for (orderId in openOrderDetailChannel) {
                if (orderId > 0) {
                    navController.navigate(AppRoutes.orderDetail(orderId))
                }
            }
        }

        DisposableEffect(navController, fragmentManager, lifecycleOwner) {
            val listener = FragmentResultListener { _, bundle ->
                val id = bundle.getInt(FragmentNavBridge.KEY_CONVERSATION_ID, 0)
                if (id > 0) {
                    navController.navigate(AppRoutes.conversation(id)) {
                        popUpTo(AppRoutes.CHAT) { inclusive = false }
                    }
                }
            }
            fragmentManager.setFragmentResultListener(
                FragmentNavBridge.OPEN_CONVERSATION,
                lifecycleOwner,
                listener,
            )
            onDispose {
                fragmentManager.clearFragmentResultListener(FragmentNavBridge.OPEN_CONVERSATION)
            }
        }

        val backStackEntry by navController.currentBackStackEntryAsState()
        val currentRoute = backStackEntry?.destination?.route
        val showBottomBar = isMainTabRoute(currentRoute)

        Scaffold(
            bottomBar = {
                if (showBottomBar) {
                    MainBottomBar(navController = navController)
                }
            },
        ) { innerPadding ->
            EyeCareNavHost(
                navController = navController,
                startDestination = startDestination,
                orderStatusNotifier = orderStatusNotifier,
                modifier = Modifier.padding(innerPadding),
            )
        }
    }
}
