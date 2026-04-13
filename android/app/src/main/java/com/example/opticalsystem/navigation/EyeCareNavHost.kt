package com.example.opticalsystem.navigation

import android.widget.Toast
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.imePadding
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.livedata.observeAsState
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.lifecycle.compose.LocalLifecycleOwner
import androidx.core.os.bundleOf
import androidx.fragment.app.FragmentActivity
import androidx.fragment.compose.AndroidFragment
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.navigation
import androidx.navigation.navArgument
import com.example.opticalsystem.R
import com.example.opticalsystem.notifications.OrderStatusNotifier
import com.example.opticalsystem.ui.auth.LoginScreen
import com.example.opticalsystem.ui.auth.LoginViewModel
import com.example.opticalsystem.ui.auth.RegisterScreen
import com.example.opticalsystem.ui.auth.RegisterViewModel
import com.example.opticalsystem.ui.bills.BillDetailScreen
import com.example.opticalsystem.ui.bills.BillDetailViewModel
import com.example.opticalsystem.ui.bills.BillsScreen
import com.example.opticalsystem.ui.bills.BillsViewModel
import com.example.opticalsystem.ui.cart.CartScreen
import com.example.opticalsystem.ui.cart.CartViewModel
import com.example.opticalsystem.ui.checkout.CheckoutScreen
import com.example.opticalsystem.ui.checkout.CheckoutViewModel
import com.example.opticalsystem.ui.checkout.OrderConfirmScreen
import com.example.opticalsystem.ui.checkout.OrderPlacedScreen
import com.example.opticalsystem.ui.home.HomeScreen
import com.example.opticalsystem.ui.home.HomeViewModel
import com.example.opticalsystem.ui.messages.ConversationThreadFragment
import com.example.opticalsystem.ui.messages.MessagesFragment
import com.example.opticalsystem.ui.notifications.OrderNotificationsScreen
import com.example.opticalsystem.ui.notifications.OrderNotificationsViewModel
import com.example.opticalsystem.ui.orders.OrderDetailScreen
import com.example.opticalsystem.ui.orders.OrderDetailViewModel
import com.example.opticalsystem.ui.orders.OrdersScreen
import com.example.opticalsystem.ui.orders.OrdersViewModel
import com.example.opticalsystem.ui.products.ProductDetailScreen
import com.example.opticalsystem.ui.products.ProductDetailViewModel
import com.example.opticalsystem.ui.products.ProductListScreen
import com.example.opticalsystem.ui.products.ProductListViewModel
import com.example.opticalsystem.ui.profile.EditProfileScreen
import com.example.opticalsystem.ui.profile.EditProfileViewModel
import com.example.opticalsystem.ui.profile.ProfileScreen
import com.example.opticalsystem.ui.profile.ProfileViewModel
import com.example.opticalsystem.ui.schedule.SchedulePlaceholderScreen

@Composable
fun EyeCareNavHost(
    navController: NavHostController,
    startDestination: String,
    orderStatusNotifier: OrderStatusNotifier,
    modifier: Modifier = Modifier,
) {
    NavHost(
        navController = navController,
        startDestination = startDestination,
        modifier = modifier.fillMaxSize(),
    ) {
        composable(AppRoutes.LOGIN) {
            val vm = hiltViewModel<LoginViewModel>()
            val ctx = LocalContext.current
            val backendRootUrl = ctx.getString(R.string.backend_root_url)
            val logoPath = ctx.getString(R.string.login_logo_public_path)
            val logoUrl = remember(backendRootUrl, logoPath) {
                backendRootUrl.trimEnd('/') + "/" + logoPath.trimStart('/')
            }
            LoginScreen(
                viewModel = vm,
                logoUrl = logoUrl,
                onNavigateToRegister = { navController.navigate(AppRoutes.REGISTER) },
                onNavigateToMain = {
                    navController.navigate(AppRoutes.MAIN_GRAPH) {
                        popUpTo(AppRoutes.LOGIN) { inclusive = true }
                    }
                },
            )
        }
        composable(AppRoutes.REGISTER) {
            val vm = hiltViewModel<RegisterViewModel>()
            val ctx = LocalContext.current
            val backendRootUrl = ctx.getString(R.string.backend_root_url)
            val logoPath = ctx.getString(R.string.login_logo_public_path)
            val logoUrl = remember(backendRootUrl, logoPath) {
                backendRootUrl.trimEnd('/') + "/" + logoPath.trimStart('/')
            }
            RegisterScreen(
                viewModel = vm,
                logoUrl = logoUrl,
                onNavigateToLogin = {
                    navController.navigate(AppRoutes.LOGIN) {
                        popUpTo(AppRoutes.LOGIN) { inclusive = true }
                    }
                },
                onNavigateToMain = {
                    navController.navigate(AppRoutes.MAIN_GRAPH) {
                        popUpTo(AppRoutes.LOGIN) { inclusive = true }
                    }
                },
            )
        }
        navigation(
            route = AppRoutes.MAIN_GRAPH,
            startDestination = AppRoutes.HOME,
        ) {
            mainGraph(
                navController = navController,
                orderStatusNotifier = orderStatusNotifier,
            )
        }
    }
}

