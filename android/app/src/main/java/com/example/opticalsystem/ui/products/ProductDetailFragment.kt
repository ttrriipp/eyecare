package com.example.opticalsystem.ui.products

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.viewpager2.widget.ViewPager2
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Product
import com.example.opticalsystem.data.model.ProductImage
import com.example.opticalsystem.databinding.FragmentProductDetailBinding
import com.example.opticalsystem.ui.cart.CartViewModel
import com.example.opticalsystem.util.Resource
import com.google.android.material.snackbar.Snackbar
import dagger.hilt.android.AndroidEntryPoint
import android.widget.ImageView
import android.widget.LinearLayout
import androidx.core.content.ContextCompat
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch
import kotlin.math.roundToInt

@AndroidEntryPoint
class ProductDetailFragment : Fragment() {

    private var _binding: FragmentProductDetailBinding? = null
    private val binding get() = _binding!!

    private val viewModel: ProductDetailViewModel by viewModels()
    private val cartViewModel: CartViewModel by viewModels()
    private var currentProduct: Product? = null

    private var quantity: Int = 1
    private var loadedProductId: Int = -1
    private lateinit var feedbackAdapter: FeedbackAdapter
    private var isUpdatingReview: Boolean = false

    override fun onSaveInstanceState(outState: Bundle) {
        super.onSaveInstanceState(outState)
        outState.putInt(STATE_PRODUCT_ID, loadedProductId)
        outState.putInt(STATE_QTY, quantity)
    }

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentProductDetailBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val productId = arguments?.getInt("productId", -1) ?: -1
        if (productId == -1) {
            findNavController().navigateUp()
            return
        }

        binding.btnBack.setOnClickListener { findNavController().navigateUp() }

        binding.btnCart.setOnClickListener {
            findNavController().navigate(R.id.action_productDetail_to_cart)
        }

        savedInstanceState?.let {
            loadedProductId = it.getInt(STATE_PRODUCT_ID, -1)
            quantity = it.getInt(STATE_QTY, 1).coerceIn(1, MAX_QTY)
        }

        binding.btnDecreaseQty.setOnClickListener {
            if (quantity > 1) {
                quantity--
                updateQuantityUi()
            }
        }
        binding.btnIncreaseQty.setOnClickListener {
            if (quantity < MAX_QTY) {
                quantity++
                updateQuantityUi()
            }
        }

        binding.btnAddToCart.setOnClickListener {
            currentProduct?.let { viewModel.addToCart(it, quantity) }
        }

        updateQuantityUi()

        observeCartBadge()
        viewModel.loadProduct(productId)
        observeWishlist(productId)
        setupFeedbackSection(productId)
        viewModel.loadCurrentUser()
        viewModel.loadFeedbacks(productId)

