package com.example.opticalsystem.ui.products

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.model.Product
import com.example.opticalsystem.data.model.ProductCategory
import com.example.opticalsystem.data.repository.ProductRepository
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ProductListViewModel @Inject constructor(
    private val productRepository: ProductRepository,
) : ViewModel() {

    private val _products = MutableLiveData<Resource<List<Product>>>()
    val products: LiveData<Resource<List<Product>>> = _products

    private val _categories = MutableLiveData<Resource<List<ProductCategory>>>()
    val categories: LiveData<Resource<List<ProductCategory>>> = _categories

    init {
        loadProducts()
        loadCategories()
    }

    fun loadProducts(
        categoryId: Int? = null,
        search: String? = null,
    ) {
        _products.value = Resource.Loading
        viewModelScope.launch {
            _products.value = productRepository.getProducts(
                categoryId = categoryId,
                search = search,
            )
        }
    }

    private fun loadCategories() {
        viewModelScope.launch {
            _categories.value = productRepository.getCategories()
        }
    }
}
