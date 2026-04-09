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
import com.example.opticalsystem.databinding.FragmentOrdersBinding
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class OrdersFragment : Fragment() {

    private var _binding: FragmentOrdersBinding? = null
    private val binding get() = _binding!!

    private val viewModel: OrdersViewModel by viewModels()
    private lateinit var orderAdapter: OrderAdapter

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentOrdersBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupRecyclerView()
        setupTabListeners()
        observeOrders()
        observeCounts()
    }

    private fun setupRecyclerView() {
        orderAdapter = OrderAdapter { order ->
            findNavController().navigate(
                R.id.action_orders_to_orderDetail,
                bundleOf("orderId" to order.id),
            )
        }
        binding.rvOrders.apply {
            layoutManager = LinearLayoutManager(requireContext())
            adapter = orderAdapter
        }
        binding.btnBack.setOnClickListener { findNavController().navigateUp() }
        binding.swipeRefresh.setOnRefreshListener { viewModel.refresh() }
    }

    private fun setupTabListeners() {
        binding.tabActive.setOnClickListener {
            viewModel.showActive()
            updateTabAppearance(activeSelected = true)
        }
        binding.tabPast.setOnClickListener {
            viewModel.showPast()
            updateTabAppearance(activeSelected = false)
        }
    }

    private fun updateTabAppearance(activeSelected: Boolean) {
        if (activeSelected) {
            binding.tabActive.setBackgroundResource(R.drawable.bg_tab_button_active)
            binding.tabActive.setTextColor(requireContext().getColor(R.color.text_primary))
            binding.tabActive.textSize = 14f
            binding.tabActive.paint.isFakeBoldText = true
            binding.tabPast.setBackgroundResource(android.R.color.transparent)
            binding.tabPast.setTextColor(requireContext().getColor(R.color.text_secondary))
            binding.tabPast.paint.isFakeBoldText = false
        } else {
            binding.tabPast.setBackgroundResource(R.drawable.bg_tab_button_active)
            binding.tabPast.setTextColor(requireContext().getColor(R.color.text_primary))
            binding.tabPast.paint.isFakeBoldText = true
            binding.tabActive.setBackgroundResource(android.R.color.transparent)
            binding.tabActive.setTextColor(requireContext().getColor(R.color.text_secondary))
            binding.tabActive.paint.isFakeBoldText = false
        }
    }

    private fun observeCounts() {
        viewModel.activeCount.observe(viewLifecycleOwner) { count ->
            val label = if (count > 0) "Active ($count)" else "Active"
            binding.tabActive.text = label
        }
        viewModel.pastCount.observe(viewLifecycleOwner) { count ->
            val label = if (count > 0) "Past ($count)" else "Past"
            binding.tabPast.text = label
        }
    }

    private fun observeOrders() {
        viewModel.orders.observe(viewLifecycleOwner) { result ->
            binding.swipeRefresh.isRefreshing = false
            when (result) {
                is Resource.Loading -> {
                    binding.progressBar.isVisible = true
                    binding.layoutEmpty.isVisible = false
                    binding.swipeRefresh.isVisible = false
                }
                is Resource.Success -> {
                    binding.progressBar.isVisible = false
                    val orders = result.data
                    val isEmpty = orders.isEmpty()
                    binding.layoutEmpty.isVisible = isEmpty
                    binding.swipeRefresh.isVisible = !isEmpty
                    orderAdapter.submitList(orders)
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
