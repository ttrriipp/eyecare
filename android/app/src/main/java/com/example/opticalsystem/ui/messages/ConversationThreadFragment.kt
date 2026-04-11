package com.example.opticalsystem.ui.messages

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.FrameLayout
import androidx.core.view.isVisible
import androidx.core.widget.doAfterTextChanged
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import androidx.navigation.ui.AppBarConfiguration
import androidx.navigation.ui.NavigationUI
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.FragmentConversationThreadBinding
import com.example.opticalsystem.util.Resource
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.google.android.material.snackbar.Snackbar
import com.google.android.material.textfield.TextInputEditText
import com.google.android.material.textfield.TextInputLayout
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

        setupToolbar()
        setupRecyclerView()
        setupInputBar()
        observeViewModel()
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

    private fun setupToolbar() {
        val navController = findNavController()
        val appBarConfiguration = AppBarConfiguration(
            setOf(
                R.id.nav_home,
                R.id.nav_explore,
                R.id.nav_schedule,
                R.id.nav_orders,
                R.id.nav_profile,
            ),
        )
        NavigationUI.setupWithNavController(binding.toolbar, navController, appBarConfiguration)
    }

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

        viewModel.conversation.observe(viewLifecycleOwner) { conversation ->
            val isClosed = conversation?.isClosed == true
            binding.bannerClosed.isVisible = isClosed
            binding.inputBar.isVisible = !isClosed
            binding.toolbar.title = when {
                isClosed -> getString(R.string.conversation_closed)
                else -> conversation?.subject?.takeIf { it.isNotBlank() }
                    ?: getString(R.string.conversation_default_title)
            }
            binding.btnNewConversationBanner.setOnClickListener {
                showSubjectDialog()
            }
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

    private fun showSubjectDialog() {
        val ctx = requireContext()
        val inputLayout = TextInputLayout(ctx).apply {
            boxBackgroundMode = TextInputLayout.BOX_BACKGROUND_OUTLINE
            hint = getString(R.string.conversation_subject_hint)
            setBoxCornerRadii(12f, 12f, 12f, 12f)
        }
        val editText = TextInputEditText(inputLayout.context)
        editText.inputType =
            android.text.InputType.TYPE_CLASS_TEXT or android.text.InputType.TYPE_TEXT_FLAG_CAP_SENTENCES
        editText.maxLines = 2
        inputLayout.addView(editText)

        val container = FrameLayout(ctx).apply {
            val horizontal = (16 * resources.displayMetrics.density).toInt()
            val top = (8 * resources.displayMetrics.density).toInt()
            setPadding(horizontal, top, horizontal, 0)
            addView(inputLayout)
        }

        val dialog = MaterialAlertDialogBuilder(ctx)
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
                    parentFragmentManager.setFragmentResult(
                        MessagesFragment.REQUEST_START_NEW_CONVERSATION,
                        Bundle().apply { putString(KEY_SUBJECT, subject) },
                    )
                    findNavController().popBackStack()
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
        const val KEY_SUBJECT = "conversation_subject"
    }
}
