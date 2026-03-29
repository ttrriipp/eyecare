package com.example.opticalsystem.ui.profile

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.NavOptions
import androidx.navigation.fragment.findNavController
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentProfileBinding
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class ProfileFragment : Fragment() {

    private var _binding: FragmentProfileBinding? = null
    private val binding get() = _binding!!

    private val viewModel: ProfileViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentProfileBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupMenuButtons()
        setupLogout()
        observeProfile()
        viewModel.loadProfile()
    }

    private fun setupMenuButtons() {
        binding.btnMyOrders.setOnClickListener {
            findNavController().navigate(R.id.action_profile_to_orders)
        }

        binding.btnMyBills.setOnClickListener {
            findNavController().navigate(R.id.action_profile_to_bills)
        }
    }

    private fun setupLogout() {
        binding.btnLogout.setOnClickListener {
            viewModel.logout()
        }

        viewModel.logoutResult.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Loading -> binding.btnLogout.isEnabled = false
                is Resource.Success -> {
                    val parentNavController = requireParentFragment()
                        .requireParentFragment()
                        .findNavController()
                    parentNavController.navigate(
                        R.id.loginFragment,
                        null,
                        NavOptions.Builder()
                            .setPopUpTo(R.id.nav_graph, true)
                            .build()
                    )
                }
                is Resource.Error -> {
                    binding.btnLogout.isEnabled = true
                    Toast.makeText(requireContext(), result.message, Toast.LENGTH_SHORT).show()
                }
            }
        }
    }

    private fun observeProfile() {
        viewModel.profile.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Success -> {
                    val user = result.data
                    binding.tvUserName.text = user.name
                    binding.tvUserEmail.text = user.email
                }
                is Resource.Error -> {
                    binding.tvUserName.text = "User"
                    binding.tvUserEmail.text = ""
                }
                is Resource.Loading -> {}
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
