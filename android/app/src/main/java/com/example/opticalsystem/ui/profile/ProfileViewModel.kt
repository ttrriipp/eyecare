package com.example.opticalsystem.ui.profile

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.model.User
import com.example.opticalsystem.data.repository.AuthRepository
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ProfileViewModel @Inject constructor(
    private val authRepository: AuthRepository,
) : ViewModel() {

    private val _logoutResult = MutableLiveData<Resource<Unit>>()
    val logoutResult: LiveData<Resource<Unit>> = _logoutResult

    private val _profile = MutableLiveData<Resource<User>>()
    val profile: LiveData<Resource<User>> = _profile

    fun loadProfile() {
        val hadData = _profile.value is Resource.Success
        if (!hadData) {
            _profile.value = Resource.Loading
        }
        viewModelScope.launch {
            when (val r = authRepository.getProfile()) {
                is Resource.Success -> _profile.value = r
                is Resource.Error -> {
                    if (!hadData) {
                        _profile.value = r
                    }
                }
                is Resource.Loading -> {}
            }
        }
    }

    fun logout() {
        _logoutResult.value = Resource.Loading
        viewModelScope.launch {
            _logoutResult.value = authRepository.logout()
        }
    }
}
