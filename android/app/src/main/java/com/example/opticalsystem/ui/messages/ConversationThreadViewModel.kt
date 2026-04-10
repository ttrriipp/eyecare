package com.example.opticalsystem.ui.messages

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.model.Conversation
import com.example.opticalsystem.data.model.Message
import com.example.opticalsystem.data.repository.ConversationRepository
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ConversationThreadViewModel @Inject constructor(
    private val conversationRepository: ConversationRepository,
    savedStateHandle: SavedStateHandle,
) : ViewModel() {

    val conversationId: Int = savedStateHandle.get<Int>("conversationId") ?: -1

    // ── State ─────────────────────────────────────────────────────────────────

    private val _messages = MutableLiveData<Resource<List<Message>>>()
    val messages: LiveData<Resource<List<Message>>> = _messages

    private val _conversation = MutableLiveData<Conversation?>()
    val conversation: LiveData<Conversation?> = _conversation

    private val _isSending = MutableLiveData(false)
    val isSending: LiveData<Boolean> = _isSending

    private val _sendError = MutableLiveData<String?>()
    val sendError: LiveData<String?> = _sendError

    /** True after a new message is sent — fragment scrolls to bottom on this signal. */
    private val _scrollToBottom = MutableLiveData(false)
    val scrollToBottom: LiveData<Boolean> = _scrollToBottom

    private var lastKnownUnreadCount = -1
    private var pollingJob: Job? = null

    // ── Init ──────────────────────────────────────────────────────────────────

    init {
        loadMessages()
    }

    // ── Load ──────────────────────────────────────────────────────────────────

    fun loadMessages() {
        if (_messages.value !is Resource.Success) {
            _messages.value = Resource.Loading
        }
        viewModelScope.launch {
            fetchMessages()
        }
    }

    private suspend fun fetchMessages() {
        when (val result = conversationRepository.getMessages(conversationId)) {
            is Resource.Success -> {
                _messages.value = Resource.Success(result.data)
                // Reload conversation status in case it changed.
                loadConversation()
            }
            is Resource.Error -> {
                if (_messages.value !is Resource.Success) {
                    _messages.value = Resource.Error(result.message)
                }
            }
            is Resource.Loading -> {}
        }
    }

    private suspend fun loadConversation() {
        when (val result = conversationRepository.getConversations()) {
            is Resource.Success -> {
                _conversation.value = result.data.firstOrNull { it.id == conversationId }
            }
            else -> {}
        }
    }

    // ── Send ──────────────────────────────────────────────────────────────────

    fun sendMessage(body: String) {
        if (body.isBlank() || _isSending.value == true) return
        _isSending.value = true
        _sendError.value = null

        // Optimistic append
        val currentMessages = (_messages.value as? Resource.Success)?.data.orEmpty()
        val optimisticMsg = Message(
            id = Int.MIN_VALUE,
            conversationId = conversationId,
            sender = null, // will be replaced on real response
            body = body,
            isRead = false,
            readAt = null,
            createdAt = "",
        )
        _messages.value = Resource.Success(currentMessages + optimisticMsg)
        _scrollToBottom.value = true

        viewModelScope.launch {
            when (val result = conversationRepository.sendMessage(conversationId, body)) {
                is Resource.Success -> {
                    // Replace optimistic with real message
                    val updated = (_messages.value as? Resource.Success)?.data.orEmpty()
                        .dropLast(1) + result.data
                    _messages.value = Resource.Success(updated)
                    _scrollToBottom.value = true
                }
                is Resource.Error -> {
                    // Rollback optimistic
                    val rolled = (_messages.value as? Resource.Success)?.data.orEmpty()
                        .filter { it.id != Int.MIN_VALUE }
                    _messages.value = Resource.Success(rolled)
                    _sendError.value = result.message
                }
                is Resource.Loading -> {}
            }
            _isSending.value = false
        }
    }

    fun clearSendError() {
        _sendError.value = null
    }

    fun onScrolledToBottom() {
        _scrollToBottom.value = false
    }

    // ── Polling ───────────────────────────────────────────────────────────────

    fun startPolling() {
        if (pollingJob?.isActive == true) return
        pollingJob = viewModelScope.launch {
            while (isActive) {
                delay(10_000)
                when (val result = conversationRepository.getUnreadCount()) {
                    is Resource.Success -> {
                        val count = result.data
                        if (count != lastKnownUnreadCount) {
                            lastKnownUnreadCount = count
                            fetchMessages()
                        }
                    }
                    else -> { /* silent */ }
                }
            }
        }
    }

    fun stopPolling() {
        pollingJob?.cancel()
        pollingJob = null
    }

    override fun onCleared() {
        super.onCleared()
        stopPolling()
    }
}
