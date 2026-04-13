package com.example.opticalsystem.ui.messages

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
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

    private val _conversationState = MutableLiveData<Resource<Unit>>()
    val conversationState: LiveData<Resource<Unit>> = _conversationState

    /** Fired when the user chooses to open the chat; `0` if the thread is not created yet. */
    private val _navigateToThreadId = MutableLiveData<Int?>()
    val navigateToThreadId: LiveData<Int?> = _navigateToThreadId

    private val _unreadCount = MutableLiveData(0)
    val unreadCount: LiveData<Int> = _unreadCount

    private var cachedThreadId: Int = 0

    private var pollingJob: Job? = null

    fun loadConversations(showLoading: Boolean = true) {
        if (showLoading) {
            _conversationState.value = Resource.Loading
        }
        viewModelScope.launch {
            when (val result = conversationRepository.getConversations()) {
                is Resource.Success -> {
                    cachedThreadId = result.data.firstOrNull()?.id ?: 0
                    refreshUnreadCount()
                    _conversationState.value = Resource.Success(Unit)
                }
                is Resource.Error -> _conversationState.value = Resource.Error(result.message)
                is Resource.Loading -> {}
            }
        }
    }

    fun openChat() {
        _navigateToThreadId.value = cachedThreadId
    }

    fun onNavigatedToThread() {
        _navigateToThreadId.value = null
    }

    private suspend fun refreshUnreadCount() {
        when (val result = conversationRepository.getUnreadCount()) {
            is Resource.Success -> _unreadCount.value = result.data
            else -> { /* keep previous */ }
        }
    }

    fun startPolling() {
        if (pollingJob?.isActive == true) return
        pollingJob = viewModelScope.launch {
            while (isActive) {
                refreshUnreadCount()
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
