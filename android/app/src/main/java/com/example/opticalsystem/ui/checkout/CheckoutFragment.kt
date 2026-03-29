package com.example.opticalsystem.ui.checkout

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.core.os.bundleOf
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentCheckoutBinding
import com.example.opticalsystem.ui.cart.CartAdapter
import com.example.opticalsystem.util.Resource
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch

@AndroidEntryPoint
class CheckoutFragment : Fragment() {

    private var _binding: FragmentCheckoutBinding? = null
    private val binding get() = _binding!!

    private val viewModel: CheckoutViewModel by viewModels()

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

        setupRecyclerView()
        setupListeners()
        observeData()
    }

    private fun setupRecyclerView() {
        val adapter = CartAdapter(
            onIncrease = {},
            onDecrease = {},
            onRemove = {},
        )
        binding.rvCheckoutItems.apply {
            layoutManager = LinearLayoutManager(requireContext())
            this.adapter = adapter
        }

        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.cartItems.collectLatest { items ->
                adapter.submitList(items)
            }
        }
    }

    private fun setupListeners() {
        binding.btnBack.setOnClickListener { findNavController().navigateUp() }

        binding.btnPlaceOrder.setOnClickListener {
            val notes = binding.etNotes.text?.toString()
            viewModel.placeOrder(notes)
        }
    }

    private fun observeData() {
        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.totalPrice.collectLatest { total ->
                binding.tvCheckoutTotal.text = "₱${String.format("%,.0f", total)}"
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.itemCount.collectLatest { count ->
                binding.tvCheckoutItemCount.text = if (count > 0) {
                    getString(R.string.checkout_items_format, count)
                } else ""
            }
        }

        viewModel.orderResult.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Loading -> {
                    binding.loadingOverlay.isVisible = true
                    binding.btnPlaceOrder.isEnabled = false
                    binding.btnPlaceOrder.text = getString(R.string.checkout_placing_order)
                }
                is Resource.Success -> {
                    binding.loadingOverlay.isVisible = false
                    val order = result.data
                    MaterialAlertDialogBuilder(requireContext())
                        .setTitle(R.string.checkout_success_title)
                        .setMessage(getString(R.string.checkout_success_message, order.orderNumber))
                        .setPositiveButton("View Order") { _, _ ->
                            findNavController().navigate(
                                R.id.action_checkout_to_orderDetail,
                                bundleOf("orderId" to order.id),
                            )
                        }
                        .setNegativeButton("Back to Shop") { _, _ ->
                            findNavController().popBackStack(R.id.nav_explore, false)
                        }
                        .setCancelable(false)
                        .show()
                }
                is Resource.Error -> {
                    binding.loadingOverlay.isVisible = false
                    binding.btnPlaceOrder.isEnabled = true
                    binding.btnPlaceOrder.text = getString(R.string.checkout_place_order)
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
