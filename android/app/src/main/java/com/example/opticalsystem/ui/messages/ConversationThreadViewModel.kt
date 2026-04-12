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

    private val navConversationId: Int = savedStateHandle.get<Int>("conversationId") ?: 0

    /** `0` until the first message is sent (server creates the thread). */
    private var resolvedConversationId: Int = navConversationId.coerceAtLeast(0)

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
        if (resolvedConversationId > 0) {
            loadMessages()
        } else {
            _messages.value = Resource.Success(emptyList())
        }
    }

    // ── Load ──────────────────────────────────────────────────────────────────

    fun loadMessages() {
        if (resolvedConversationId <= 0) {
            _messages.value = Resource.Success(emptyList())
            return
        }
        if (_messages.value !is Resource.Success) {
            _messages.value = Resource.Loading
        }
        viewModelScope.launch {
            fetchMessages()
        }
    }

    private suspend fun fetchMessages() {
        if (resolvedConversationId <= 0) {
            return
        }
        when (val result = conversationRepository.getMessages(resolvedConversationId)) {
            is Resource.Success -> {
                _messages.value = Resource.Success(result.data)
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
        if (resolvedConversationId <= 0) {
            return
        }
        when (val result = conversationRepository.getConversation(resolvedConversationId)) {
            is Resource.Success -> _conversation.value = result.data
            else -> {}
        }
    }

    // ── Send ──────────────────────────────────────────────────────────────────

    fun sendMessage(body: String) {
        if (body.isBlank() || _isSending.value == true) return
        _isSending.value = true
        _sendError.value = null

        val currentMessages = (_messages.value as? Resource.Success)?.data.orEmpty()
        val optimisticMsg = Message(
            id = Int.MIN_VALUE,
            conversationId = resolvedConversationId.coerceAtLeast(0),
            sender = null,
            body = body,
            isRead = false,
            readAt = null,
            createdAt = "",
        )
        _messages.value = Resource.Success(currentMessages + optimisticMsg)
        _scrollToBottom.value = true

        viewModelScope.launch {
            if (resolvedConversationId <= 0) {
                when (val result = conversationRepository.sendMessageToMyConversation(body)) {
                    is Resource.Success -> {
                        val payload = result.data
                        resolvedConversationId = payload.conversation.id
                        _conversation.value = payload.conversation
                        val updated = (_messages.value as? Resource.Success)?.data.orEmpty()
                            .filter { it.id != Int.MIN_VALUE } + payload.message
                        _messages.value = Resource.Success(updated)
                        _scrollToBottom.value = true
                    }
                    is Resource.Error -> {
                        val rolled = (_messages.value as? Resource.Success)?.data.orEmpty()
                            .filter { it.id != Int.MIN_VALUE }
                        _messages.value = Resource.Success(rolled)
                        _sendError.value = result.message
                    }
                    is Resource.Loading -> {}
                }
            } else {
                when (val result = conversationRepository.sendMessage(resolvedConversationId, body)) {
                    is Resource.Success -> {
                        val updated = (_messages.value as? Resource.Success)?.data.orEmpty()
                            .filter { it.id != Int.MIN_VALUE } + result.data
                        _messages.value = Resource.Success(updated)
                        _scrollToBottom.value = true
                        loadConversation()
                    }
                    is Resource.Error -> {
                        val rolled = (_messages.value as? Resource.Success)?.data.orEmpty()
                            .filter { it.id != Int.MIN_VALUE }
                        _messages.value = Resource.Success(rolled)
                        _sendError.value = result.message
                    }
                    is Resource.Loading -> {}
                }
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
                if (resolvedConversationId <= 0) {
                    continue
                }
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
