package com.example.opticalsystem.ui.messages

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.core.view.isVisible
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Conversation
import com.example.opticalsystem.databinding.ItemConversationBinding
import com.example.opticalsystem.util.StatusHelper

class ConversationsAdapter(
    private val onClick: (Conversation) -> Unit,
) : ListAdapter<Conversation, ConversationsAdapter.ConversationViewHolder>(ConversationDiffCallback()) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ConversationViewHolder {
        val binding = ItemConversationBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ConversationViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ConversationViewHolder, position: Int) {
        holder.bind(getItem(position), onClick)
    }

    class ConversationViewHolder(
        private val binding: ItemConversationBinding,
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(conversation: Conversation, onClick: (Conversation) -> Unit) {
            val context = binding.root.context
            binding.tvTitle.text = conversation.subject?.takeIf { it.isNotBlank() }
                ?: context.getString(R.string.conversation_default_title)
            binding.tvStatus.text = conversation.statusLabel
            val dateRaw = conversation.lastMessageAt ?: conversation.createdAt
            binding.tvDate.text = StatusHelper.formatDateShort(dateRaw)

            val unread = conversation.unreadCount ?: 0
            binding.tvUnreadBadge.isVisible = unread > 0
            binding.tvUnreadBadge.text = if (unread > 99) "99+" else unread.toString()

            binding.root.setOnClickListener { onClick(conversation) }
        }
    }

    private class ConversationDiffCallback : DiffUtil.ItemCallback<Conversation>() {
        override fun areItemsTheSame(oldItem: Conversation, newItem: Conversation): Boolean =
            oldItem.id == newItem.id

        override fun areContentsTheSame(oldItem: Conversation, newItem: Conversation): Boolean =
            oldItem == newItem
    }
}
