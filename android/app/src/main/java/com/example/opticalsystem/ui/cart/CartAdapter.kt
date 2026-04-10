package com.example.opticalsystem.ui.cart

import android.content.Context
import android.net.Uri
import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.example.opticalsystem.R
import com.example.opticalsystem.data.local.CartItem
import com.example.opticalsystem.databinding.ItemCartBinding

class CartAdapter(
    private val onIncrease: (CartItem) -> Unit,
    private val onDecrease: (CartItem) -> Unit,
    private val onRemove: (CartItem) -> Unit,
) : ListAdapter<CartItem, CartAdapter.CartViewHolder>(CartDiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): CartViewHolder {
        val binding = ItemCartBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return CartViewHolder(binding)
    }

    override fun onBindViewHolder(holder: CartViewHolder, position: Int) {
        holder.bind(getItem(position), onIncrease, onDecrease, onRemove)
    }

    class CartViewHolder(
        private val binding: ItemCartBinding,
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(
            item: CartItem,
            onIncrease: (CartItem) -> Unit,
            onDecrease: (CartItem) -> Unit,
            onRemove: (CartItem) -> Unit,
        ) {
            binding.tvCartItemName.text = item.productName
            binding.tvCartItemBrand.text = item.productBrand ?: ""
            binding.tvCartItemBrand.isVisible = !item.productBrand.isNullOrBlank()
            binding.tvCartItemVariant.text = item.variantLabel.orEmpty()
            binding.tvCartItemVariant.isVisible = !item.variantLabel.isNullOrBlank()
            binding.tvCartItemPrice.text = "₱${formatPrice(item.productPrice)}"
            binding.tvQuantity.text = item.quantity.toString()
            val unit = item.productPrice.toDoubleOrNull() ?: 0.0
            binding.tvCartItemLineTotal.text = "₱${String.format("%,.0f", unit * item.quantity)}"

            binding.btnIncrease.setOnClickListener { onIncrease(item) }
            binding.btnDecrease.setOnClickListener { onDecrease(item) }
            binding.btnRemove.setOnClickListener { onRemove(item) }

            val imageUrl = buildImageUrl(binding.root.context, item.productImageUrl)
            if (imageUrl == null) {
                binding.ivCartItemImage.setImageResource(R.drawable.bg_product_placeholder)
            } else {
                Glide.with(binding.ivCartItemImage.context)
                    .load(imageUrl)
                    .placeholder(R.drawable.bg_product_placeholder)
                    .error(R.drawable.bg_product_placeholder)
                    .centerCrop()
                    .into(binding.ivCartItemImage)
            }
        }

        private fun buildImageUrl(context: Context, url: String?): String? {
            val trimmed = url?.trim().orEmpty()
            if (trimmed.isBlank()) return null
            val backendRootUrl = context.getString(R.string.backend_root_url).trimEnd('/')

            if (trimmed.startsWith("http://") || trimmed.startsWith("https://")) {
                return try {
                    val uri = Uri.parse(trimmed)
                    val host = uri.host.orEmpty()
                    val backendHost = Uri.parse(backendRootUrl).host.orEmpty()
                    val shouldRewrite = host.equals("eyecare.test", ignoreCase = true) ||
                        (backendHost.isNotBlank() && !host.equals(backendHost, ignoreCase = true))
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

    class CartDiffCallback : DiffUtil.ItemCallback<CartItem>() {
        override fun areItemsTheSame(oldItem: CartItem, newItem: CartItem): Boolean =
            oldItem.productId == newItem.productId && oldItem.productVariantId == newItem.productVariantId

        override fun areContentsTheSame(oldItem: CartItem, newItem: CartItem): Boolean =
            oldItem == newItem
    }
}
