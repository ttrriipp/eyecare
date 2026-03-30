package com.example.opticalsystem.ui.products

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.example.opticalsystem.data.model.Feedback
import com.example.opticalsystem.databinding.ItemFeedbackBinding

class FeedbackAdapter : ListAdapter<Feedback, FeedbackAdapter.FeedbackViewHolder>(FeedbackDiff) {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): FeedbackViewHolder {
        val binding = ItemFeedbackBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return FeedbackViewHolder(binding)
    }

    override fun onBindViewHolder(holder: FeedbackViewHolder, position: Int) {
        holder.bind(getItem(position))
    }

    class FeedbackViewHolder(
        private val binding: ItemFeedbackBinding,
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(feedback: Feedback) {
            binding.tvUserName.text = feedback.user?.name ?: "Anonymous"
            binding.ratingBarFeedback.rating = feedback.rating.toFloat()
            binding.tvComment.text = feedback.comment?.takeIf { it.isNotBlank() } ?: "—"

            // Backend sends ISO timestamps (e.g., 2026-03-30T12:34:56.000Z).
            binding.tvCreatedAt.text = feedback.createdAt.take(10).ifBlank { "—" }
        }
    }

    private object FeedbackDiff : DiffUtil.ItemCallback<Feedback>() {
        override fun areItemsTheSame(oldItem: Feedback, newItem: Feedback): Boolean = oldItem.id == newItem.id
        override fun areContentsTheSame(oldItem: Feedback, newItem: Feedback): Boolean = oldItem == newItem
    }
}

