package com.example.opticalsystem.ui.home

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.repository.AuthRepository
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class HomeViewModel @Inject constructor(
    private val authRepository: AuthRepository,
) : ViewModel() {

    private val _userName = MutableLiveData<String?>()
    val userName: LiveData<String?> = _userName

    init {
        loadProfile()
    }

    private fun loadProfile() {
        viewModelScope.launch {
            when (val result = authRepository.getProfile()) {
                is Resource.Success -> _userName.value = result.data.name
                is Resource.Error -> _userName.value = "User"
                is Resource.Loading -> {}
            }
        }
    }
}
