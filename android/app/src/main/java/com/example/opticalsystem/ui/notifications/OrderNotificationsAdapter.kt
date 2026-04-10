package com.example.opticalsystem.ui.notifications

import android.text.format.DateUtils
import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.example.opticalsystem.R
import com.example.opticalsystem.databinding.ItemOrderNotificationBinding
import com.example.opticalsystem.notifications.OrderStatusNotifier

class OrderNotificationsAdapter :
    ListAdapter<OrderStatusNotifier.InAppNotification, OrderNotificationsAdapter.NotificationViewHolder>(Diff) {

    var onItemClick: ((OrderStatusNotifier.InAppNotification) -> Unit)? = null

    object Diff : DiffUtil.ItemCallback<OrderStatusNotifier.InAppNotification>() {
        override fun areItemsTheSame(
            oldItem: OrderStatusNotifier.InAppNotification,
            newItem: OrderStatusNotifier.InAppNotification,
        ): Boolean = oldItem.orderId == newItem.orderId && oldItem.changedAtMillis == newItem.changedAtMillis

        override fun areContentsTheSame(
            oldItem: OrderStatusNotifier.InAppNotification,
            newItem: OrderStatusNotifier.InAppNotification,
        ): Boolean = oldItem == newItem
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): NotificationViewHolder {
        val binding = ItemOrderNotificationBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return NotificationViewHolder(binding)
    }

    override fun onBindViewHolder(holder: NotificationViewHolder, position: Int) {
        holder.bind(getItem(position), onItemClick)
    }

    class NotificationViewHolder(
        private val binding: ItemOrderNotificationBinding,
    ) : RecyclerView.ViewHolder(binding.root) {
        fun bind(
            item: OrderStatusNotifier.InAppNotification,
            onItemClick: ((OrderStatusNotifier.InAppNotification) -> Unit)?,
        ) {
            binding.tvTitle.text = binding.root.context.getString(
                R.string.order_notification_item_title,
                item.orderNumber,
            )
            binding.tvMessage.text = binding.root.context.getString(
                R.string.order_notification_item_message,
                item.statusLabel,
            )
            binding.tvTime.text = DateUtils.getRelativeTimeSpanString(
                item.changedAtMillis,
                System.currentTimeMillis(),
                DateUtils.MINUTE_IN_MILLIS,
            )
            binding.root.setOnClickListener { onItemClick?.invoke(item) }
        }
    }
}
