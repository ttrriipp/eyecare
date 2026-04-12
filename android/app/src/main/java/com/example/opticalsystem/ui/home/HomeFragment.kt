package com.example.opticalsystem.ui.home

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.getValue
import androidx.compose.runtime.livedata.observeAsState
import androidx.compose.runtime.mutableStateOf
import androidx.compose.ui.platform.ComposeView
import androidx.compose.ui.platform.ViewCompositionStrategy
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.example.opticalsystem.R
import com.example.opticalsystem.notifications.OrderStatusNotifier
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject

@AndroidEntryPoint
class HomeFragment : Fragment() {

    private val viewModel: HomeViewModel by viewModels()

    private val notificationUnreadState = mutableStateOf(false)

    @Inject
    lateinit var orderStatusNotifier: OrderStatusNotifier

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        return ComposeView(requireContext()).apply {
            setViewCompositionStrategy(ViewCompositionStrategy.DisposeOnViewTreeLifecycleDestroyed)
            setContent {
                MaterialTheme {
                    val name by viewModel.userName.observeAsState()
                    HomeScreen(
                        userName = name ?: "User",
                        onNotificationsClick = {
                            findNavController().navigate(R.id.action_nav_home_to_orderNotificationsFragment)
                        },
                        notificationDot = notificationUnreadState,
                    )
                }
            }
        }
    }

    override fun onResume() {
        super.onResume()
        notificationUnreadState.value = orderStatusNotifier.hasUnreadNotifications()
    }
}
