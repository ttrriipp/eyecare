package com.example.opticalsystem.ui.auth

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
class RegisterViewModel @Inject constructor(
    private val authRepository: AuthRepository,
) : ViewModel() {

    private val _registerResult = MutableLiveData<Resource<User>>()
    val registerResult: LiveData<Resource<User>> = _registerResult

    fun register(
        name: String,
        email: String,
        phone: String?,
        password: String,
        passwordConfirmation: String,
    ) {
        _registerResult.value = Resource.Loading
        viewModelScope.launch {
            _registerResult.value = authRepository.register(
                name, email, phone, password, passwordConfirmation
            )
        }
    }
}
