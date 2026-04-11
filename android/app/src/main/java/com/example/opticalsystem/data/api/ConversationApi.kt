package com.example.opticalsystem.data.api

import com.example.opticalsystem.data.model.ConversationDetailResponse
import com.example.opticalsystem.data.model.ConversationListResponse
import com.example.opticalsystem.data.model.MessageListResponse
import com.example.opticalsystem.data.model.SendMessageRequest
import com.example.opticalsystem.data.model.SendMessageResponse
import com.example.opticalsystem.data.model.StartConversationRequest
import com.example.opticalsystem.data.model.UnreadCountResponse
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface ConversationApi {

    @GET("conversations")
    suspend fun getConversations(
        @Query("status") status: String? = null,
        @Query("per_page") perPage: Int = 50,
    ): Response<ConversationListResponse>

    @GET("conversations/unread-count")
    suspend fun getUnreadCount(): Response<UnreadCountResponse>

    @GET("conversations/{id}")
    suspend fun getConversation(
        @Path("id") id: Int,
    ): Response<ConversationDetailResponse>

    @POST("conversations")
    suspend fun startConversation(
        @Body request: StartConversationRequest,
    ): Response<ConversationDetailResponse>

    @GET("conversations/{id}/messages")
    suspend fun getMessages(
        @Path("id") conversationId: Int,
    ): Response<MessageListResponse>

    @POST("conversations/{id}/messages")
    suspend fun sendMessage(
        @Path("id") conversationId: Int,
        @Body request: SendMessageRequest,
    ): Response<SendMessageResponse>

    @PATCH("conversations/{id}/close")
    suspend fun closeConversation(
        @Path("id") conversationId: Int,
    ): Response<ConversationDetailResponse>
}
