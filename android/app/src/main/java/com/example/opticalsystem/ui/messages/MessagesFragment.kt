package com.example.opticalsystem.ui.messages

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.EditText
import android.widget.FrameLayout
import androidx.core.os.bundleOf
import androidx.core.view.isVisible
import androidx.core.widget.doAfterTextChanged
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentMessagesBinding
import com.example.opticalsystem.util.Resource
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.google.android.material.snackbar.Snackbar
import com.google.android.material.textfield.TextInputEditText
import com.google.android.material.textfield.TextInputLayout
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class MessagesFragment : Fragment() {

    private var _binding: FragmentMessagesBinding? = null
    private val binding get() = _binding!!

    private val viewModel: MessagingViewModel by viewModels()
    private lateinit var conversationsAdapter: ConversationsAdapter

    private var suppressNextResumeRefresh = false

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

        setupConversationList()

        binding.btnStartConversation.setOnClickListener { showSubjectDialog() }
        binding.btnNewConversationList.setOnClickListener { showSubjectDialog() }

        parentFragmentManager.setFragmentResultListener(REQUEST_START_NEW_CONVERSATION, viewLifecycleOwner) { _, bundle ->
            val subject = bundle.getString(ConversationThreadFragment.KEY_SUBJECT)
            if (!subject.isNullOrBlank()) {
                viewModel.startConversation(subject = subject)
            } else {
                showSubjectDialog()
            }
        }

        observeViewModel()
        suppressNextResumeRefresh = true
        viewModel.loadConversations()
    }

    override fun onResume() {
        super.onResume()
        if (suppressNextResumeRefresh) {
            suppressNextResumeRefresh = false
        } else {
            viewModel.refreshConversations()
        }
    }

    override fun onStart() {
        super.onStart()
        viewModel.startPolling()
    }

    override fun onStop() {
        super.onStop()
        viewModel.stopPolling()
    }

    private fun setupConversationList() {
        conversationsAdapter = ConversationsAdapter { conversation ->
            findNavController().navigate(
                R.id.action_nav_orders_to_conversationThread,
                bundleOf("conversationId" to conversation.id),
            )
        }
        binding.rvConversations.apply {
            layoutManager = LinearLayoutManager(requireContext())
            adapter = conversationsAdapter
        }
    }

    private fun observeViewModel() {
        viewModel.conversationState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is Resource.Loading -> {
                    binding.progressBar.isVisible = true
                    binding.groupEmptyState.isVisible = false
                    binding.groupConversationList.isVisible = false
                    binding.btnNewConversationList.isVisible = false
                    binding.groupError.isVisible = false
                }
                is Resource.Success -> {
                    binding.progressBar.isVisible = false
                    binding.groupError.isVisible = false
                    val conversations = state.data
                    if (conversations.isEmpty()) {
                        binding.groupConversationList.isVisible = false
                        binding.btnNewConversationList.isVisible = false
                        binding.groupEmptyState.isVisible = true
                    } else {
                        binding.groupEmptyState.isVisible = false
                        binding.groupConversationList.isVisible = true
                        conversationsAdapter.submitList(conversations)
                    }
                }
                is Resource.Error -> {
                    binding.progressBar.isVisible = false
                    binding.groupEmptyState.isVisible = false
                    binding.groupConversationList.isVisible = false
                    binding.btnNewConversationList.isVisible = false
                    binding.groupError.isVisible = true
                    binding.tvError.text = state.message
                    binding.btnRetry.setOnClickListener { viewModel.loadConversations() }
                }
            }
        }

        viewModel.hasOpenConversation.observe(viewLifecycleOwner) { hasOpen ->
            val hasConversations = (viewModel.conversationState.value as? Resource.Success)?.data?.isNotEmpty() == true
            binding.btnNewConversationList.isVisible = hasConversations && !hasOpen
        }

        viewModel.navigateToThread.observe(viewLifecycleOwner) { conversation ->
            if (conversation != null) {
                findNavController().navigate(
                    R.id.action_nav_orders_to_conversationThread,
                    bundleOf("conversationId" to conversation.id),
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

    private fun showSubjectDialog() {
        val context = requireContext()
        val inputLayout = TextInputLayout(context).apply {
            boxBackgroundMode = TextInputLayout.BOX_BACKGROUND_OUTLINE
            hint = getString(R.string.conversation_subject_hint)
            setBoxCornerRadii(12f, 12f, 12f, 12f)
        }
        val editText = TextInputEditText(inputLayout.context)
        editText.inputType =
            android.text.InputType.TYPE_CLASS_TEXT or android.text.InputType.TYPE_TEXT_FLAG_CAP_SENTENCES
        editText.maxLines = 2
        inputLayout.addView(editText)

        val container = FrameLayout(context).apply {
            val horizontal = (16 * resources.displayMetrics.density).toInt()
            val top = (8 * resources.displayMetrics.density).toInt()
            setPadding(horizontal, top, horizontal, 0)
            addView(inputLayout)
        }

        val dialog = MaterialAlertDialogBuilder(context)
            .setTitle(R.string.conversation_subject_title)
            .setView(container)
            .setPositiveButton(R.string.start_conversation_action, null)
            .setNegativeButton(android.R.string.cancel, null)
            .create()

        dialog.setOnShowListener {
            val positiveBtn = dialog.getButton(android.app.AlertDialog.BUTTON_POSITIVE)
            positiveBtn.isEnabled = false
            editText.doAfterTextChanged { text ->
                positiveBtn.isEnabled = !text.isNullOrBlank()
            }
            positiveBtn.setOnClickListener {
                val subject = editText.text?.toString()?.trim().orEmpty()
                if (subject.isNotBlank()) {
                    dialog.dismiss()
                    viewModel.startConversation(subject = subject)
                }
            }
            editText.requestFocus()
        }
        dialog.show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }

    companion object {
        const val REQUEST_START_NEW_CONVERSATION = "messages_request_start_new_conversation"
    }
}