private fun NavGraphBuilder.mainGraph(
    navController: NavHostController,
    orderStatusNotifier: OrderStatusNotifier,
) {
    composable(AppRoutes.HOME) {
        val vm = hiltViewModel<HomeViewModel>()
        val name by vm.userName.observeAsState()
        val lifecycleOwner = LocalLifecycleOwner.current
        val showDot = remember { mutableStateOf(false) }
        DisposableEffect(lifecycleOwner, orderStatusNotifier) {
            val observer = LifecycleEventObserver { _, event ->
                if (event == Lifecycle.Event.ON_RESUME) {
                    showDot.value = orderStatusNotifier.hasUnreadNotifications()
                }
            }
            lifecycleOwner.lifecycle.addObserver(observer)
            onDispose { lifecycleOwner.lifecycle.removeObserver(observer) }
        }
        HomeScreen(
            userName = name ?: "User",
            onNotificationsClick = { navController.navigate(AppRoutes.NOTIFICATIONS) },
            notificationDot = showDot,
        )
    }
    composable(AppRoutes.CATALOG) {
        val vm = hiltViewModel<ProductListViewModel>()
        ProductListScreen(
            viewModel = vm,
            onOpenProduct = { product ->
                navController.navigate(AppRoutes.product(product.id))
            },
            onOpenCart = { navController.navigate(AppRoutes.CART) },
        )
    }
    composable(AppRoutes.SCHEDULE) {
        SchedulePlaceholderScreen()
    }
    composable(AppRoutes.CHAT) {
        AndroidFragment<MessagesFragment>(modifier = Modifier.fillMaxSize())
    }
    composable(
        route = AppRoutes.CONVERSATION,
        arguments = listOf(
            navArgument("conversationId") { type = NavType.IntType },
        ),
    ) { entry ->
        val conversationId = entry.arguments?.getInt("conversationId") ?: 0
        AndroidFragment<ConversationThreadFragment>(
            modifier = Modifier
                .fillMaxSize()
                .imePadding(),
            arguments = bundleOf("conversationId" to conversationId),
        )
    }
    composable(AppRoutes.PROFILE) {
        val vm = hiltViewModel<ProfileViewModel>()
        fun popToMainAndNavigate(route: String) {
            navController.navigate(route) {
                popUpTo(AppRoutes.MAIN_GRAPH) {
                    saveState = true
                }
                launchSingleTop = true
                restoreState = true
            }
        }
        ProfileScreen(
            viewModel = vm,
            onNavigateToEditProfile = { navController.navigate(AppRoutes.EDIT_PROFILE) },
            onNavigateToOrders = { navController.navigate(AppRoutes.ORDERS) },
            onNavigateToBills = { navController.navigate(AppRoutes.BILLS) },
            onNavigateToNotifications = { navController.navigate(AppRoutes.NOTIFICATIONS) },
            onNavigateToCatalog = { popToMainAndNavigate(AppRoutes.CATALOG) },
            onLoggedOut = {
                navController.navigate(AppRoutes.LOGIN) {
                    popUpTo(AppRoutes.MAIN_GRAPH) { inclusive = true }
                }
            },
        )
    }
    composable(AppRoutes.EDIT_PROFILE) {
        val vm = hiltViewModel<EditProfileViewModel>()
        EditProfileScreen(
            viewModel = vm,
            onBack = { navController.navigateUp() },
            onSaved = { navController.navigateUp() },
        )
    }
    composable(AppRoutes.NOTIFICATIONS) {
        val vm = hiltViewModel<OrderNotificationsViewModel>()
        LaunchedEffect(Unit) { vm.loadNotifications() }
        OrderNotificationsScreen(
            viewModel = vm,
            onBack = { navController.navigateUp() },
            onOpenOrder = { orderId ->
                navController.navigate(AppRoutes.orderDetail(orderId))
            },
        )
    }
    composable(
        route = AppRoutes.PRODUCT,
        arguments = listOf(
            navArgument("productId") {
                type = NavType.IntType
                defaultValue = -1
            },
        ),
    ) { entry ->
        val productId = entry.arguments?.getInt("productId") ?: -1
        val vm = hiltViewModel<ProductDetailViewModel>()
        LaunchedEffect(productId) {
            if (productId == -1) {
                navController.navigateUp()
            }
        }
        if (productId != -1) {
            ProductDetailScreen(
                productId = productId,
                viewModel = vm,
                onBack = { navController.navigateUp() },
                onOpenCart = { navController.navigate(AppRoutes.CART) },
            )
        }
    }
    composable(AppRoutes.CART) {
        val vm = hiltViewModel<CartViewModel>()
        CartScreen(
            viewModel = vm,
            onBack = { navController.navigateUp() },
            onCheckout = { navController.navigate(AppRoutes.CHECKOUT) },
            onAddMoreItems = {
                navController.navigate(AppRoutes.CATALOG) {
                    popUpTo(AppRoutes.MAIN_GRAPH) {
                        saveState = true
                    }
                    launchSingleTop = true
                    restoreState = true
                }
            },
        )
    }
    composable(AppRoutes.CHECKOUT) {
        val activity = LocalContext.current as FragmentActivity
        val checkoutVm: CheckoutViewModel = hiltViewModel(viewModelStoreOwner = activity)
        CheckoutScreen(
            viewModel = checkoutVm,
            onBack = { navController.navigateUp() },
            onReviewOrder = { navController.navigate(AppRoutes.ORDER_CONFIRM) },
        )
    }
    composable(AppRoutes.ORDER_CONFIRM) {
        val activity = LocalContext.current as FragmentActivity
        val checkoutVm: CheckoutViewModel = hiltViewModel(viewModelStoreOwner = activity)
        OrderConfirmScreen(
            viewModel = checkoutVm,
            onBack = { navController.navigateUp() },
            onEditOrder = {
                navController.popBackStack(AppRoutes.CART, false)
            },
            onOrderPlaced = { order ->
                navController.navigate(
                    AppRoutes.orderPlaced(
                        order.id,
                        order.orderNumber,
                        order.totalAmount,
                    ),
                )
                checkoutVm.clearOrderResult()
            },
        )
    }
    composable(
        route = AppRoutes.ORDER_PLACED,
        arguments = listOf(
            navArgument("orderId") { type = NavType.IntType },
            navArgument("orderNumber") { type = NavType.StringType },
            navArgument("orderTotal") { type = NavType.StringType },
        ),
    ) { entry ->
        val orderId = entry.arguments?.getInt("orderId") ?: -1
        val orderNumber = AppRoutes.decodeArg(
            entry.arguments?.getString("orderNumber").orEmpty(),
        )
        val orderTotal = AppRoutes.decodeArg(
            entry.arguments?.getString("orderTotal").orEmpty(),
        )
        OrderPlacedScreen(
            orderNumber = orderNumber,
            orderTotal = orderTotal,
            onViewOrder = {
                navController.navigate(AppRoutes.orderDetail(orderId))
            },
            onBackToCatalog = {
                navController.popBackStack(AppRoutes.CATALOG, false)
            },
        )
    }
    composable(AppRoutes.ORDERS) {
        val vm = hiltViewModel<OrdersViewModel>()
        OrdersScreen(
            viewModel = vm,
            onBack = { navController.navigateUp() },
            onOpenOrder = { order ->
                navController.navigate(AppRoutes.orderDetail(order.id))
            },
        )
    }
    composable(
        route = AppRoutes.ORDER,
        arguments = listOf(
            navArgument("orderId") {
                type = NavType.IntType
                defaultValue = -1
            },
        ),
    ) { entry ->
        val ctx = LocalContext.current
        val orderId = entry.arguments?.getInt("orderId") ?: -1
        val vm = hiltViewModel<OrderDetailViewModel>()
        LaunchedEffect(orderId) {
            when {
                orderId == -1 -> {
                    Toast.makeText(ctx, "Order not found", Toast.LENGTH_SHORT).show()
                    navController.navigateUp()
                }
                else -> vm.loadOrder(orderId)
            }
        }
        if (orderId != -1) {
            OrderDetailScreen(
                viewModel = vm,
                onBack = { navController.navigateUp() },
            )
        }
    }
    composable(AppRoutes.BILLS) {
        val vm = hiltViewModel<BillsViewModel>()
        BillsScreen(
            viewModel = vm,
            onBack = { navController.navigateUp() },
            onOpenBill = { bill ->
                navController.navigate(AppRoutes.billDetail(bill.id))
            },
        )
    }
    composable(
        route = AppRoutes.BILL,
        arguments = listOf(
            navArgument("billId") {
                type = NavType.IntType
                defaultValue = -1
            },
        ),
    ) { entry ->
        val ctx = LocalContext.current
        val billId = entry.arguments?.getInt("billId") ?: -1
        val vm = hiltViewModel<BillDetailViewModel>()
        LaunchedEffect(billId) {
            when {
                billId == -1 -> {
                    Toast.makeText(ctx, "Bill not found", Toast.LENGTH_SHORT).show()
                    navController.navigateUp()
                }
                else -> vm.loadBill(billId)
            }
        }
        if (billId != -1) {
            BillDetailScreen(
                viewModel = vm,
                onBack = { navController.navigateUp() },
                onOpenLinkedOrder = { orderId ->
                    navController.navigate(AppRoutes.orderDetail(orderId))
                },
            )
        }
    }
}
