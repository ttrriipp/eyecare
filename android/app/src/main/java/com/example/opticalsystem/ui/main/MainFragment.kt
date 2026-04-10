package com.example.opticalsystem.ui.main

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.os.bundleOf
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.updatePadding
import androidx.fragment.app.Fragment
import androidx.navigation.fragment.NavHostFragment
import androidx.navigation.ui.setupWithNavController
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentMainBinding
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class MainFragment : Fragment() {

    private var _binding: FragmentMainBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentMainBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val navHostFragment = childFragmentManager
            .findFragmentById(R.id.main_nav_host) as NavHostFragment
        val navController = navHostFragment.navController

        binding.bottomNav.setupWithNavController(navController)
        listenForOrderDetailRequest(navController)

        // Tapping the already-selected tab pops nested destinations (e.g. Catalog → detail → cart)
        // back to that tab's root, matching common bottom-nav behavior.
        binding.bottomNav.setOnItemReselectedListener { menuItem ->
            val rootDestinationId = when (menuItem.itemId) {
                R.id.nav_home -> R.id.nav_home
                R.id.nav_explore -> R.id.nav_explore
                R.id.nav_schedule -> R.id.nav_schedule
                R.id.nav_orders -> R.id.nav_orders
                R.id.nav_profile -> R.id.nav_profile
                else -> return@setOnItemReselectedListener
            }
            navController.popBackStack(rootDestinationId, inclusive = false)
        }

        ViewCompat.setOnApplyWindowInsetsListener(binding.bottomNav) { v, insets ->
            val navBars = insets.getInsets(WindowInsetsCompat.Type.navigationBars())
            v.updatePadding(bottom = navBars.bottom)
            insets
        }
    }

    private fun listenForOrderDetailRequest(navController: androidx.navigation.NavController) {
        parentFragmentManager.setFragmentResultListener(
            REQUEST_OPEN_ORDER_DETAIL,
            viewLifecycleOwner,
        ) { _, bundle ->
            val orderId = bundle.getInt(KEY_ORDER_ID, -1)
            if (orderId <= 0) return@setFragmentResultListener
            navController.navigate(
                R.id.orderDetailFragment,
                bundleOf("orderId" to orderId),
            )
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }

    private companion object {
        const val REQUEST_OPEN_ORDER_DETAIL = "request_open_order_detail"
        const val KEY_ORDER_ID = "order_id"
    }
}
