package com.example.opticalsystem.data.model

import com.google.gson.annotations.SerializedName

// ── Nested objects ────────────────────────────────────────────────────────────

data class ConversationUser(
    val id: Int,
    val name: String,
)

data class MessageSender(
    val id: Int,
    val name: String,
    val role: String,
)

// ── Core models ───────────────────────────────────────────────────────────────

data class Conversation(
    val id: Int,
    val subject: String?,
    val status: String,
    @SerializedName("status_label")
    val statusLabel: String,
    @SerializedName("last_message_at")
    val lastMessageAt: String?,
    val user: ConversationUser?,
    @SerializedName("message_count")
    val messageCount: Int?,
    @SerializedName("unread_count")
    val unreadCount: Int?,
    @SerializedName("created_at")
    val createdAt: String,
) {
    val isOpen: Boolean get() = status == "open"
    val isClosed: Boolean get() = status == "closed"
}

data class Message(
    val id: Int,
    @SerializedName("conversation_id")
    val conversationId: Int,
    val sender: MessageSender?,
    val body: String,
    @SerializedName("is_read")
    val isRead: Boolean,
    @SerializedName("read_at")
    val readAt: String?,
    @SerializedName("created_at")
    val createdAt: String,
)

// ── API request / response bodies ────────────────────────────────────────────

data class StartConversationRequest(
    val subject: String?,
)

data class SendMessageRequest(
    val body: String,
)

data class UnreadCountResponse(
    @SerializedName("unread_count")
    val unreadCount: Int,
)

data class ConversationListResponse(
    val data: List<Conversation>,
    val meta: PaginationMeta?,
)

data class ConversationDetailResponse(
    val conversation: Conversation,
)

data class MessageListResponse(
    val data: List<Message>,
)

data class SendMessageResponse(
    val message: String,
    val data: Message,
)
