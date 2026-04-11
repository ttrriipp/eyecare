package com.example.opticalsystem.ui.products

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.local.CartItem
import com.example.opticalsystem.data.local.CartManager
import com.example.opticalsystem.data.local.WishlistManager
import com.example.opticalsystem.data.model.Feedback
import com.example.opticalsystem.data.model.FeedbackListResponse
import com.example.opticalsystem.data.repository.FeedbackRepository
import com.example.opticalsystem.data.model.Product
import com.example.opticalsystem.data.model.ProductVariant
import com.example.opticalsystem.data.model.displayLabel
import com.example.opticalsystem.data.model.displayUnitPrice
import com.example.opticalsystem.data.model.selectableVariants
import com.example.opticalsystem.data.repository.ProductRepository
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ProductDetailViewModel @Inject constructor(
    private val productRepository: ProductRepository,
    private val feedbackRepository: FeedbackRepository,
    private val cartManager: CartManager,
    private val wishlistManager: WishlistManager,
) : ViewModel() {
    val cartItemCount: StateFlow<Int> = cartManager.cartItems
        .map { items -> items.sumOf { it.quantity } }
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5000), 0)

    private val _product = MutableLiveData<Resource<Product>>()
    val product: LiveData<Resource<Product>> = _product

    private val _cartMessage = MutableLiveData<String>()
    val cartMessage: LiveData<String> = _cartMessage

    private val _feedbacks = MutableLiveData<Resource<FeedbackListResponse>>()
    val feedbacks: LiveData<Resource<FeedbackListResponse>> = _feedbacks

    private val _submitFeedback = MutableLiveData<Resource<Feedback>>()
    val submitFeedback: LiveData<Resource<Feedback>> = _submitFeedback

    private val _deleteReview = MutableLiveData<Resource<Unit>>()
    val deleteReview: LiveData<Resource<Unit>> = _deleteReview

    private val _myFeedback = MutableLiveData<Feedback?>()
    val myFeedback: LiveData<Feedback?> = _myFeedback

    private val _canReview = MutableLiveData(false)
    val canReview: LiveData<Boolean> = _canReview

    private val _isInWishlist = MutableLiveData<Boolean>()
    val isInWishlist: LiveData<Boolean> = _isInWishlist

    private var currentProductId: Int? = null

    init {
        viewModelScope.launch {
            wishlistManager.wishlistIds.collect { ids ->
                val id = currentProductId
                _isInWishlist.postValue(id != null && ids.contains(id))
            }
        }
    }

    fun loadProduct(id: Int) {
        _product.value = Resource.Loading
        currentProductId = id
        viewModelScope.launch {
            _product.value = productRepository.getProduct(id)
        }
    }

    fun loadFeedbacks(productId: Int, perPage: Int = 15) {
        _feedbacks.value = Resource.Loading
        viewModelScope.launch {
            when (val result = feedbackRepository.getFeedbacks(
                productId = productId,
                page = 1,
                perPage = perPage,
            )) {
                is Resource.Success -> {
                    _feedbacks.value = result
                    _canReview.value = result.data.canReview == true
                    _myFeedback.value = result.data.myFeedback
                }
                else -> {
                    _feedbacks.value = result
                    _canReview.value = false
                    _myFeedback.value = null
                }
            }
        }
    }

    private fun submitFeedback(productId: Int, rating: Int, comment: String?) {
        _submitFeedback.value = Resource.Loading
        viewModelScope.launch {
            _submitFeedback.value = feedbackRepository.submitFeedback(
                productId = productId,
                rating = rating,
                comment = comment,
            )
        }
    }

    private fun updateExistingFeedback(feedbackId: Int, rating: Int, comment: String?) {
        _submitFeedback.value = Resource.Loading
        viewModelScope.launch {
            _submitFeedback.value = feedbackRepository.updateFeedback(
                feedbackId = feedbackId,
                rating = rating,
                comment = comment,
            )
        }
    }

    fun saveReview(productId: Int, rating: Int, comment: String?) {
        if (_canReview.value != true) {
            _submitFeedback.value = Resource.Error("Only customers with completed orders can write a review for this product.")
            return
        }

        val existing = _myFeedback.value
        if (existing != null) {
            updateExistingFeedback(
                feedbackId = existing.id,
                rating = rating,
                comment = comment,
            )
        } else {
            submitFeedback(
                productId = productId,
                rating = rating,
                comment = comment,
            )
        }
    }

    fun deleteReview(feedbackId: Int, productId: Int) {
        _deleteReview.value = Resource.Loading
        viewModelScope.launch {
            when (val result = feedbackRepository.deleteFeedback(feedbackId)) {
                is Resource.Success -> {
                    _deleteReview.value = result
                    loadFeedbacks(productId)
                    loadProduct(productId)
                }
                else -> {
                    _deleteReview.value = result
                }
            }
        }
    }

    fun addToCart(product: Product, quantity: Int, selectedVariant: ProductVariant? = null) {
        val q = quantity.coerceIn(1, 99)
        val variant = selectedVariant ?: product.defaultVariant ?: product.selectableVariants().firstOrNull()
        if (variant == null) {
            _cartMessage.value = "No selectable variant available for this product"
            return
        }
        val price = variant.displayUnitPrice(product)
        val imageUrl = variant.images?.firstOrNull()?.imageUrl ?: product.images?.firstOrNull()?.imageUrl
        viewModelScope.launch {
            cartManager.addToCart(
                CartItem(
                    productId = product.id,
                    productVariantId = variant.id,
                    productName = product.name,
                    productBrand = product.brand,
                    variantLabel = variant.displayLabel(),
                    productPrice = price,
                    productImageUrl = imageUrl,
                    quantity = q,
                ),
            )
            _cartMessage.value = if (q == 1) {
                "${product.name} added to order"
            } else {
                "$q × ${product.name} added to order"
            }
        }
    }

    fun toggleWishlist(product: Product) {
        viewModelScope.launch {
            wishlistManager.toggle(product.id)
        }
    }
}
