package com.example.opticalsystem.ui.products

import android.content.Context
import android.net.Uri
import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.bumptech.glide.signature.ObjectKey
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Product
import com.example.opticalsystem.databinding.ItemProductBinding

class ProductAdapter(
    private val onItemClick: (Product) -> Unit,
) : ListAdapter<Product, ProductAdapter.ProductViewHolder>(ProductDiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ProductViewHolder {
        val binding = ItemProductBinding.inflate(
            LayoutInflater.from(parent.context), parent, false,
        )
        return ProductViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ProductViewHolder, position: Int) {
        holder.bind(getItem(position), onItemClick)
    }

    class ProductViewHolder(
        private val binding: ItemProductBinding,
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(product: Product, onItemClick: (Product) -> Unit) {
            binding.root.setOnClickListener { onItemClick(product) }

            binding.tvProductName.text = product.name
            binding.tvProductBrand.text = product.brand ?: ""
            binding.tvProductBrand.isVisible = !product.brand.isNullOrEmpty()
            binding.tvProductPrice.text = "₱${formatPrice(product.price)}"

            // AR badge
            binding.tvArBadge.isVisible = product.arModelUrl != null

            // Rating (visible once Phase F data arrives)
            val rating = product.averageRating
            val count = product.reviewsCount
            if (rating != null && count != null) {
                binding.ratingBar.rating = rating
                binding.tvReviewCount.text = "($count)"
                binding.layoutStars.isVisible = true
            } else {
                binding.layoutStars.isVisible = false
            }

            // Product image (first image from gallery)
            val rawImageUrl = product.images?.firstOrNull()?.imageUrl
            val imageUrl = buildImageUrl(binding.root.context, rawImageUrl)
            if (imageUrl == null) {
                binding.ivProductImage.setImageResource(R.drawable.bg_product_placeholder)
                return
            }
            val imageCacheKey = "${product.id}_${product.updatedAt}_${rawImageUrl.orEmpty()}"
            Glide.with(binding.ivProductImage.context)
                .load(imageUrl)
                .signature(ObjectKey(imageCacheKey))
                .placeholder(R.drawable.bg_product_placeholder)
                .error(R.drawable.bg_product_placeholder)
                .centerCrop()
                .into(binding.ivProductImage)
        }

        private fun buildImageUrl(context: Context, url: String?): String? {
            val trimmed = url?.trim().orEmpty()
            if (trimmed.isBlank()) return null
            val backendRootUrl = context.getString(R.string.backend_root_url).trimEnd('/')

            // If backend returns a fully-qualified URL but the hostname isn't reachable from the device
            // (common with staging domains like `eyecare.test`), rewrite it to use backendRootUrl.
            if (trimmed.startsWith("http://") || trimmed.startsWith("https://")) {
                return try {
                    val uri = Uri.parse(trimmed)
                    val host = uri.host.orEmpty()
                    val backendHost = Uri.parse(backendRootUrl).host.orEmpty()

                    val shouldRewrite = host.equals("eyecare.test", ignoreCase = true) || (backendHost.isNotBlank() && !host.equals(backendHost, ignoreCase = true))
                    if (!shouldRewrite) return trimmed

                    val rebuilt = backendRootUrl + (uri.encodedPath ?: "")
                    val query = uri.encodedQuery
                    if (!query.isNullOrBlank()) "$rebuilt?$query" else rebuilt
                } catch (_: Exception) {
                    trimmed
                }
            }

            val relative = trimmed.trimStart('/')
            return "$backendRootUrl/$relative"
        }

        private fun formatPrice(price: String): String {
            return try {
                val number = price.toDouble()
                String.format("%,.0f", number)
            } catch (e: NumberFormatException) {
                price
            }
        }
    }

    class ProductDiffCallback : DiffUtil.ItemCallback<Product>() {
        override fun areItemsTheSame(oldItem: Product, newItem: Product): Boolean =
            oldItem.id == newItem.id

        override fun areContentsTheSame(oldItem: Product, newItem: Product): Boolean =
            oldItem == newItem
    }
}
