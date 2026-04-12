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

    /** When non-null, navigate to this thread: real id, or `0` before the first message exists. */
    private val _navigateToThreadId = MutableLiveData<Int?>()
    val navigateToThreadId: LiveData<Int?> = _navigateToThreadId

    private val _unreadCount = MutableLiveData(0)
    val unreadCount: LiveData<Int> = _unreadCount

    private var pollingJob: Job? = null

    fun loadConversations() {
        _conversationState.value = Resource.Loading
        viewModelScope.launch {
            when (val result = conversationRepository.getConversations()) {
                is Resource.Success -> {
                    val first = result.data.firstOrNull()
                    _navigateToThreadId.value = first?.id ?: 0
                    _conversationState.value = Resource.Success(Unit)
                }
                is Resource.Error -> _conversationState.value = Resource.Error(result.message)
                is Resource.Loading -> {}
            }
        }
    }

    fun onNavigatedToThread() {
        _navigateToThreadId.value = null
    }

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
