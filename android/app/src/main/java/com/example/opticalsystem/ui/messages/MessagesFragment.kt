package com.example.opticalsystem.ui.messages

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentMessagesBinding
import com.example.opticalsystem.util.Resource
import com.google.android.material.snackbar.Snackbar
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

        // "Message Us" button
        binding.btnStartConversation.setOnClickListener {
            viewModel.startConversation(subject = null)
        }

        observeViewModel()
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
                    binding.groupEmptyState.isVisible = false
                    binding.groupClosedState.isVisible = false
                    binding.groupError.isVisible = false
                }
                is Resource.Success -> {
                    binding.progressBar.isVisible = false
                    val conversations = state.data
                    val closedConversation = conversations.firstOrNull { it.isClosed }

                    if (closedConversation != null) {
                        // Has a closed conversation but no open one
                        binding.groupClosedState.isVisible = true
                        binding.groupEmptyState.isVisible = false
                        binding.groupError.isVisible = false
                        binding.btnNewConversationClosed.setOnClickListener {
                            viewModel.startConversation(subject = null)
                        }
                    } else {
                        // Truly no conversations
                        binding.groupEmptyState.isVisible = true
                        binding.groupClosedState.isVisible = false
                        binding.groupError.isVisible = false
                    }
                }
                is Resource.Error -> {
                    binding.progressBar.isVisible = false
                    binding.groupEmptyState.isVisible = false
                    binding.groupClosedState.isVisible = false
                    binding.groupError.isVisible = true
                    binding.tvError.text = state.message
                    binding.btnRetry.setOnClickListener { viewModel.loadConversations() }
                }
            }
        }

        viewModel.navigateToThread.observe(viewLifecycleOwner) { conversation ->
            if (conversation != null) {
                findNavController().navigate(
                    R.id.action_nav_orders_to_conversationThread,
                    Bundle().apply { putInt("conversationId", conversation.id) },
                )
                viewModel.onNavigatedToThread()
            }
        }

        viewModel.error.observe(viewLifecycleOwner) { msg ->
            if (msg != null) {
                Snackbar.make(binding.root, msg, Snackbar.LENGTH_LONG).show()
                viewModel.clearError()
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
