package com.example.opticalsystem.ui.cart

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.compose.material3.MaterialTheme
import androidx.compose.ui.platform.ComposeView
import androidx.compose.ui.platform.ViewCompositionStrategy
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.example.opticalsystem.R
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class CartFragment : Fragment() {

    private val viewModel: CartViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        return ComposeView(requireContext()).apply {
            setViewCompositionStrategy(
                ViewCompositionStrategy.DisposeOnViewTreeLifecycleDestroyed,
            )
            setContent {
                MaterialTheme {
                    CartScreen(
                        viewModel = viewModel,
                        onBack = { findNavController().navigateUp() },
                        onCheckout = {
                            findNavController().navigate(R.id.action_cart_to_checkout)
                        },
                        onAddMoreItems = {
                            findNavController().navigate(R.id.action_cart_to_catalog)
                        },
                    )
                }
            }
        }
    }
}
