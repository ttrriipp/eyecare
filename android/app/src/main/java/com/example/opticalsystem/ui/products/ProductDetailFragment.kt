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
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Product
import com.example.opticalsystem.data.model.ProductImage
import com.example.opticalsystem.databinding.FragmentProductDetailBinding
import com.example.opticalsystem.util.Resource
import com.google.android.material.snackbar.Snackbar
import dagger.hilt.android.AndroidEntryPoint
import android.widget.ImageView
import android.widget.LinearLayout
import androidx.core.content.ContextCompat

@AndroidEntryPoint
class ProductDetailFragment : Fragment() {

    private var _binding: FragmentProductDetailBinding? = null
    private val binding get() = _binding!!

    private val viewModel: ProductDetailViewModel by viewModels()
    private var currentProduct: Product? = null

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

        binding.btnWishlist.setOnClickListener {
            Toast.makeText(requireContext(), "Saved to wishlist", Toast.LENGTH_SHORT).show()
        }

        binding.btnAddToCart.setOnClickListener {
            currentProduct?.let { viewModel.addToCart(it) }
        }

        binding.btnOrderNow.setOnClickListener {
            Toast.makeText(requireContext(), getString(R.string.coming_soon), Toast.LENGTH_SHORT).show()
        }

        viewModel.loadProduct(productId)

        viewModel.product.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Loading -> showLoading(true)
                is Resource.Success -> {
                    showLoading(false)
                    currentProduct = result.data
                    bindProduct(result.data)
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
    }

    private fun showLoading(loading: Boolean) {
        binding.progressBar.isVisible = loading
        binding.scrollContent.isVisible = !loading
        binding.btnAddToCart.isEnabled = !loading
        binding.btnOrderNow.isEnabled = !loading
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
}
