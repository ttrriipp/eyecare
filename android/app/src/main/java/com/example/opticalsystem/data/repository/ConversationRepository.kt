package com.example.opticalsystem.data.repository

import android.util.Log
import com.example.opticalsystem.data.api.ConversationApi
import com.example.opticalsystem.data.model.Conversation
import com.example.opticalsystem.data.model.Message
import com.example.opticalsystem.data.model.SendMessageRequest
import com.example.opticalsystem.data.model.StartConversationRequest
import com.example.opticalsystem.util.Resource
import org.json.JSONObject
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ConversationRepository @Inject constructor(
    private val conversationApi: ConversationApi,
) {
    companion object {
        private const val TAG = "ConversationRepository"
    }

    private fun networkError(e: Exception): String {
        val msg = e.message?.trim()
        return if (!msg.isNullOrEmpty()) {
            "Network error (${e.javaClass.simpleName}): $msg"
        } else {
            "Network error (${e.javaClass.simpleName})"
        }
    }

    private fun parseError(code: Int, rawBody: String?, fallback: String): String {
        val body = rawBody?.trim().orEmpty()
        if (body.isNotEmpty()) {
            runCatching {
                val json = JSONObject(body)
                val errorsObj = json.optJSONObject("errors")
                if (errorsObj != null) {
                    val keys = errorsObj.keys()
                    while (keys.hasNext()) {
                        val key = keys.next()
                        val arr = errorsObj.optJSONArray(key)
                        if (arr != null && arr.length() > 0) {
                            val first = arr.optString(0)
                            if (first.isNotBlank()) return first
                        }
                    }
                }
                val message = json.optString("message").trim()
                if (message.isNotBlank()) return message
            }
        }
        return if (code >= 500) "Server error ($code). Please try again." else fallback
    }

    suspend fun getConversations(status: String? = null): Resource<List<Conversation>> {
        return try {
            val response = conversationApi.getConversations(status = status)
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.data)
            } else {
                Resource.Error(
                    parseError(response.code(), response.errorBody()?.string(), "Failed to load conversations"),
                )
            }
        } catch (e: Exception) {
            Log.e(TAG, "getConversations failed", e)
            Resource.Error(networkError(e))
        }
    }

    suspend fun getUnreadCount(): Resource<Int> {
        return try {
            val response = conversationApi.getUnreadCount()
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.unreadCount)
            } else {
                Resource.Error(
                    parseError(response.code(), response.errorBody()?.string(), "Failed to get unread count"),
                )
            }
        } catch (e: Exception) {
            Log.e(TAG, "getUnreadCount failed", e)
            Resource.Error(networkError(e))
        }
    }

    suspend fun startConversation(subject: String?): Resource<Conversation> {
        return try {
            val response = conversationApi.startConversation(StartConversationRequest(subject = subject))
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.conversation)
            } else {
                Resource.Error(
                    parseError(response.code(), response.errorBody()?.string(), "Failed to start conversation"),
                )
            }
        } catch (e: Exception) {
            Log.e(TAG, "startConversation failed", e)
            Resource.Error(networkError(e))
        }
    }

    suspend fun getMessages(conversationId: Int): Resource<List<Message>> {
        return try {
            val response = conversationApi.getMessages(conversationId)
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.data)
            } else {
                Resource.Error(
                    parseError(response.code(), response.errorBody()?.string(), "Failed to load messages"),
                )
            }
        } catch (e: Exception) {
            Log.e(TAG, "getMessages failed", e)
            Resource.Error(networkError(e))
        }
    }

    suspend fun sendMessage(conversationId: Int, body: String): Resource<Message> {
        return try {
            val response = conversationApi.sendMessage(conversationId, SendMessageRequest(body = body))
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.data)
            } else {
                Resource.Error(
                    parseError(response.code(), response.errorBody()?.string(), "Failed to send message"),
                )
            }
        } catch (e: Exception) {
            Log.e(TAG, "sendMessage failed", e)
            Resource.Error(networkError(e))
        }
    }

    suspend fun closeConversation(conversationId: Int): Resource<Conversation> {
        return try {
            val response = conversationApi.closeConversation(conversationId)
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.conversation)
            } else {
                Resource.Error(
                    parseError(response.code(), response.errorBody()?.string(), "Failed to close conversation"),
                )
            }
        } catch (e: Exception) {
            Log.e(TAG, "closeConversation failed", e)
            Resource.Error(networkError(e))
        }
    }
}