        viewModel.product.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Loading -> showLoading(true)
                is Resource.Success -> {
                    showLoading(false)
                    val product = result.data
                    val previousId = loadedProductId
                    loadedProductId = product.id
                    if (previousId != -1 && previousId != product.id) {
                        quantity = 1
                    }
                    currentProduct = product
                    bindProduct(product)
                    updateQuantityUi()
                }
                is Resource.Error -> {
                    showLoading(false)
                    Toast.makeText(requireContext(), result.message, Toast.LENGTH_LONG).show()
                }
            }
        }

        viewModel.cartMessage.observe(viewLifecycleOwner) { message ->
            Snackbar.make(binding.root, message, Snackbar.LENGTH_SHORT).show()
        }

        viewModel.feedbacks.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Loading -> showFeedbackLoading(true)
                is Resource.Success -> {
                    showFeedbackLoading(false)

                    val payload = result.data
                    val average = payload.averageRating
                    val total = payload.meta?.total ?: payload.data.size

                    updateRatingSummary(averageRating = average, totalReviews = total)

                    binding.tvNoReviews.isVisible = payload.data.isEmpty()
                    binding.rvFeedbacks.isVisible = payload.data.isNotEmpty()
                    feedbackAdapter.submitList(payload.data)
                }
                is Resource.Error -> {
                    showFeedbackLoading(false)
                    binding.tvNoReviews.isVisible = true
                    binding.rvFeedbacks.isVisible = false
                    Toast.makeText(requireContext(), result.message, Toast.LENGTH_LONG).show()
                }
            }
        }

        viewModel.myFeedback.observe(viewLifecycleOwner) { feedback ->
            if (feedback != null) {
                // User already reviewed this product; let them update it.
                if (binding.ratingBarWrite.rating != feedback.rating.toFloat()) {
                    binding.ratingBarWrite.rating = feedback.rating.toFloat()
                }
                binding.etReviewComment.setText(feedback.comment.orEmpty())

                binding.btnSubmitReview.text = getString(R.string.update_review)
                binding.btnSubmitReview.isEnabled = feedback.rating >= 1
            } else {
                binding.ratingBarWrite.rating = 0f
                binding.etReviewComment.setText("")
                binding.btnSubmitReview.text = getString(R.string.submit_review)
                binding.btnSubmitReview.isEnabled = false
            }
        }

        viewModel.submitFeedback.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Loading -> {
                    showSubmitFeedbackLoading(true)
                }
                is Resource.Success -> {
                    showSubmitFeedbackLoading(false)
                    val msgRes = if (isUpdatingReview) {
                        R.string.review_update_success
                    } else {
                        R.string.review_submit_success
                    }
                    Snackbar.make(binding.root, getString(msgRes), Snackbar.LENGTH_SHORT).show()
                    isUpdatingReview = false
                    viewModel.loadFeedbacks(productId)
                    viewModel.loadProduct(productId) // Refresh product rating summary from feedback summary.
                }
                is Resource.Error -> {
                    showSubmitFeedbackLoading(false)
                    Toast.makeText(requireContext(), result.message, Toast.LENGTH_LONG).show()
                }
            }
        }
    }

    private fun observeWishlist(productId: Int) {
        viewModel.isInWishlist.observe(viewLifecycleOwner) { inWishlist ->
            val iconRes = if (inWishlist) {
                R.drawable.ic_heart_filled_24      // filled
            } else {
                R.drawable.ic_heart_24              // default
            }
            binding.btnWishlist.setImageResource(iconRes)
            binding.btnWishlist.contentDescription = if (inWishlist) {
                getString(R.string.remove_from_wishlist)
            } else {
                getString(R.string.save_to_wishlist)
            }

            binding.btnWishlist.setOnClickListener {
                currentProduct?.let { product ->
                    viewModel.toggleWishlist(product)
                    val messageRes = if (inWishlist) {
                        R.string.wishlist_removed
                    } else {
                        R.string.wishlist_added
                    }
                    Snackbar.make(binding.root, getString(messageRes), Snackbar.LENGTH_SHORT).show()
                }
            }
        }
    }

    private fun observeCartBadge() {
        viewLifecycleOwner.lifecycleScope.launch {
            cartViewModel.itemCount.collectLatest { count ->
                binding.cartBadge.isVisible = count > 0
                binding.tvCartBadge.text = if (count > 99) "99+" else count.toString()
            }
        }
    }

    private fun showLoading(loading: Boolean) {
        binding.progressBar.isVisible = loading
        binding.scrollContent.isVisible = !loading
        binding.btnAddToCart.isEnabled = !loading
        binding.btnDecreaseQty.isEnabled = !loading && quantity > 1
        binding.btnIncreaseQty.isEnabled = !loading && quantity < MAX_QTY
    }

    private fun setupFeedbackSection(productId: Int) {
        feedbackAdapter = FeedbackAdapter()
        binding.rvFeedbacks.apply {
            adapter = feedbackAdapter
            layoutManager = LinearLayoutManager(requireContext())
            isNestedScrollingEnabled = false
        }

        binding.btnSubmitReview.isEnabled = false
        binding.ratingBarWrite.setOnRatingBarChangeListener { _, rating, _ ->
            binding.btnSubmitReview.isEnabled = rating >= 1f
        }

        binding.btnSubmitReview.setOnClickListener {
            val rating = binding.ratingBarWrite.rating.roundToInt()
            val comment = binding.etReviewComment.text?.toString()?.trim().orEmpty()
            val commentOrNull = comment.takeIf { it.isNotBlank() }

            if (rating < 1) {
                Toast.makeText(requireContext(), getString(R.string.review_pick_rating_first), Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            isUpdatingReview = viewModel.myFeedback.value != null
            viewModel.saveReview(
                productId = productId,
                rating = rating,
                comment = commentOrNull,
            )
        }
    }

    private fun showFeedbackLoading(loading: Boolean) {
        binding.progressFeedbackList.isVisible = loading
        binding.rvFeedbacks.isVisible = !loading && binding.rvFeedbacks.adapter?.itemCount.orZero() > 0
        binding.tvNoReviews.isVisible = !loading && (binding.rvFeedbacks.adapter?.itemCount.orZero() == 0)
    }

    private fun showSubmitFeedbackLoading(loading: Boolean) {
        binding.progressSubmitReview.isVisible = loading
        binding.btnSubmitReview.isEnabled = !loading && binding.ratingBarWrite.rating >= 1f
    }

    private fun updateRatingSummary(averageRating: Float?, totalReviews: Int?) {
        if (averageRating != null && totalReviews != null && totalReviews > 0) {
            binding.ratingBar.rating = averageRating
            binding.tvRatingValue.text = "$averageRating (${totalReviews} reviews)"
            binding.layoutRating.isVisible = true
        } else {
            binding.layoutRating.isVisible = false
        }
    }

    private fun updateQuantityUi() {
        binding.tvProductQty.text = quantity.toString()
        val loading = binding.progressBar.isVisible
        binding.btnDecreaseQty.isEnabled = !loading && quantity > 1
        binding.btnIncreaseQty.isEnabled = !loading && quantity < MAX_QTY
    }

    private fun bindProduct(product: Product) {
        binding.apply {
            // Category + Brand + Name
            tvCategory.text = product.category?.name ?: ""
            tvCategory.isVisible = product.category != null
            tvBrand.text = product.brand ?: ""
            tvBrand.isVisible = product.brand != null
            tvProductName.text = product.name
            tvPrice.text = "₱${formatPrice(product.price)}"

            // Description
            if (!product.description.isNullOrBlank()) {
                tvDescription.text = product.description
                tvDescription.isVisible = true
            }

            // AR Try-On badge
            tvArBadge.isVisible = product.arModelUrl != null

            // Rating (visible only when Phase F data is available)
            val rating = product.averageRating
            val count = product.reviewsCount
            if (rating != null && count != null) {
                ratingBar.rating = rating
                tvRatingValue.text = "$rating (${count} reviews)"
                layoutRating.isVisible = true
            }

            // Image carousel
            setupImageCarousel(product.images ?: emptyList())

            // Specifications
            setupSpecifications(product)
        }
    }

    private fun setupImageCarousel(images: List<ProductImage>) {
        val effectiveImages = images.ifEmpty {
            listOf(ProductImage(id = 0, imageUrl = "", sortOrder = 0, createdAt = ""))
        }

        val adapter = ProductImageAdapter(effectiveImages)
        binding.viewPagerImages.adapter = adapter
        setupDotIndicators(effectiveImages.size)

        binding.viewPagerImages.registerOnPageChangeCallback(object : ViewPager2.OnPageChangeCallback() {
            override fun onPageSelected(position: Int) {
                updateDots(position, effectiveImages.size)
            }
        })
    }

    private fun setupDotIndicators(count: Int) {
        binding.dotsContainer.removeAllViews()
        if (count <= 1) return

        repeat(count) { index ->
            val dot = ImageView(requireContext()).apply {
                val dotSize = resources.getDimensionPixelSize(R.dimen.dot_size)
                layoutParams = LinearLayout.LayoutParams(dotSize, dotSize).also { params ->
                    if (index > 0) params.marginStart = resources.getDimensionPixelSize(R.dimen.dot_margin)
                }
                setImageDrawable(
                    ContextCompat.getDrawable(
                        requireContext(),
                        if (index == 0) R.drawable.dot_active else R.drawable.dot_inactive,
                    ),
                )
            }
            binding.dotsContainer.addView(dot)
        }
    }

    private fun updateDots(selectedIndex: Int, count: Int) {
        if (count <= 1) return
        for (i in 0 until binding.dotsContainer.childCount) {
            val dot = binding.dotsContainer.getChildAt(i) as? ImageView ?: continue
            dot.setImageDrawable(
                ContextCompat.getDrawable(
                    requireContext(),
                    if (i == selectedIndex) R.drawable.dot_active else R.drawable.dot_inactive,
                ),
            )
        }
    }

    private fun setupSpecifications(product: Product) {
        binding.apply {
            if (!product.frameMaterial.isNullOrBlank()) {
                tvSpecFrame.text = product.frameMaterial
                rowFrame.isVisible = true
                dividerFrame.isVisible = !product.lensType.isNullOrBlank()
            }
            if (!product.lensType.isNullOrBlank()) {
                tvSpecLens.text = product.lensType
                rowLens.isVisible = true
                dividerLens.isVisible = true
            }
            tvSpecSku.text = product.sku
        }
    }

    private fun formatPrice(price: String): String {
        return try {
            val number = price.toDouble()
            String.format("%,.0f", number)
        } catch (e: NumberFormatException) {
            price
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }

    companion object {
        private const val MAX_QTY = 99
        private const val STATE_QTY = "product_detail_qty"
        private const val STATE_PRODUCT_ID = "product_detail_product_id"
    }
}

private fun Int?.orZero(): Int = this ?: 0

