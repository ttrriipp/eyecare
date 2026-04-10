package com.example.opticalsystem.ui.products

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import androidx.viewpager2.widget.ViewPager2
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Product
import com.example.opticalsystem.data.model.ProductCategory
import com.example.opticalsystem.data.model.ProductImage
import com.example.opticalsystem.data.model.ProductVariant
import com.example.opticalsystem.data.model.hasArTryOn
import com.example.opticalsystem.data.model.displayUnitPrice
import com.example.opticalsystem.data.model.selectableVariants
import com.example.opticalsystem.databinding.FragmentProductDetailBinding
import com.example.opticalsystem.databinding.ItemSpecRowBinding
import com.example.opticalsystem.util.Resource
import com.google.android.material.snackbar.Snackbar
import dagger.hilt.android.AndroidEntryPoint
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.TextView
import androidx.core.content.ContextCompat
import android.graphics.Typeface
import android.graphics.drawable.GradientDrawable
import android.util.TypedValue
import kotlin.math.roundToInt

@AndroidEntryPoint
class ProductDetailFragment : Fragment() {

    private var _binding: FragmentProductDetailBinding? = null
    private val binding get() = _binding!!

    private val viewModel: ProductDetailViewModel by viewModels()
    private var currentProduct: Product? = null
    private var selectedVariant: ProductVariant? = null

    private var loadedProductId: Int = -1
    private lateinit var feedbackAdapter: FeedbackAdapter
    private var isUpdatingReview: Boolean = false

