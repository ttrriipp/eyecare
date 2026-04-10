package com.example.opticalsystem.ui.checkout

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.navigation.fragment.findNavController
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentOrderPlacedBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class OrderPlacedFragment : Fragment() {
    private var _binding: FragmentOrderPlacedBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = FragmentOrderPlacedBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        val orderId = arguments?.getInt("orderId", -1) ?: -1
        val orderNumber = arguments?.getString("orderNumber").orEmpty()
        val orderTotal = arguments?.getString("orderTotal").orEmpty()

        binding.tvOrderNumber.text = getString(R.string.order_number_compact, orderNumber)
        binding.tvTimeline.text = buildString {
            append("• ")
            append(getString(R.string.timeline_order_submitted))
            append("\n")
            append(getString(R.string.timeline_order_submitted_sub))
            append("\n\n")
            append("• ")
            append(getString(R.string.timeline_staff_confirms))
            append("\n")
            append(getString(R.string.timeline_staff_confirms_sub))
            append("\n\n")
            append("• ")
            append(getString(R.string.timeline_appointment_pickup))
            append("\n")
            append(getString(R.string.timeline_appointment_pickup_sub))
            append("\n\n")
            append("• ")
            append(getString(R.string.timeline_payment_collected))
            append("\n")
            append(getString(R.string.timeline_payment_due_format, orderTotal))
        }

        binding.btnViewOrder.setOnClickListener {
            findNavController().navigate(R.id.action_orderPlaced_to_orderDetail, Bundle().apply { putInt("orderId", orderId) })
        }
        binding.btnBackToCatalog.setOnClickListener {
            findNavController().popBackStack(R.id.nav_explore, false)
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
