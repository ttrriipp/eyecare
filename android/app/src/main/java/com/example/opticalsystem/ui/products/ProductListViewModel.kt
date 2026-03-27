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

    private val _totalCount = MutableLiveData<Int>(0)
    val totalCount: LiveData<Int> = _totalCount

    private var currentCategoryId: Int? = null
    private var currentSearch: String? = null
    private var currentSortBy: String? = null
    private var currentSortDir: String? = null

    init {
        loadProducts()
        loadCategories()
    }

    fun loadProducts(
        categoryId: Int? = currentCategoryId,
        search: String? = currentSearch,
        sortBy: String? = currentSortBy,
        sortDir: String? = currentSortDir,
    ) {
        currentCategoryId = categoryId
        currentSearch = search
        currentSortBy = sortBy
        currentSortDir = sortDir
        _products.value = Resource.Loading
        viewModelScope.launch {
            val response = productRepository.getProducts(
                categoryId = categoryId,
                search = search,
                sortBy = sortBy,
                sortDir = sortDir,
            )
            when (response) {
                is Resource.Success -> {
                    _products.value = Resource.Success(response.data.first)
                    _totalCount.value = response.data.second
                }
                is Resource.Error -> _products.value = Resource.Error(response.message)
                is Resource.Loading -> Unit
            }
        }
    }

    fun applySearch(query: String?) {
        loadProducts(
            categoryId = currentCategoryId,
            search = query?.takeIf { it.isNotBlank() },
            sortBy = currentSortBy,
            sortDir = currentSortDir,
        )
    }

    fun applyCategory(categoryId: Int?) {
        loadProducts(
            categoryId = categoryId,
            search = currentSearch,
            sortBy = currentSortBy,
            sortDir = currentSortDir,
        )
    }

    fun applySort(sortBy: String?, sortDir: String?) {
        loadProducts(
            categoryId = currentCategoryId,
            search = currentSearch,
            sortBy = sortBy,
            sortDir = sortDir,
        )
    }

    fun refreshProducts() {
        loadProducts(
            categoryId = currentCategoryId,
            search = currentSearch,
            sortBy = currentSortBy,
            sortDir = currentSortDir,
        )
    }

    private fun loadCategories() {
        viewModelScope.launch {
            _categories.value = productRepository.getCategories()
        }
    }
}
