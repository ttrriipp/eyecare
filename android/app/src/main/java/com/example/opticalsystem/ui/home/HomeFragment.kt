package com.example.opticalsystem.ui.home

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentHomeBinding
import com.example.opticalsystem.notifications.OrderStatusNotifier
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject

@AndroidEntryPoint
class HomeFragment : Fragment() {

    private var _binding: FragmentHomeBinding? = null
    private val binding get() = _binding!!

    private val viewModel: HomeViewModel by viewModels()

    @Inject
    lateinit var orderStatusNotifier: OrderStatusNotifier

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentHomeBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        viewModel.userName.observe(viewLifecycleOwner) { name ->
            binding.tvUserName.text = name ?: "User"
        }

        binding.btnNotifications.setOnClickListener {
            findNavController().navigate(R.id.action_nav_home_to_orderNotificationsFragment)
        }
    }

    override fun onResume() {
        super.onResume()
        binding.viewNotificationDot.isVisible = orderStatusNotifier.hasUnreadNotifications()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
