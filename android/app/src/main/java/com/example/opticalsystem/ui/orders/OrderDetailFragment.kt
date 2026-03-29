package com.example.opticalsystem.ui.orders

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.core.os.bundleOf
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Order
import com.example.opticalsystem.databinding.FragmentOrderDetailBinding
import com.example.opticalsystem.util.Resource
import com.example.opticalsystem.util.StatusHelper
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class OrderDetailFragment : Fragment() {

    private var _binding: FragmentOrderDetailBinding? = null
    private val binding get() = _binding!!

    private val viewModel: OrderDetailViewModel by viewModels()
    private lateinit var itemAdapter: OrderItemAdapter

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentOrderDetailBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        itemAdapter = OrderItemAdapter()
        binding.rvOrderItems.apply {
            layoutManager = LinearLayoutManager(requireContext())
            adapter = itemAdapter
            isNestedScrollingEnabled = false
        }

        binding.btnBack.setOnClickListener { findNavController().navigateUp() }

        val orderId = arguments?.getInt("orderId", -1) ?: -1
        if (orderId == -1) {
            Toast.makeText(requireContext(), "Order not found", Toast.LENGTH_SHORT).show()
            findNavController().navigateUp()
            return
        }

        viewModel.loadOrder(orderId)
        observeOrder()
        observeCancelResult()
    }

    private fun observeOrder() {
        viewModel.order.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Loading -> {
                    binding.progressBar.isVisible = true
                    binding.scrollContent.isVisible = false
                }
                is Resource.Success -> {
                    binding.progressBar.isVisible = false
                    binding.scrollContent.isVisible = true
                    displayOrder(result.data)
                }
                is Resource.Error -> {
                    binding.progressBar.isVisible = false
                    Toast.makeText(requireContext(), result.message, Toast.LENGTH_SHORT).show()
                }
            }
        }
    }

    private fun displayOrder(order: Order) {
        binding.tvHeaderTitle.text = getString(R.string.order_number_format, order.orderNumber)
        binding.tvOrderNumber.text = getString(R.string.order_number_format, order.orderNumber)
        binding.tvOrderDate.text = StatusHelper.formatDate(order.createdAt)
        binding.tvOrderTotal.text = StatusHelper.formatPrice(order.totalAmount)

        StatusHelper.applyOrderStatusBadge(binding.tvOrderStatus, order.status, order.statusLabel)

        // Items
        itemAdapter.submitList(order.items ?: emptyList())

        // Notes
        if (!order.notes.isNullOrBlank()) {
            binding.cardNotes.isVisible = true
            binding.tvNotes.text = order.notes
        } else {
            binding.cardNotes.isVisible = false
        }

        // Cancel button (only for pending/confirmed)
        val canCancel = order.status == "pending" || order.status == "confirmed"
        binding.btnCancelOrder.isVisible = canCancel
        binding.btnCancelOrder.setOnClickListener {
            MaterialAlertDialogBuilder(requireContext())
                .setTitle(R.string.order_cancel_title)
                .setMessage(getString(R.string.order_cancel_message, order.orderNumber))
                .setPositiveButton(R.string.order_cancel_button) { _, _ ->
                    viewModel.cancelOrder()
                }
                .setNegativeButton(R.string.action_cancel, null)
                .show()
        }

        // View Bill button
        binding.btnViewBill.setOnClickListener {
            findNavController().navigate(
                R.id.action_orderDetail_to_billDetail,
                bundleOf("billId" to order.id),
            )
        }
    }

    private fun observeCancelResult() {
        viewModel.cancelResult.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Loading -> {
                    binding.btnCancelOrder.isEnabled = false
                }
                is Resource.Success -> {
                    binding.btnCancelOrder.isEnabled = true
                    Toast.makeText(requireContext(), getString(R.string.order_cancelled_success), Toast.LENGTH_SHORT).show()
                }
                is Resource.Error -> {
                    binding.btnCancelOrder.isEnabled = true
                    Toast.makeText(requireContext(), result.message, Toast.LENGTH_LONG).show()
                }
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
