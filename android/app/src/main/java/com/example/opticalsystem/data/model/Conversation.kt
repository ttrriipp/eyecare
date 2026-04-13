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
    @SerializedName("last_message_at")
    val lastMessageAt: String?,
    val user: ConversationUser?,
    @SerializedName("message_count")
    val messageCount: Int?,
    @SerializedName("unread_count")
    val unreadCount: Int?,
    @SerializedName("created_at")
    val createdAt: String,
)

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

/** Empty JSON body for POST /conversations (idempotent get-or-create). */
class StartConversationRequest

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
    /** Present on `POST conversations/my/messages` (first message creates the thread). */
    val conversation: Conversation? = null,
)

data class SendMessageResult(
    val message: Message,
    val conversation: Conversation,
)
