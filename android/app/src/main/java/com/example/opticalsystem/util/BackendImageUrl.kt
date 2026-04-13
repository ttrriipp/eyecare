package com.example.opticalsystem.util

import android.content.Context
import android.net.Uri
import com.example.opticalsystem.R

object BackendImageUrl {

    fun resolve(context: Context, url: String?): String? {
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
