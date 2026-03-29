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
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentBillsBinding
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class BillsFragment : Fragment() {

    private var _binding: FragmentBillsBinding? = null
    private val binding get() = _binding!!

    private val viewModel: BillsViewModel by viewModels()
    private lateinit var billAdapter: BillAdapter

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentBillsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupRecyclerView()
        setupChipFilters()
        setupListeners()
        observeBills()
    }

    private fun setupRecyclerView() {
        billAdapter = BillAdapter { bill ->
            findNavController().navigate(
                R.id.action_bills_to_billDetail,
                bundleOf("billId" to bill.id),
            )
        }
        binding.rvBills.apply {
            layoutManager = LinearLayoutManager(requireContext())
            adapter = billAdapter
        }
    }

    private fun setupChipFilters() {
        binding.chipGroupStatus.setOnCheckedStateChangeListener { _, checkedIds ->
            val status = when {
                checkedIds.contains(R.id.chipUnpaid) -> "unpaid"
                checkedIds.contains(R.id.chipPaid) -> "paid"
                checkedIds.contains(R.id.chipRefunded) -> "refunded"
                checkedIds.contains(R.id.chipVoided) -> "voided"
                else -> null
            }
            viewModel.filterByStatus(status)
        }
    }

    private fun setupListeners() {
        binding.btnBack.setOnClickListener { findNavController().navigateUp() }
        binding.swipeRefresh.setOnRefreshListener { viewModel.refresh() }
    }

    private fun observeBills() {
        viewModel.bills.observe(viewLifecycleOwner) { result ->
            binding.swipeRefresh.isRefreshing = false
            when (result) {
                is Resource.Loading -> {
                    binding.progressBar.isVisible = true
                    binding.layoutEmpty.isVisible = false
                    binding.swipeRefresh.isVisible = false
                }
                is Resource.Success -> {
                    binding.progressBar.isVisible = false
                    val bills = result.data
                    val isEmpty = bills.isEmpty()
                    binding.layoutEmpty.isVisible = isEmpty
                    binding.swipeRefresh.isVisible = !isEmpty
                    billAdapter.submitList(bills)
                }
                is Resource.Error -> {
                    binding.progressBar.isVisible = false
                    binding.layoutEmpty.isVisible = true
                    binding.swipeRefresh.isVisible = false
                    Toast.makeText(requireContext(), result.message, Toast.LENGTH_SHORT).show()
                }
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
