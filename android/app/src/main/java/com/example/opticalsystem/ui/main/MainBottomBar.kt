package com.example.opticalsystem.ui.main

import androidx.annotation.DrawableRes
import androidx.annotation.StringRes
import androidx.compose.foundation.layout.Column
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationBarItemDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import androidx.navigation.NavDestination
import com.example.opticalsystem.R
import com.example.opticalsystem.navigation.AppRoutes

private data class BottomNavTab(
    val route: String,
    @StringRes val labelRes: Int,
    @DrawableRes val iconRes: Int,
)

private val bottomNavTabs = listOf(
    BottomNavTab(AppRoutes.HOME, R.string.nav_home, R.drawable.ic_nav_home_outlined),
    BottomNavTab(AppRoutes.CATALOG, R.string.nav_catalog, R.drawable.ic_nav_shop_outlined),
    BottomNavTab(AppRoutes.SCHEDULE, R.string.nav_book, R.drawable.ic_nav_calendar_outlined),
    BottomNavTab(AppRoutes.CHAT, R.string.nav_chat, R.drawable.ic_nav_chat_outlined),
    BottomNavTab(AppRoutes.PROFILE, R.string.nav_profile, R.drawable.ic_nav_person_outlined),
)

private val mainTabRouteSet = bottomNavTabs.map { it.route }.toSet()

fun isMainTabRoute(route: String?): Boolean = route != null && route in mainTabRouteSet

private fun destinationMatchesTab(destination: NavDestination?, tabRoute: String): Boolean {
    var current: NavDestination? = destination
    while (current != null) {
        if (current.route == tabRoute) return true
        current = current.parent
    }
    return false
}

@Composable
fun MainBottomBar(navController: NavController) {
    var selectedTabIndex by remember { mutableIntStateOf(0) }

    fun syncSelection(destination: NavDestination?) {
        if (destination == null) return
        bottomNavTabs.forEachIndexed { index, tab ->
            if (destinationMatchesTab(destination, tab.route)) {
                selectedTabIndex = index
                return
            }
        }
    }

    DisposableEffect(navController) {
        syncSelection(navController.currentDestination)
        val listener = NavController.OnDestinationChangedListener { _, destination, _ ->
            syncSelection(destination)
        }
        navController.addOnDestinationChangedListener(listener)
        onDispose { navController.removeOnDestinationChangedListener(listener) }
    }

    Column {
        HorizontalDivider(
            thickness = 1.dp,
            color = colorResource(R.color.bottom_nav_border),
        )
        NavigationBar(
            containerColor = colorResource(R.color.bottom_nav_bg),
            tonalElevation = 0.dp,
        ) {
            bottomNavTabs.forEachIndexed { index, tab ->
                val selected = selectedTabIndex == index
                NavigationBarItem(
                    selected = selected,
                    onClick = {
                        if (selected) {
                            navController.popBackStack(tab.route, inclusive = false)
                        } else {
                            navController.navigate(tab.route) {
                                popUpTo(AppRoutes.MAIN_GRAPH) {
                                    saveState = true
                                }
                                launchSingleTop = true
                                restoreState = true
                            }
                        }
                    },
                    icon = {
                        Icon(
                            painter = painterResource(tab.iconRes),
                            contentDescription = stringResource(tab.labelRes),
                        )
                    },
                    label = { Text(stringResource(tab.labelRes)) },
                    alwaysShowLabel = true,
                    colors = NavigationBarItemDefaults.colors(
                        selectedIconColor = colorResource(R.color.bottom_nav_active),
                        selectedTextColor = colorResource(R.color.bottom_nav_active),
                        unselectedIconColor = colorResource(R.color.bottom_nav_inactive),
                        unselectedTextColor = colorResource(R.color.bottom_nav_inactive),
                        indicatorColor = Color.Transparent,
                    ),
                )
            }
        }
    }
}
