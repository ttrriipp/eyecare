package com.example.opticalsystem.ui.messages

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.model.Conversation
import com.example.opticalsystem.data.repository.ConversationRepository
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class MessagingViewModel @Inject constructor(
    private val conversationRepository: ConversationRepository,
) : ViewModel() {

    // ── State ─────────────────────────────────────────────────────────────────

    private val _conversationState = MutableLiveData<Resource<List<Conversation>>>()
    val conversationState: LiveData<Resource<List<Conversation>>> = _conversationState

    /** Non-null once a conversation is started/found. Used to navigate to the thread. */
    private val _navigateToThread = MutableLiveData<Conversation?>()
    val navigateToThread: LiveData<Conversation?> = _navigateToThread

    private val _hasOpenConversation = MutableLiveData(false)
    val hasOpenConversation: LiveData<Boolean> = _hasOpenConversation

    private val _unreadCount = MutableLiveData(0)
    val unreadCount: LiveData<Int> = _unreadCount

    private val _error = MutableLiveData<String?>()
    val error: LiveData<String?> = _error

    private var pollingJob: Job? = null

    // ── Load ──────────────────────────────────────────────────────────────────

    fun loadConversations() {
        _conversationState.value = Resource.Loading
        viewModelScope.launch {
            val result = conversationRepository.getConversations()
            when (result) {
                is Resource.Success -> {
                    val sorted = sortConversations(result.data)
                    _conversationState.value = Resource.Success(sorted)
                    _hasOpenConversation.value = sorted.any { it.isOpen }
                }
                is Resource.Error -> _conversationState.value = Resource.Error(result.message)
                is Resource.Loading -> {}
            }
        }
    }

    fun onNavigatedToThread() {
        _navigateToThread.value = null
    }

    /** Refreshes the list without clearing the UI to a loading state (e.g. after returning from a thread). */
    fun refreshConversations() {
        viewModelScope.launch {
            when (val result = conversationRepository.getConversations()) {
                is Resource.Success -> {
                    val sorted = sortConversations(result.data)
                    _conversationState.value = Resource.Success(sorted)
                    _hasOpenConversation.value = sorted.any { it.isOpen }
                }
                else -> { /* keep existing list on error */ }
            }
        }
    }

    private fun sortConversations(list: List<Conversation>): List<Conversation> {
        return list.sortedByDescending { it.lastMessageAt ?: it.createdAt }
    }

    // ── Start a new conversation ──────────────────────────────────────────────

    fun startConversation(subject: String? = null) {
        _conversationState.value = Resource.Loading
        viewModelScope.launch {
            when (val result = conversationRepository.startConversation(subject)) {
                is Resource.Success -> _navigateToThread.value = result.data
                is Resource.Error -> {
                    _error.value = result.message
                    _conversationState.value = Resource.Success(emptyList())
                }
                is Resource.Loading -> {}
            }
        }
    }

    fun clearError() {
        _error.value = null
    }

    // ── Unread badge polling ──────────────────────────────────────────────────

    fun startPolling() {
        if (pollingJob?.isActive == true) return
        pollingJob = viewModelScope.launch {
            while (isActive) {
                when (val result = conversationRepository.getUnreadCount()) {
                    is Resource.Success -> _unreadCount.value = result.data
                    else -> { /* silent fail — don't disrupt UI */ }
                }
                delay(10_000)
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
