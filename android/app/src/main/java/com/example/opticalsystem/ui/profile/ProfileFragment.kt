package com.example.opticalsystem.ui.profile

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.compose.material3.MaterialTheme
import androidx.compose.ui.platform.ComposeView
import androidx.compose.ui.platform.ViewCompositionStrategy
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.NavOptions
import androidx.navigation.fragment.findNavController
import com.example.opticalsystem.R
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class ProfileFragment : Fragment() {

    private val viewModel: ProfileViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        return ComposeView(requireContext()).apply {
            setViewCompositionStrategy(ViewCompositionStrategy.DisposeOnViewTreeLifecycleDestroyed)
            setContent {
                MaterialTheme {
                    ProfileScreen(
                        viewModel = viewModel,
                        onNavigateToOrders = {
                            findNavController().navigate(R.id.action_profile_to_orders)
                        },
                        onNavigateToBills = {
                            findNavController().navigate(R.id.action_profile_to_bills)
                        },
                        onLoggedOut = {
                            val parentNavController = requireParentFragment()
                                .requireParentFragment()
                                .findNavController()
                            parentNavController.navigate(
                                R.id.loginFragment,
                                null,
                                NavOptions.Builder()
                                    .setPopUpTo(R.id.nav_graph, true)
                                    .build(),
                            )
                        },
                    )
                }
            }
        }
    }
}
