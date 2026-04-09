package com.example.opticalsystem.ui.orders

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.core.content.ContextCompat
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
        binding.tvHeaderTitle.text = order.orderNumber
        binding.tvHeaderDate.text = StatusHelper.formatDateShort(order.createdAt)
        binding.tvOrderTotal.text = StatusHelper.formatPrice(order.totalAmount)
        binding.tvSummarySubtotal.text = StatusHelper.formatPrice(order.totalAmount)

        setupStatusBanner(order)
        updateProgressStepper(order.status)

        itemAdapter.submitList(order.items ?: emptyList())

        if (!order.notes.isNullOrBlank()) {
            binding.cardNotes.isVisible = true
            binding.tvNotes.text = order.notes
        } else {
            binding.cardNotes.isVisible = false
        }

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
    }

    private fun setupStatusBanner(order: Order) {
        val ctx = requireContext()
        val (bgColor, iconTint, description) = when (order.status) {
            "pending" -> Triple(
                ContextCompat.getColor(ctx, R.color.status_pending_bg),
                ContextCompat.getColor(ctx, R.color.status_pending),
                "Your order has been placed and is waiting to be confirmed.",
            )
            "confirmed" -> Triple(
                ContextCompat.getColor(ctx, R.color.status_confirmed_bg),
                ContextCompat.getColor(ctx, R.color.status_confirmed),
                "Your order has been confirmed and is being prepared.",
            )
            "ready" -> Triple(
                ContextCompat.getColor(ctx, R.color.status_ready_bg),
                ContextCompat.getColor(ctx, R.color.status_ready),
                "Your order is ready for pickup at our store.",
            )
            "completed" -> Triple(
                ContextCompat.getColor(ctx, R.color.status_completed_bg),
                ContextCompat.getColor(ctx, R.color.status_completed),
                "Your order has been picked up. Thank you!",
            )
            "cancelled" -> Triple(
                ContextCompat.getColor(ctx, R.color.status_cancelled_bg),
                ContextCompat.getColor(ctx, R.color.status_cancelled),
                "This order has been cancelled.",
            )
            else -> Triple(
                ContextCompat.getColor(ctx, R.color.divider),
                ContextCompat.getColor(ctx, R.color.text_secondary),
                "",
            )
        }

        binding.cardStatusBanner.setCardBackgroundColor(bgColor)
        binding.cardStatusBanner.cardElevation = 0f

        val iconBg = binding.ivStatusIcon.parent as android.widget.FrameLayout
        iconBg.background = android.graphics.drawable.GradientDrawable().apply {
            shape = android.graphics.drawable.GradientDrawable.OVAL
            setColor(iconTint)
        }
        binding.ivStatusIcon.setColorFilter(android.graphics.Color.WHITE)

        binding.tvStatusTitle.text = order.statusLabel
        binding.tvStatusDescription.text = description
    }

    private fun updateProgressStepper(status: String) {
        val ctx = requireContext()
        val activeColor = ContextCompat.getColor(ctx, R.color.primary)
        val inactiveColor = ContextCompat.getColor(ctx, R.color.divider)
        val activeTextColor = ContextCompat.getColor(ctx, android.R.color.white)
        val inactiveTextColor = ContextCompat.getColor(ctx, R.color.text_secondary)

        val stepsDone = when (status) {
            "pending" -> 1
            "confirmed" -> 2
            "ready" -> 3
            "completed" -> 4
            else -> 0
        }

        val circles = listOf(binding.stepCircle1, binding.stepCircle2, binding.stepCircle3, binding.stepCircle4)
        val icons = listOf(binding.stepIcon1, binding.stepIcon2, binding.stepIcon3, binding.stepIcon4)
        val nums = listOf(binding.stepNum1, binding.stepNum2, binding.stepNum3, binding.stepNum4)
        val lines = listOf(binding.stepLine12, binding.stepLine23, binding.stepLine34)

        circles.forEachIndexed { index, circle ->
            val done = index < stepsDone
            circle.background = android.graphics.drawable.GradientDrawable().apply {
                shape = android.graphics.drawable.GradientDrawable.OVAL
                setColor(if (done) activeColor else inactiveColor)
            }
            icons[index].isVisible = done
            nums[index].isVisible = !done
            nums[index].setTextColor(inactiveTextColor)
        }

        lines.forEachIndexed { index, line ->
            val done = index + 1 < stepsDone
            line.setBackgroundColor(if (done) activeColor else inactiveColor)
        }
    }

    private fun observeCancelResult() {
        viewModel.cancelResult.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Loading -> binding.btnCancelOrder.isEnabled = false
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
