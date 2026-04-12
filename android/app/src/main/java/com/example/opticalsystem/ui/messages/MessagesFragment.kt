package com.example.opticalsystem.ui.messages

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.os.bundleOf
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.NavOptions
import androidx.navigation.fragment.findNavController
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentMessagesBinding
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class MessagesFragment : Fragment() {

    private var _binding: FragmentMessagesBinding? = null
    private val binding get() = _binding!!

    private val viewModel: MessagingViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentMessagesBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        observeViewModel()
    }

    override fun onResume() {
        super.onResume()
        viewModel.loadConversations()
    }

    override fun onStart() {
        super.onStart()
        viewModel.startPolling()
    }

    override fun onStop() {
        super.onStop()
        viewModel.stopPolling()
    }

    private fun observeViewModel() {
        viewModel.conversationState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is Resource.Loading -> {
                    binding.progressBar.isVisible = true
                    binding.groupError.isVisible = false
                    binding.groupEmptyState.isVisible = false
                    binding.groupClosedState.isVisible = false
                }
                is Resource.Success -> {
                    binding.progressBar.isVisible = false
                }
                is Resource.Error -> {
                    binding.progressBar.isVisible = false
                    binding.groupError.isVisible = true
                    binding.groupEmptyState.isVisible = false
                    binding.groupClosedState.isVisible = false
                    binding.tvError.text = state.message
                    binding.btnRetry.setOnClickListener { viewModel.loadConversations() }
                }
            }
        }

        viewModel.navigateToThreadId.observe(viewLifecycleOwner) { id ->
            if (id != null) {
                val options = NavOptions.Builder()
                    .setPopUpTo(R.id.conversationThreadFragment, true)
                    .build()
                findNavController().navigate(
                    R.id.action_nav_orders_to_conversationThread,
                    bundleOf("conversationId" to id),
                    options,
                )
                viewModel.onNavigatedToThread()
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