    override fun onSaveInstanceState(outState: Bundle) {
        super.onSaveInstanceState(outState)
        outState.putInt(STATE_PRODUCT_ID, loadedProductId)
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
        binding.btnCart.setOnClickListener { findNavController().navigate(R.id.action_productDetail_to_cart) }

        savedInstanceState?.let {
            loadedProductId = it.getInt(STATE_PRODUCT_ID, -1)
        }

        binding.btnAddToCart.setOnClickListener {
            val product = currentProduct ?: return@setOnClickListener
            if (requiresColorSelection(product) && selectedVariant == null) {
                Snackbar.make(binding.root, getString(R.string.select_in_stock_color_first), Snackbar.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            viewModel.addToCart(product, 1, selectedVariant)
        }

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
                    loadedProductId = product.id
                    currentProduct = product
                    bindProduct(product)
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

    private fun showLoading(loading: Boolean) {
        binding.progressBar.isVisible = loading
        binding.scrollContent.isVisible = !loading
        binding.btnAddToCart.isEnabled = !loading
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

    private fun bindProduct(product: Product) {
        binding.apply {
            // Category + Brand + Name
            tvCategory.text = product.category?.name ?: ""
            tvCategory.isVisible = product.category != null
            tvBrand.text = product.brand ?: ""
            tvBrand.isVisible = product.brand != null
            tvProductName.text = product.name
            val variants = product.selectableVariants()
            val hasColorSwatches = hasColorVariants(product, variants)
            selectedVariant = if (hasColorSwatches) null else (product.defaultVariant ?: variants.firstOrNull())

            if (hasColorSwatches) {
                tvPrice.text = getString(R.string.select_color_to_see_price)
            } else {
                val basePrice = selectedVariant?.displayUnitPrice(product) ?: product.price
                tvPrice.text = "₱${formatPrice(basePrice)}"
            }

            // Description
            if (!product.description.isNullOrBlank()) {
                tvDescription.text = product.description
                tvDescription.isVisible = true
            }

            // AR Try-On badge
            tvArBadge.isVisible = product.hasArTryOn()

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

            setupColorSwatches(product, variants, hasColorSwatches)
            setupSpecifications(product, selectedVariant)
            updateAddToOrderEnabled(product)
        }
    }

    private fun setupColorSwatches(product: Product, variants: List<ProductVariant>, show: Boolean) {
        binding.colorSwatchContainer.removeAllViews()
        binding.colorSwatchScroll.isVisible = show
        if (!show) return

        variants.forEachIndexed { index, variant ->
            val colorName = variant.color?.trim().takeUnless { it.isNullOrEmpty() } ?: return@forEachIndexed
            val inStock = (variant.stockQuantity ?: 0) > 0
            val chip = buildSwatchChip(colorName, inStock, selected = false)
            chip.setOnClickListener {
                if (!inStock) return@setOnClickListener
                selectedVariant = variant
                val selectedPrice = variant.displayUnitPrice(product)
                binding.tvPrice.text = "₱${formatPrice(selectedPrice)}"
                updateSwatchSelection()
                setupSpecifications(product, selectedVariant)
                updateAddToOrderEnabled(product)
            }
            val lp = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT,
                LinearLayout.LayoutParams.WRAP_CONTENT,
            )
            if (index > 0) {
                lp.marginStart = dp(8)
            }
            binding.colorSwatchContainer.addView(chip, lp)
        }
    }

    private fun updateSwatchSelection() {
        val selectedColor = selectedVariant?.color?.trim().orEmpty()
        for (i in 0 until binding.colorSwatchContainer.childCount) {
            val v = binding.colorSwatchContainer.getChildAt(i) as? TextView ?: continue
            val isSelected = v.tag == selectedColor
            v.typeface = if (isSelected) Typeface.DEFAULT_BOLD else Typeface.DEFAULT
            val bg = v.background as? GradientDrawable
            bg?.setStroke(dp(1), ContextCompat.getColor(requireContext(), if (isSelected) R.color.primary else R.color.divider))
        }
    }

    private fun buildSwatchChip(colorName: String, inStock: Boolean, selected: Boolean): TextView {
        return TextView(requireContext()).apply {
            tag = colorName
            text = colorName
            setTextSize(TypedValue.COMPLEX_UNIT_SP, 12f)
            setPadding(dp(12), dp(8), dp(12), dp(8))
            maxLines = 1
            background = GradientDrawable().apply {
                cornerRadius = dp(16).toFloat()
                setColor(ContextCompat.getColor(context, R.color.surface))
                setStroke(dp(1), ContextCompat.getColor(context, if (selected) R.color.primary else R.color.divider))
            }
            if (!inStock) {
                paintFlags = paintFlags or android.graphics.Paint.STRIKE_THRU_TEXT_FLAG
                alpha = 0.45f
                isClickable = false
            }
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

    private fun setupSpecifications(product: Product, chosenVariant: ProductVariant?) {
        val container = binding.specRowsContainer
        container.removeAllViews()

        val variant: ProductVariant? = chosenVariant ?: product.defaultVariant ?: product.selectableVariants().firstOrNull()
        val category = product.category

        if (category != null && category.specFlagsKnown()) {
            if (category.hasColor == true) appendSpecRow(container, R.string.spec_color, variant?.color)
            if (category.hasFrameSize == true) appendSpecRow(container, R.string.spec_frame_size, variant?.frameSize)
            if (category.hasMaterial == true) appendSpecRow(container, R.string.spec_material, variant?.material)
            if (category.hasLensType == true) appendSpecRow(container, R.string.spec_lens_type, variant?.lensType)
            if (category.hasPowerField == true) {
                val powerValue = variant?.power ?: variant?.baseCurve
                val powerLabel = if (!variant?.power.isNullOrBlank()) R.string.spec_power else R.string.spec_base_curve
                appendSpecRow(container, powerLabel, powerValue)
            }
            if (category.hasDuration == true) {
                val durationValue = variant?.duration ?: variant?.diameter
                val durationLabel = if (!variant?.duration.isNullOrBlank()) R.string.spec_duration else R.string.spec_diameter
                appendSpecRow(container, durationLabel, durationValue)
            }
        } else {
            fun appendIfValue(labelRes: Int, value: String?) {
                if (!value.isNullOrBlank()) appendSpecRow(container, labelRes, value)
            }
            appendIfValue(R.string.spec_color, variant?.color)
            appendIfValue(R.string.spec_frame_size, variant?.frameSize)
            appendIfValue(R.string.spec_material, variant?.material)
            appendIfValue(R.string.spec_lens_type, variant?.lensType)
            if (!variant?.power.isNullOrBlank()) {
                appendIfValue(R.string.spec_power, variant?.power)
            } else {
                appendIfValue(R.string.spec_base_curve, variant?.baseCurve)
            }
            if (!variant?.duration.isNullOrBlank()) {
                appendIfValue(R.string.spec_duration, variant?.duration)
            } else {
                appendIfValue(R.string.spec_diameter, variant?.diameter)
            }
        }

        binding.cardSpecifications.isVisible = container.childCount > 0
    }

    private fun appendSpecRow(container: LinearLayout, labelRes: Int, value: String?) {
        val display = value?.trim()?.takeUnless { it.isEmpty() } ?: return
        if (container.childCount > 0) {
            container.addView(createSpecDivider())
        }
        val rowBinding = ItemSpecRowBinding.inflate(layoutInflater, container, false)
        rowBinding.tvSpecRowLabel.setText(labelRes)
        rowBinding.tvSpecRowValue.text = display
        container.addView(rowBinding.root)
    }

    private fun createSpecDivider(): View {
        val h = (resources.displayMetrics.density).toInt().coerceAtLeast(1)
        return View(requireContext()).apply {
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                h,
            ).apply {
                topMargin = (4 * resources.displayMetrics.density).toInt()
                bottomMargin = (4 * resources.displayMetrics.density).toInt()
            }
            setBackgroundColor(ContextCompat.getColor(requireContext(), R.color.divider))
        }
    }

    /** True when the API sent at least one category spec flag (even if false). */
    private fun ProductCategory.specFlagsKnown(): Boolean =
        listOf(hasColor, hasFrameSize, hasMaterial, hasLensType, hasPowerField, hasDuration).any { it != null }

    private fun hasColorVariants(product: Product, variants: List<ProductVariant>): Boolean {
        if (product.category?.hasColor != true) return false
        val colors = variants.mapNotNull { it.color?.trim()?.takeIf(String::isNotBlank) }.distinct()
        return colors.isNotEmpty()
    }

    private fun requiresColorSelection(product: Product): Boolean {
        return hasColorVariants(product, product.selectableVariants())
    }

    private fun updateAddToOrderEnabled(product: Product) {
        val needsSelection = requiresColorSelection(product)
        val enabled = if (!needsSelection) true else ((selectedVariant?.stockQuantity ?: 0) > 0)
        binding.btnAddToCart.isEnabled = enabled
        binding.btnAddToCart.alpha = if (enabled) 1f else 0.6f
    }

    private fun dp(value: Int): Int = (value * resources.displayMetrics.density).toInt()

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
        private const val STATE_PRODUCT_ID = "product_detail_product_id"
    }
}

private fun Int?.orZero(): Int = this ?: 0

