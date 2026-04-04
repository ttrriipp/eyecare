package com.example.opticalsystem.data.local

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject
import javax.inject.Singleton

private val Context.wishlistDataStore: DataStore<Preferences> by preferencesDataStore("wishlist")

@Singleton
class WishlistManager @Inject constructor(
    @ApplicationContext private val context: Context,
) {
    private val wishlistKey = stringPreferencesKey("wishlist_ids")

    val wishlistIds: Flow<Set<Int>> = context.wishlistDataStore.data.map { prefs ->
        val raw = prefs[wishlistKey] ?: return@map emptySet()
        raw.split(",")
            .mapNotNull { it.toIntOrNull() }
            .toSet()
    }

    suspend fun toggle(productId: Int) {
        context.wishlistDataStore.edit { prefs ->
            val current = prefs[wishlistKey]
                ?.split(",")
                ?.mapNotNull { it.toIntOrNull() }
                ?.toMutableSet()
                ?: mutableSetOf()

            if (current.contains(productId)) {
                current.remove(productId)
            } else {
                current.add(productId)
            }

            if (current.isEmpty()) {
                prefs.remove(wishlistKey)
            } else {
                prefs[wishlistKey] = current.joinToString(",")
            }
        }
    }
}

