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

        binding.btnOpenChat.setOnClickListener { viewModel.openChat() }

        observeViewModel()
    }

    override fun onResume() {
        super.onResume()
        val skipLoading = viewModel.conversationState.value is Resource.Success
        viewModel.loadConversations(showLoading = !skipLoading)
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
                    binding.groupIntro.isVisible = false
                }
                is Resource.Success -> {
                    binding.progressBar.isVisible = false
                    binding.groupError.isVisible = false
                    binding.groupIntro.isVisible = true
                }
                is Resource.Error -> {
                    binding.progressBar.isVisible = false
                    binding.groupError.isVisible = true
                    binding.groupIntro.isVisible = false
                    binding.tvError.text = state.message
                    binding.btnRetry.setOnClickListener { viewModel.loadConversations() }
                }
            }
        }

        viewModel.unreadCount.observe(viewLifecycleOwner) { count ->
            if (count > 0) {
                binding.tvIntroUnread.text = resources.getQuantityString(
                    R.plurals.messages_intro_unread,
                    count,
                    count,
                )
                binding.tvIntroUnread.isVisible = true
            } else {
                binding.tvIntroUnread.isVisible = false
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
