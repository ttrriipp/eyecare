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
import com.example.opticalsystem.data.model.Order
import com.example.opticalsystem.databinding.ItemOrderBinding
import com.example.opticalsystem.util.StatusHelper

class OrderAdapter(
    private val onClick: (Order) -> Unit,
) : ListAdapter<Order, OrderAdapter.OrderViewHolder>(OrderDiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): OrderViewHolder {
        val binding = ItemOrderBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return OrderViewHolder(binding)
    }

    override fun onBindViewHolder(holder: OrderViewHolder, position: Int) {
        holder.bind(getItem(position), onClick)
    }

    class OrderViewHolder(
        private val binding: ItemOrderBinding,
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(order: Order, onClick: (Order) -> Unit) {
            val context = binding.root.context

            binding.tvOrderNumber.text = order.orderNumber
            binding.tvOrderTotal.text = StatusHelper.formatPrice(order.totalAmount)
            binding.tvOrderDate.text = StatusHelper.formatDateShort(order.createdAt)

            val itemCount = order.items?.size ?: 0
            binding.tvOrderItemCount.text = if (itemCount == 1) "1 item" else "$itemCount items"

            StatusHelper.applyOrderStatusBadge(binding.tvOrderStatus, order.status, order.statusLabel)

            // Load thumbnails
            val images = order.items
                ?.mapNotNull { it.product?.images?.firstOrNull()?.imageUrl }
                ?.take(2)

            val url1 = images?.getOrNull(0)
            val url2 = images?.getOrNull(1)

            loadThumbnail(context, binding.ivThumb1, url1)

            if (url2 != null) {
                binding.ivThumb2.isVisible = true
                loadThumbnail(context, binding.ivThumb2, url2)
            } else {
                binding.ivThumb2.isVisible = false
            }

            binding.root.setOnClickListener { onClick(order) }
        }

        private fun loadThumbnail(context: Context, imageView: android.widget.ImageView, url: String?) {
            val fullUrl = buildImageUrl(context, url)
            if (fullUrl == null) {
                imageView.setImageResource(R.drawable.bg_product_placeholder)
            } else {
                Glide.with(context)
                    .load(fullUrl)
                    .placeholder(R.drawable.bg_product_placeholder)
                    .error(R.drawable.bg_product_placeholder)
                    .centerCrop()
                    .circleCrop()
                    .into(imageView)
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

    class OrderDiffCallback : DiffUtil.ItemCallback<Order>() {
        override fun areItemsTheSame(oldItem: Order, newItem: Order): Boolean =
            oldItem.id == newItem.id

        override fun areContentsTheSame(oldItem: Order, newItem: Order): Boolean =
            oldItem == newItem
    }
}
