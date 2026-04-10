package com.example.opticalsystem.ui.checkout

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.core.os.bundleOf
import androidx.fragment.app.Fragment
import androidx.fragment.app.activityViewModels
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Appointment
import com.example.opticalsystem.util.Resource
import com.example.opticalsystem.databinding.FragmentOrderConfirmBinding
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch
import android.widget.LinearLayout
import android.widget.TextView
import androidx.core.content.ContextCompat

@AndroidEntryPoint
class OrderConfirmFragment : Fragment() {
    private var _binding: FragmentOrderConfirmBinding? = null
    private val binding get() = _binding!!
    private val viewModel: CheckoutViewModel by activityViewModels()
    private var appointmentsById: Map<Int, Appointment> = emptyMap()

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentOrderConfirmBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        binding.btnBack.setOnClickListener { findNavController().navigateUp() }
        binding.btnEditOrder.setOnClickListener { findNavController().popBackStack(R.id.cartFragment, false) }
        binding.btnPlaceOrder.setOnClickListener {
            viewModel.placeOrder(viewModel.orderNotes.value)
        }

        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.cartItems.collectLatest { items ->
                binding.layoutItemsRows.removeAllViews()
                items.forEachIndexed { index, item ->
                    val unit = item.productPrice.toDoubleOrNull() ?: 0.0
                    val label = item.productName + if (item.variantLabel.isNullOrBlank()) "" else " (${item.variantLabel})"
                    val amount = "₱${String.format("%,.0f", unit * item.quantity)}"
                    binding.layoutItemsRows.addView(buildItemRow(label, amount, bold = false))
                    if (index < items.lastIndex) {
                        addRowGap(binding.layoutItemsRows, 8)
                    }
                }
            }
        }
        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.totalPrice.collectLatest { total ->
                binding.tvItemsTotalAmount.text = "₱${String.format("%,.0f", total)}"
            }
        }
        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.orderNotes.collectLatest { notes ->
                binding.tvNotesSummary.text = if (notes.isBlank()) getString(R.string.none_label) else notes
            }
        }
        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.selectedAppointmentId.collectLatest { id ->
                binding.tvAppointmentSummary.text = buildAppointmentSummary(id)
            }
        }
        viewModel.upcomingAppointments.observe(viewLifecycleOwner) { result ->
            if (result is Resource.Success) {
                appointmentsById = result.data.associateBy { it.id }
                binding.tvAppointmentSummary.text = buildAppointmentSummary(viewModel.selectedAppointmentId.value)
            }
        }

        viewModel.orderResult.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Success -> {
                    val order = result.data
                    findNavController().navigate(
                        R.id.action_orderConfirm_to_orderPlaced,
                        bundleOf("orderId" to order.id, "orderNumber" to order.orderNumber, "orderTotal" to order.totalAmount),
                    )
                }
                is Resource.Error -> Toast.makeText(requireContext(), result.message, Toast.LENGTH_LONG).show()
                else -> Unit
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }

    private fun buildAppointmentSummary(id: Int?): String {
        if (id == null) return getString(R.string.none_linked)
        val selected = appointmentsById[id] ?: return "Appointment #$id"
        val type = selected.appointmentType ?: getString(R.string.appointment_label)
        val schedule = selected.scheduledAt ?: getString(R.string.schedule_tbd)
        val doctor = selected.doctorName ?: getString(R.string.doctor_tbd)
        return "$type\n$schedule\n$doctor"
    }

    private fun buildItemRow(leftText: String, rightText: String, bold: Boolean): LinearLayout {
        val row = LinearLayout(requireContext()).apply {
            orientation = LinearLayout.HORIZONTAL
            gravity = android.view.Gravity.CENTER_VERTICAL
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT,
            )
        }

        val left = TextView(requireContext()).apply {
            text = leftText
            setTextColor(ContextCompat.getColor(context, R.color.text_primary))
            textSize = 15f
            if (bold) setTypeface(typeface, android.graphics.Typeface.BOLD)
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f)
        }

        val right = TextView(requireContext()).apply {
            text = rightText
            setTextColor(ContextCompat.getColor(context, R.color.text_primary))
            textSize = 15f
            setTypeface(typeface, android.graphics.Typeface.BOLD)
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT,
                LinearLayout.LayoutParams.WRAP_CONTENT,
            )
        }

        row.addView(left)
        row.addView(right)
        return row
    }

    private fun addRowGap(container: LinearLayout, dp: Int) {
        container.addView(TextView(requireContext()).apply {
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                (dp * resources.displayMetrics.density).toInt(),
            )
        })
    }
}
