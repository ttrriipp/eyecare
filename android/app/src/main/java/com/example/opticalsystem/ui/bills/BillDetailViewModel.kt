package com.example.opticalsystem.ui.bills

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.model.Bill
import com.example.opticalsystem.data.repository.BillRepository
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class BillDetailViewModel @Inject constructor(
    private val billRepository: BillRepository,
) : ViewModel() {

    private val _bill = MutableLiveData<Resource<Bill>>()
    val bill: LiveData<Resource<Bill>> = _bill

    fun loadBill(id: Int) {
        _bill.value = Resource.Loading
        viewModelScope.launch {
            _bill.value = billRepository.getBill(id)
        }
    }
}
