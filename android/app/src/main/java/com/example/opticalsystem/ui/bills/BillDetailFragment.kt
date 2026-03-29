package com.example.opticalsystem.ui.bills

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
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Bill
import com.example.opticalsystem.databinding.FragmentBillDetailBinding
import com.example.opticalsystem.util.Resource
import com.example.opticalsystem.util.StatusHelper
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class BillDetailFragment : Fragment() {

    private var _binding: FragmentBillDetailBinding? = null
    private val binding get() = _binding!!

    private val viewModel: BillDetailViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentBillDetailBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.btnBack.setOnClickListener { findNavController().navigateUp() }

        val billId = arguments?.getInt("billId", -1) ?: -1
        if (billId == -1) {
            Toast.makeText(requireContext(), "Bill not found", Toast.LENGTH_SHORT).show()
            findNavController().navigateUp()
            return
        }

        viewModel.loadBill(billId)
        observeBill()
    }

    private fun observeBill() {
        viewModel.bill.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Loading -> {
                    binding.progressBar.isVisible = true
                    binding.scrollContent.isVisible = false
                }
                is Resource.Success -> {
                    binding.progressBar.isVisible = false
                    binding.scrollContent.isVisible = true
                    displayBill(result.data)
                }
                is Resource.Error -> {
                    binding.progressBar.isVisible = false
                    Toast.makeText(requireContext(), result.message, Toast.LENGTH_SHORT).show()
                }
            }
        }
    }

    private fun displayBill(bill: Bill) {
        binding.tvInvoiceNumber.text = bill.invoiceNumber
        binding.tvAmount.text = StatusHelper.formatPrice(bill.amount)
        binding.tvDate.text = StatusHelper.formatDate(bill.createdAt)

        StatusHelper.applyPaymentStatusBadge(binding.tvPaymentStatus, bill.paymentStatus, bill.paymentStatusLabel)

        // Payment method
        if (!bill.paymentMethod.isNullOrBlank()) {
            binding.rowPaymentMethod.isVisible = true
            binding.tvPaymentMethod.text = bill.paymentMethod
        } else {
            binding.rowPaymentMethod.isVisible = false
        }

        // Paid at
        if (!bill.paidAt.isNullOrBlank()) {
            binding.rowPaidAt.isVisible = true
            binding.tvPaidAt.text = StatusHelper.formatDate(bill.paidAt)
        } else {
            binding.rowPaidAt.isVisible = false
        }

        // Linked order
        if (bill.orderId != null) {
            binding.cardLinkedOrder.isVisible = true
            val orderNumber = bill.order?.orderNumber ?: bill.orderId.toString()
            binding.tvLinkedOrderNumber.text = getString(R.string.order_number_format, orderNumber)
            binding.cardLinkedOrder.setOnClickListener {
                findNavController().navigate(
                    R.id.action_billDetail_to_orderDetail,
                    bundleOf("orderId" to bill.orderId),
                )
            }
        } else {
            binding.cardLinkedOrder.isVisible = false
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
