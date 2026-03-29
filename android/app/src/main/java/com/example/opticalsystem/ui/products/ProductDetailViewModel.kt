package com.example.opticalsystem.ui.products

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.local.CartItem
import com.example.opticalsystem.data.local.CartManager
import com.example.opticalsystem.data.model.Product
import com.example.opticalsystem.data.repository.ProductRepository
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ProductDetailViewModel @Inject constructor(
    private val productRepository: ProductRepository,
    private val cartManager: CartManager,
) : ViewModel() {

    private val _product = MutableLiveData<Resource<Product>>()
    val product: LiveData<Resource<Product>> = _product

    private val _cartMessage = MutableLiveData<String>()
    val cartMessage: LiveData<String> = _cartMessage

    fun loadProduct(id: Int) {
        _product.value = Resource.Loading
        viewModelScope.launch {
            _product.value = productRepository.getProduct(id)
        }
    }

    fun addToCart(product: Product, quantity: Int) {
        val q = quantity.coerceIn(1, 99)
        viewModelScope.launch {
            cartManager.addToCart(
                CartItem(
                    productId = product.id,
                    productName = product.name,
                    productBrand = product.brand,
                    productPrice = product.price,
                    productImageUrl = product.images?.firstOrNull()?.imageUrl,
                    quantity = q,
                ),
            )
            _cartMessage.value = if (q == 1) {
                "${product.name} added to cart"
            } else {
                "$q × ${product.name} added to cart"
            }
        }
    }
}
