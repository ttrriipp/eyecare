package com.example.opticalsystem.ui.notifications

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentOrderNotificationsBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class OrderNotificationsFragment : Fragment() {

    private var _binding: FragmentOrderNotificationsBinding? = null
    private val binding get() = _binding!!

    private val viewModel: OrderNotificationsViewModel by viewModels()
    private lateinit var adapter: OrderNotificationsAdapter

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentOrderNotificationsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        adapter = OrderNotificationsAdapter().apply {
            onItemClick = { item ->
                findNavController().navigate(
                    R.id.action_orderNotifications_to_orderDetail,
                    Bundle().apply { putInt("orderId", item.orderId) },
                )
            }
        }
        binding.rvNotifications.layoutManager = LinearLayoutManager(requireContext())
        binding.rvNotifications.adapter = adapter

        binding.btnBack.setOnClickListener { findNavController().navigateUp() }

        viewModel.notifications.observe(viewLifecycleOwner) { list ->
            adapter.submitList(list)
            binding.layoutEmpty.isVisible = list.isEmpty()
            binding.rvNotifications.isVisible = list.isNotEmpty()
        }
    }

    override fun onResume() {
        super.onResume()
        viewModel.loadNotifications()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
