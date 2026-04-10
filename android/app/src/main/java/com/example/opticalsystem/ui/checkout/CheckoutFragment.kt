package com.example.opticalsystem.ui.checkout

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.activityViewModels
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentCheckoutBinding
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch

@AndroidEntryPoint
class CheckoutFragment : Fragment() {

    private var _binding: FragmentCheckoutBinding? = null
    private val binding get() = _binding!!

    private val viewModel: CheckoutViewModel by activityViewModels()
    private lateinit var appointmentAdapter: AppointmentOptionAdapter

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentCheckoutBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupAppointmentList()
        setupListeners()
        observeData()
        viewModel.loadOrderDetailsData()
    }

    private fun setupAppointmentList() {
        appointmentAdapter = AppointmentOptionAdapter { appointment ->
            val current = viewModel.selectedAppointmentId.value
            viewModel.selectAppointment(if (current == appointment.id) null else appointment.id)
        }
        binding.rvAppointments.apply {
            layoutManager = LinearLayoutManager(requireContext())
            adapter = appointmentAdapter
        }
    }

    private fun setupListeners() {
        binding.btnBack.setOnClickListener { findNavController().navigateUp() }
        binding.btnReviewOrder.setOnClickListener {
            viewModel.setOrderNotes(binding.etNotes.text?.toString().orEmpty())
            findNavController().navigate(R.id.action_checkout_to_orderConfirm)
        }
    }

    private fun observeData() {
        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.selectedAppointmentId.collectLatest { selectedId ->
                appointmentAdapter.selectedId = selectedId
                binding.tvNoAppointmentNote.isVisible = selectedId == null
            }
        }

        viewModel.upcomingAppointments.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Success -> appointmentAdapter.submitList(result.data)
                else -> {
                }
            }
        }

        viewModel.profile.observe(viewLifecycleOwner) { result ->
            if (result is Resource.Success) {
                binding.tvCustomerName.text = result.data.name
                binding.tvCustomerPhone.text = result.data.phone ?: getString(R.string.not_provided)
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
