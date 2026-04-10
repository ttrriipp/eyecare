package com.example.opticalsystem.ui.products

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.opticalsystem.data.local.CartItem
import com.example.opticalsystem.data.local.CartManager
import com.example.opticalsystem.data.local.WishlistManager
import com.example.opticalsystem.data.repository.AuthRepository
import com.example.opticalsystem.data.model.Feedback
import com.example.opticalsystem.data.model.FeedbackListResponse
import com.example.opticalsystem.data.repository.FeedbackRepository
import com.example.opticalsystem.data.model.Product
import com.example.opticalsystem.data.repository.ProductRepository
import com.example.opticalsystem.util.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ProductDetailViewModel @Inject constructor(
    private val productRepository: ProductRepository,
    private val feedbackRepository: FeedbackRepository,
    private val authRepository: AuthRepository,
    private val cartManager: CartManager,
    private val wishlistManager: WishlistManager,
) : ViewModel() {

    private val _product = MutableLiveData<Resource<Product>>()
    val product: LiveData<Resource<Product>> = _product

    private val _cartMessage = MutableLiveData<String>()
    val cartMessage: LiveData<String> = _cartMessage

    private val _feedbacks = MutableLiveData<Resource<FeedbackListResponse>>()
    val feedbacks: LiveData<Resource<FeedbackListResponse>> = _feedbacks

    private val _submitFeedback = MutableLiveData<Resource<Feedback>>()
    val submitFeedback: LiveData<Resource<Feedback>> = _submitFeedback

    private val _myFeedback = MutableLiveData<Feedback?>()
    val myFeedback: LiveData<Feedback?> = _myFeedback

    private val _isInWishlist = MutableLiveData<Boolean>()
    val isInWishlist: LiveData<Boolean> = _isInWishlist

    private var currentUserId: Int? = null
    private var cachedFeedbacks: List<Feedback> = emptyList()
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

    fun loadCurrentUser() {
        viewModelScope.launch {
            when (val result = authRepository.getProfile()) {
                is Resource.Success -> currentUserId = result.data.id
                else -> currentUserId = null
            }
            // If feedbacks already loaded, compute the current user's review.
            updateMyFeedback()
        }
    }

    private fun updateMyFeedback() {
        val uid = currentUserId
        if (uid == null) {
            _myFeedback.value = null
            return
        }
        _myFeedback.value = cachedFeedbacks.firstOrNull { it.userId == uid }
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
                    cachedFeedbacks = result.data.data
                    _feedbacks.value = result
                    updateMyFeedback()
                }
                else -> {
                    _feedbacks.value = result
                    cachedFeedbacks = emptyList()
                    updateMyFeedback()
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
