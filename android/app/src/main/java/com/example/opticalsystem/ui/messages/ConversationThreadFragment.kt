package com.example.opticalsystem.ui.messages

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.view.WindowManager
import androidx.appcompat.content.res.AppCompatResources
import androidx.core.view.isVisible
import androidx.core.widget.doAfterTextChanged
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentConversationThreadBinding
import com.example.opticalsystem.util.Resource
import com.google.android.material.snackbar.Snackbar
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class ConversationThreadFragment : Fragment() {

    private var _binding: FragmentConversationThreadBinding? = null
    private val binding get() = _binding!!

    private val viewModel: ConversationThreadViewModel by viewModels()
    private lateinit var messageAdapter: MessageAdapter

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentConversationThreadBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.toolbar.navigationIcon = AppCompatResources.getDrawable(
            requireContext(),
            R.drawable.ic_back_24,
        )
        binding.toolbar.setNavigationOnClickListener {
            requireActivity().onBackPressedDispatcher.onBackPressed()
        }

        setupRecyclerView()
        setupInputBar()
        observeViewModel()
    }

    override fun onResume() {
        super.onResume()
        // adjustResize + edge-to-edge often fails to lift AndroidFragment inside Compose; use
        // SOFT_INPUT_ADJUST_NOTHING here so NavHost imePadding() is the sole IME inset source.
        requireActivity().window.setSoftInputMode(WindowManager.LayoutParams.SOFT_INPUT_ADJUST_NOTHING)
    }

    override fun onPause() {
        requireActivity().window.setSoftInputMode(WindowManager.LayoutParams.SOFT_INPUT_ADJUST_RESIZE)
        super.onPause()
    }

    override fun onStart() {
        super.onStart()
        viewModel.startPolling()
    }

    override fun onStop() {
        super.onStop()
        viewModel.stopPolling()
    }

    // ── Setup ─────────────────────────────────────────────────────────────────

    private fun setupRecyclerView() {
        messageAdapter = MessageAdapter()
        binding.rvMessages.apply {
            adapter = messageAdapter
            layoutManager = LinearLayoutManager(requireContext()).also {
                it.stackFromEnd = true
            }
        }
    }

    private fun setupInputBar() {
        binding.etMessageBody.doAfterTextChanged { text ->
            binding.btnSend.isEnabled = !text.isNullOrBlank() && viewModel.isSending.value != true
        }

        binding.btnSend.setOnClickListener {
            val body = binding.etMessageBody.text?.toString()?.trim() ?: return@setOnClickListener
            if (body.isBlank()) return@setOnClickListener
            viewModel.sendMessage(body)
            binding.etMessageBody.text?.clear()
        }
    }

    // ── Observe ───────────────────────────────────────────────────────────────

    private fun observeViewModel() {
        viewModel.messages.observe(viewLifecycleOwner) { state ->
            when (state) {
                is Resource.Loading -> {
                    binding.progressBar.isVisible = true
                }
                is Resource.Success -> {
                    binding.progressBar.isVisible = false
                    messageAdapter.submitMessages(state.data)
                }
                is Resource.Error -> {
                    binding.progressBar.isVisible = false
                    Snackbar.make(binding.root, state.message, Snackbar.LENGTH_LONG)
                        .setAction(getString(R.string.retry)) { viewModel.loadMessages() }
                        .show()
                }
            }
        }

        viewModel.conversation.observe(viewLifecycleOwner) {
            binding.bannerClosed.isVisible = false
            binding.toolbar.title = getString(R.string.conversation_default_title)
        }

        viewModel.isSending.observe(viewLifecycleOwner) { sending ->
            binding.btnSend.isEnabled = !sending &&
                binding.etMessageBody.text?.isNotBlank() == true
            binding.etMessageBody.isEnabled = !sending
        }

        viewModel.sendError.observe(viewLifecycleOwner) { msg ->
            if (msg != null) {
                Snackbar.make(binding.root, msg, Snackbar.LENGTH_LONG).show()
                viewModel.clearSendError()
            }
        }

        viewModel.scrollToBottom.observe(viewLifecycleOwner) { should ->
            if (should) {
                scrollToBottom()
                viewModel.onScrolledToBottom()
            }
        }
    }

    private fun scrollToBottom() {
        val adapter = binding.rvMessages.adapter ?: return
        if (adapter.itemCount > 0) {
            binding.rvMessages.smoothScrollToPosition(adapter.itemCount - 1)
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
