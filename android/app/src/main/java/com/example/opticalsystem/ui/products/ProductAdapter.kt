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
import com.example.opticalsystem.data.model.hasArTryOn
import com.example.opticalsystem.databinding.ItemProductBinding
import com.example.opticalsystem.databinding.ItemProductRowBinding
import com.example.opticalsystem.databinding.ItemProductSectionHeaderBinding

class ProductAdapter(
    private val onItemClick: (Product) -> Unit,
) : ListAdapter<ProductAdapter.RowModel, RecyclerView.ViewHolder>(RowDiffCallback()) {

    sealed interface RowModel {
        data class Header(val title: String) : RowModel
        data class GridProduct(val product: Product) : RowModel
        data class ListProduct(val product: Product) : RowModel
    }

    companion object {
        private const val VIEW_TYPE_HEADER = 0
        private const val VIEW_TYPE_GRID = 1
        private const val VIEW_TYPE_LIST = 2
    }

    fun submitProducts(products: List<Product>) {
        submitList(buildRows(products))
    }

    fun isFullSpan(position: Int): Boolean {
        return when (getItem(position)) {
            is RowModel.GridProduct -> false
            is RowModel.Header, is RowModel.ListProduct -> true
        }
    }

    override fun getItemViewType(position: Int): Int {
        return when (getItem(position)) {
            is RowModel.Header -> VIEW_TYPE_HEADER
            is RowModel.GridProduct -> VIEW_TYPE_GRID
            is RowModel.ListProduct -> VIEW_TYPE_LIST
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): RecyclerView.ViewHolder {
        val inflater = LayoutInflater.from(parent.context)
        return when (viewType) {
            VIEW_TYPE_HEADER -> HeaderViewHolder(
                ItemProductSectionHeaderBinding.inflate(inflater, parent, false),
            )
            VIEW_TYPE_LIST -> ListProductViewHolder(
                ItemProductRowBinding.inflate(inflater, parent, false),
            )
            else -> GridProductViewHolder(
                ItemProductBinding.inflate(inflater, parent, false),
            )
        }
    }

    override fun onBindViewHolder(holder: RecyclerView.ViewHolder, position: Int) {
        when (val item = getItem(position)) {
            is RowModel.Header -> (holder as HeaderViewHolder).bind(item.title)
            is RowModel.GridProduct -> (holder as GridProductViewHolder).bind(item.product, onItemClick)
            is RowModel.ListProduct -> (holder as ListProductViewHolder).bind(item.product, onItemClick)
        }
    }

    class GridProductViewHolder(
        private val binding: ItemProductBinding,
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(product: Product, onItemClick: (Product) -> Unit) {
            binding.root.setOnClickListener { onItemClick(product) }

            binding.tvProductName.text = product.name
            binding.tvProductBrand.text = product.brand ?: ""
            binding.tvProductBrand.isVisible = !product.brand.isNullOrEmpty()
            binding.tvProductPrice.text = formatDisplayPrice(binding.root.context, product)

            // AR badge
            binding.tvArBadge.isVisible = product.hasArTryOn()

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

    }

    class ListProductViewHolder(
        private val binding: ItemProductRowBinding,
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(product: Product, onItemClick: (Product) -> Unit) {
            binding.root.setOnClickListener { onItemClick(product) }
            binding.tvProductName.text = product.name
            binding.tvProductBrand.text = product.brand ?: ""
            binding.tvProductBrand.isVisible = !product.brand.isNullOrBlank()
            binding.tvProductPrice.text = formatDisplayPrice(binding.root.context, product)

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
    }

    class HeaderViewHolder(
        private val binding: ItemProductSectionHeaderBinding,
    ) : RecyclerView.ViewHolder(binding.root) {
        fun bind(title: String) {
            binding.tvSectionTitle.text = title
        }
    }

    class RowDiffCallback : DiffUtil.ItemCallback<RowModel>() {
        override fun areItemsTheSame(oldItem: RowModel, newItem: RowModel): Boolean {
            return when {
                oldItem is RowModel.Header && newItem is RowModel.Header -> oldItem.title == newItem.title
                oldItem is RowModel.GridProduct && newItem is RowModel.GridProduct -> oldItem.product.id == newItem.product.id
                oldItem is RowModel.ListProduct && newItem is RowModel.ListProduct -> oldItem.product.id == newItem.product.id
                else -> false
            }
        }

        override fun areContentsTheSame(oldItem: RowModel, newItem: RowModel): Boolean = oldItem == newItem
    }

    private fun buildRows(products: List<Product>): List<RowModel> {
        if (products.isEmpty()) return emptyList()

        val featured = products.filter(::isVisualProduct)
        val consumables = products.filterNot(::isVisualProduct)
        val rows = mutableListOf<RowModel>()

        if (featured.isNotEmpty()) {
            rows += featured.map { RowModel.GridProduct(it) }
        }

        consumables
            .groupBy { sectionNameFor(it) }
            .forEach { (section, sectionProducts) ->
                rows += RowModel.Header(section)
                rows += sectionProducts.map { RowModel.ListProduct(it) }
            }

        return rows
    }

    private fun isVisualProduct(product: Product): Boolean {
        val slug = product.category?.slug?.lowercase().orEmpty()
        return slug.contains("frame") || slug.contains("sunglass")
    }

    private fun sectionNameFor(product: Product): String {
        val name = product.category?.name?.trim().orEmpty()
        if (name.isNotEmpty()) return name
        return "Products"
    }
}

/** File-level helpers so nested ViewHolders can call them (outer private members are not in scope). */
private fun formatDisplayPrice(context: Context, product: Product): String {
    val base = "₱${formatPrice(product.price)}"
    val variantCount = product.variants?.size ?: 0
    return if (variantCount > 1) {
        context.getString(R.string.price_from_format, base)
    } else {
        base
    }
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
