package com.example.opticalsystem.ui.bills

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
class BillsFragment : Fragment() {

    private val viewModel: BillsViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        return ComposeView(requireContext()).apply {
            setViewCompositionStrategy(ViewCompositionStrategy.DisposeOnViewTreeLifecycleDestroyed)
            setContent {
                MaterialTheme {
                    BillsScreen(
                        viewModel = viewModel,
                        onBack = { findNavController().navigateUp() },
                        onOpenBill = { bill ->
                            findNavController().navigate(
                                R.id.action_bills_to_billDetail,
                                bundleOf("billId" to bill.id),
                            )
                        },
                    )
                }
            }
        }
    }
}
