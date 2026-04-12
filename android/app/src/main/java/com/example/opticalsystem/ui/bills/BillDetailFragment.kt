package com.example.opticalsystem.ui.bills

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
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
class BillDetailFragment : Fragment() {

    private val viewModel: BillDetailViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        return ComposeView(requireContext()).apply {
            setViewCompositionStrategy(ViewCompositionStrategy.DisposeOnViewTreeLifecycleDestroyed)
            setContent {
                MaterialTheme {
                    BillDetailScreen(
                        viewModel = viewModel,
                        onBack = { findNavController().navigateUp() },
                        onOpenLinkedOrder = { orderId ->
                            findNavController().navigate(
                                R.id.action_billDetail_to_orderDetail,
                                bundleOf("orderId" to orderId),
                            )
                        },
                    )
                }
            }
        }
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        val billId = arguments?.getInt("billId", -1) ?: -1
        if (billId == -1) {
            Toast.makeText(requireContext(), "Bill not found", Toast.LENGTH_SHORT).show()
            findNavController().navigateUp()
            return
        }
        viewModel.loadBill(billId)
    }
}
