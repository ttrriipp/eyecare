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
class EditProfileViewModel @Inject constructor(
    private val authRepository: AuthRepository,
) : ViewModel() {

    private val _profileLoad = MutableLiveData<Resource<User>>()
    val profileLoad: LiveData<Resource<User>> = _profileLoad

    private val _saveResult = MutableLiveData<Resource<User>?>()
    val saveResult: LiveData<Resource<User>?> = _saveResult

    init {
        loadProfile()
    }

    fun loadProfile() {
        _profileLoad.value = Resource.Loading
        viewModelScope.launch {
            _profileLoad.value = authRepository.getProfile()
        }
    }

    fun save(name: String, phone: String, dateOfBirth: String, address: String) {
        _saveResult.value = Resource.Loading
        viewModelScope.launch {
            _saveResult.value = authRepository.updateProfile(
                name = name,
                phone = phone,
                dateOfBirth = dateOfBirth.ifBlank { null },
                address = address.ifBlank { null },
            )
        }
    }

    fun clearSaveResult() {
        _saveResult.value = null
    }
}
