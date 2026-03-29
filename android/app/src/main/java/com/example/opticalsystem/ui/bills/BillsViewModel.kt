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
class BillsViewModel @Inject constructor(
    private val billRepository: BillRepository,
) : ViewModel() {

    private val _bills = MutableLiveData<Resource<List<Bill>>>()
    val bills: LiveData<Resource<List<Bill>>> = _bills

    private var currentPaymentStatus: String? = null

    init {
        loadBills()
    }

    fun loadBills(paymentStatus: String? = currentPaymentStatus) {
        currentPaymentStatus = paymentStatus
        _bills.value = Resource.Loading
        viewModelScope.launch {
            when (val result = billRepository.getBills(paymentStatus = paymentStatus)) {
                is Resource.Success -> _bills.value = Resource.Success(result.data.first)
                is Resource.Error -> _bills.value = Resource.Error(result.message)
                is Resource.Loading -> {}
            }
        }
    }

    fun filterByStatus(paymentStatus: String?) {
        loadBills(paymentStatus)
    }

    fun refresh() {
        loadBills()
    }
}
