package com.example.opticalsystem.ui.orders

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
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

            binding.tvOrderNumber.text = context.getString(R.string.order_number_format, order.orderNumber)
            binding.tvOrderTotal.text = StatusHelper.formatPrice(order.totalAmount)
            binding.tvOrderDate.text = StatusHelper.formatDateShort(order.createdAt)

            val itemCount = order.items?.size ?: 0
            binding.tvOrderItemCount.text = context.getString(R.string.order_items_count_format, itemCount)

            StatusHelper.applyOrderStatusBadge(binding.tvOrderStatus, order.status, order.statusLabel)

            binding.root.setOnClickListener { onClick(order) }
        }
    }

    class OrderDiffCallback : DiffUtil.ItemCallback<Order>() {
        override fun areItemsTheSame(oldItem: Order, newItem: Order): Boolean =
            oldItem.id == newItem.id

        override fun areContentsTheSame(oldItem: Order, newItem: Order): Boolean =
            oldItem == newItem
    }
}
