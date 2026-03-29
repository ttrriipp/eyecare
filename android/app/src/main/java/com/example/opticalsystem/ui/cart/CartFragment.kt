package com.example.opticalsystem.ui.cart

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.opticalsystem.R
import com.example.opticalsystem.data.local.CartItem
import com.example.opticalsystem.databinding.FragmentCartBinding
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch

@AndroidEntryPoint
class CartFragment : Fragment() {

    private var _binding: FragmentCartBinding? = null
    private val binding get() = _binding!!

    private val viewModel: CartViewModel by viewModels()
    private lateinit var cartAdapter: CartAdapter

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentCartBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupRecyclerView()
        setupListeners()
        observeCart()
    }

    private fun setupRecyclerView() {
        cartAdapter = CartAdapter(
            onIncrease = { item -> viewModel.increase(item) },
            onDecrease = { item ->
                if (item.quantity > 1) {
                    viewModel.decrease(item)
                } else {
                    showRemoveItemConfirmation(item)
                }
            },
            onRemove = { item -> showRemoveItemConfirmation(item) },
        )
        binding.rvCartItems.apply {
            layoutManager = LinearLayoutManager(requireContext())
            adapter = cartAdapter
        }
    }

    private fun setupListeners() {
        binding.btnBack.setOnClickListener { findNavController().navigateUp() }

        binding.btnCheckout.setOnClickListener {
            findNavController().navigate(R.id.action_cart_to_checkout)
        }

        binding.tvClearCart.setOnClickListener {
            MaterialAlertDialogBuilder(requireContext())
                .setTitle(R.string.cart_clear)
                .setMessage(R.string.cart_clear_message)
                .setPositiveButton(R.string.action_clear) { _, _ ->
                    viewModel.clearCart()
                    Toast.makeText(requireContext(), getString(R.string.cart_cleared), Toast.LENGTH_SHORT).show()
                }
                .setNegativeButton(R.string.action_cancel, null)
                .show()
        }
    }

    private fun showRemoveItemConfirmation(item: CartItem) {
        MaterialAlertDialogBuilder(requireContext())
            .setTitle(R.string.cart_remove_item_title)
            .setMessage(getString(R.string.cart_remove_item_message, item.productName))
            .setPositiveButton(R.string.action_remove) { _, _ ->
                viewModel.remove(item)
                Toast.makeText(
                    requireContext(),
                    getString(R.string.cart_removed_format, item.productName),
                    Toast.LENGTH_SHORT,
                ).show()
            }
            .setNegativeButton(R.string.action_cancel, null)
            .show()
    }

    private fun observeCart() {
        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.cartItems.collectLatest { items ->
                cartAdapter.submitList(items)

                val isEmpty = items.isEmpty()
                binding.layoutEmpty.isVisible = isEmpty
                binding.rvCartItems.isVisible = !isEmpty
                binding.cardSummary.isVisible = !isEmpty
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.itemCount.collectLatest { count ->
                binding.tvCartHeaderCount.text = if (count > 0) {
                    getString(R.string.cart_item_count_format, count)
                } else {
                    ""
                }
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            viewModel.totalPrice.collectLatest { total ->
                val formatted = "₱${String.format("%,.0f", total)}"
                binding.tvSubtotal.text = formatted
                binding.tvTotal.text = formatted
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
