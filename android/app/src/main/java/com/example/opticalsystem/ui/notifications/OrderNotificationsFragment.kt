package com.example.opticalsystem.ui.notifications

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.compose.material3.MaterialTheme
import androidx.compose.ui.platform.ComposeView
import androidx.compose.ui.platform.ViewCompositionStrategy
import androidx.core.os.bundleOf
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.example.opticalsystem.R
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class OrderNotificationsFragment : Fragment() {

    private val viewModel: OrderNotificationsViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        return ComposeView(requireContext()).apply {
            setViewCompositionStrategy(ViewCompositionStrategy.DisposeOnViewTreeLifecycleDestroyed)
            setContent {
                MaterialTheme {
                    OrderNotificationsScreen(
                        viewModel = viewModel,
                        onBack = { findNavController().navigateUp() },
                        onOpenOrder = { orderId ->
                            findNavController().navigate(
                                R.id.action_orderNotifications_to_orderDetail,
                                bundleOf("orderId" to orderId),
                            )
                        },
                    )
                }
            }
        }
    }

    override fun onResume() {
        super.onResume()
        viewModel.loadNotifications()
    }
}
