package com.example.opticalsystem.ui.messages

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.example.opticalsystem.data.model.Message
import com.example.opticalsystem.databinding.ItemMessageDateSeparatorBinding
import com.example.opticalsystem.databinding.ItemMessageIncomingBinding
import com.example.opticalsystem.databinding.ItemMessageOutgoingBinding
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale
import java.util.TimeZone

class MessageAdapter : RecyclerView.Adapter<RecyclerView.ViewHolder>() {

    private sealed class ListItem {
        data class DateHeader(val label: String) : ListItem()
        data class MessageItem(val message: Message, val isOutgoing: Boolean) : ListItem()
    }

    private var items: List<ListItem> = emptyList()

    /** The logged-in customer's sender role — incoming = not "customer". */
    private val customerRole = "customer"

    companion object {
        private const val TYPE_DATE = 0
        private const val TYPE_INCOMING = 1
        private const val TYPE_OUTGOING = 2
    }

    fun submitMessages(messages: List<Message>) {
        items = buildListItems(messages)
        notifyDataSetChanged()
    }

    private fun buildListItems(messages: List<Message>): List<ListItem> {
        val result = mutableListOf<ListItem>()
        var lastDateLabel: String? = null

        for (msg in messages) {
            val dateLabel = resolveDateLabel(msg.createdAt)
            if (dateLabel != lastDateLabel) {
                result.add(ListItem.DateHeader(dateLabel))
                lastDateLabel = dateLabel
            }
            val isOutgoing = msg.sender?.role == customerRole || msg.sender == null
            result.add(ListItem.MessageItem(msg, isOutgoing))
        }
        return result
    }

    /**
     * Laravel [toISOString] is UTC with `Z` and may include fractional seconds (e.g. microseconds).
     * Parse as UTC, then format/compare in the device local zone for display.
     */
    private fun parseMessageInstant(isoDate: String): Date? {
        val trimmed = isoDate.trim()
        if (trimmed.isEmpty()) return null
        return try {
            val withoutZ = when {
                trimmed.endsWith("Z", ignoreCase = true) -> trimmed.dropLast(1)
                else -> trimmed
            }
            // Strip sub-second fraction; SimpleDateFormat only handles ms reliably for our minSdk path.
            val upToSeconds = withoutZ.substringBefore('.')
            val utcParser = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US).apply {
                timeZone = TimeZone.getTimeZone("UTC")
            }
            utcParser.parse(upToSeconds)
        } catch (_: Exception) {
            null
        }
    }

    private fun resolveDateLabel(isoDate: String): String {
        val date = parseMessageInstant(isoDate) ?: return isoDate.take(10).ifBlank { isoDate }
        return try {
            val cal = Calendar.getInstance().apply { time = date }
            val today = Calendar.getInstance()
            val yesterday = Calendar.getInstance().apply { add(Calendar.DAY_OF_YEAR, -1) }
            when {
                isSameDay(cal, today) -> "Today"
                isSameDay(cal, yesterday) -> "Yesterday"
                else -> SimpleDateFormat("MMMM d", Locale.getDefault()).format(date)
            }
        } catch (_: Exception) {
            isoDate.take(10)
        }
    }

    private fun isSameDay(a: Calendar, b: Calendar): Boolean =
        a.get(Calendar.YEAR) == b.get(Calendar.YEAR) &&
            a.get(Calendar.DAY_OF_YEAR) == b.get(Calendar.DAY_OF_YEAR)

    private fun formatTime(isoDate: String): String {
        val date = parseMessageInstant(isoDate) ?: return ""
        return SimpleDateFormat("h:mm a", Locale.getDefault()).format(date)
    }

    override fun getItemCount() = items.size

    override fun getItemViewType(position: Int): Int = when (items[position]) {
        is ListItem.DateHeader -> TYPE_DATE
        is ListItem.MessageItem -> if ((items[position] as ListItem.MessageItem).isOutgoing) TYPE_OUTGOING else TYPE_INCOMING
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): RecyclerView.ViewHolder {
        val inflater = LayoutInflater.from(parent.context)
        return when (viewType) {
            TYPE_DATE -> DateHeaderViewHolder(
                ItemMessageDateSeparatorBinding.inflate(inflater, parent, false),
            )
            TYPE_OUTGOING -> OutgoingViewHolder(
                ItemMessageOutgoingBinding.inflate(inflater, parent, false),
            )
            else -> IncomingViewHolder(
                ItemMessageIncomingBinding.inflate(inflater, parent, false),
            )
        }
    }

    override fun onBindViewHolder(holder: RecyclerView.ViewHolder, position: Int) {
        when (val item = items[position]) {
            is ListItem.DateHeader -> (holder as DateHeaderViewHolder).bind(item.label)
            is ListItem.MessageItem -> when (holder) {
                is OutgoingViewHolder -> holder.bind(item.message)
                is IncomingViewHolder -> holder.bind(item.message)
            }
        }
    }

    // ── ViewHolders ───────────────────────────────────────────────────────────

    inner class DateHeaderViewHolder(
        private val binding: ItemMessageDateSeparatorBinding,
    ) : RecyclerView.ViewHolder(binding.root) {
        fun bind(label: String) {
            binding.tvDate.text = label
        }
    }

    inner class IncomingViewHolder(
        private val binding: ItemMessageIncomingBinding,
    ) : RecyclerView.ViewHolder(binding.root) {
        fun bind(message: Message) {
            binding.tvBody.text = message.body
            binding.tvSender.text = message.sender?.name ?: "Optical Shop"
            binding.tvTime.text = formatTime(message.createdAt)
        }
    }

    inner class OutgoingViewHolder(
        private val binding: ItemMessageOutgoingBinding,
    ) : RecyclerView.ViewHolder(binding.root) {
        fun bind(message: Message) {
            binding.tvBody.text = message.body
            binding.tvTime.text = formatTime(message.createdAt)
        }
    }
}
