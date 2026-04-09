package com.example.opticalsystem.ui.orders

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
import com.example.opticalsystem.data.model.OrderItem
import com.example.opticalsystem.databinding.ItemOrderItemBinding
import com.example.opticalsystem.util.StatusHelper

class OrderItemAdapter : ListAdapter<OrderItem, OrderItemAdapter.OrderItemViewHolder>(DiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): OrderItemViewHolder {
        val binding = ItemOrderItemBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return OrderItemViewHolder(binding)
    }

    override fun onBindViewHolder(holder: OrderItemViewHolder, position: Int) {
        holder.bind(getItem(position))
    }

    class OrderItemViewHolder(
        private val binding: ItemOrderItemBinding,
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(item: OrderItem) {
            val context = binding.root.context
            val product = item.product

            binding.tvProductName.text = product?.name ?: "Product #${item.productId}"

            val brand = product?.brand
            binding.tvProductBrand.text = brand ?: ""
            binding.tvProductBrand.isVisible = !brand.isNullOrBlank()

            binding.tvQtyPrice.text = "Qty: ${item.quantity}"
            binding.tvSubtotal.text = StatusHelper.formatPrice(item.unitPrice)

            val imageUrl = product?.images?.firstOrNull()?.imageUrl
            val fullUrl = buildImageUrl(context, imageUrl)
            if (fullUrl == null) {
                binding.ivProductImage.setImageResource(R.drawable.bg_product_placeholder)
            } else {
                Glide.with(context)
                    .load(fullUrl)
                    .placeholder(R.drawable.bg_product_placeholder)
                    .error(R.drawable.bg_product_placeholder)
                    .centerCrop()
                    .into(binding.ivProductImage)
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
    }

    class DiffCallback : DiffUtil.ItemCallback<OrderItem>() {
        override fun areItemsTheSame(oldItem: OrderItem, newItem: OrderItem): Boolean =
            oldItem.id == newItem.id

        override fun areContentsTheSame(oldItem: OrderItem, newItem: OrderItem): Boolean =
            oldItem == newItem
    }
}
