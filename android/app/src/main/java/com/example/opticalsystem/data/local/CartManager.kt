package com.example.opticalsystem.data.local

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject
import javax.inject.Singleton

private val Context.cartDataStore: DataStore<Preferences> by preferencesDataStore("cart")

@Singleton
class CartManager @Inject constructor(
    @ApplicationContext private val context: Context,
) {
    private val gson = Gson()
    private val cartKey = stringPreferencesKey("cart_items")

    val cartItems: Flow<List<CartItem>> = context.cartDataStore.data.map { prefs ->
        deserialize(prefs[cartKey])
    }

    suspend fun addToCart(item: CartItem) {
        context.cartDataStore.edit { prefs ->
            val current = deserialize(prefs[cartKey])
            val existingIndex = current.indexOfFirst {
                it.productId == item.productId && it.productVariantId == item.productVariantId
            }
            val updated = if (existingIndex >= 0) {
                current.toMutableList().also {
                    val existing = it[existingIndex]
                    it[existingIndex] = existing.copy(
                        quantity = existing.quantity + item.quantity,
                    )
                }
            } else {
                current + item
            }
            prefs[cartKey] = gson.toJson(updated)
        }
    }

    suspend fun updateQuantity(productId: Int, productVariantId: Int, quantity: Int) {
        context.cartDataStore.edit { prefs ->
            val current = deserialize(prefs[cartKey])
            val updated = current.map {
                if (it.productId == productId && it.productVariantId == productVariantId) {
                    it.copy(quantity = quantity)
                } else {
                    it
                }
            }
                .filter { it.quantity > 0 }
            prefs[cartKey] = gson.toJson(updated)
        }
    }

    suspend fun removeFromCart(productId: Int, productVariantId: Int) {
        context.cartDataStore.edit { prefs ->
            val current = deserialize(prefs[cartKey])
            prefs[cartKey] = gson.toJson(
                current.filterNot { it.productId == productId && it.productVariantId == productVariantId }
            )
        }
    }

    suspend fun clearCart() {
        context.cartDataStore.edit { prefs -> prefs.remove(cartKey) }
    }

    private fun deserialize(json: String?): List<CartItem> {
        if (json.isNullOrEmpty()) return emptyList()
        val type = object : TypeToken<List<CartItem>>() {}.type
        val raw = gson.fromJson<List<CartItem>>(json, type) ?: emptyList()
        // Drop legacy/invalid cart rows from older app versions that lack variant IDs.
        return raw.filter { it.productId > 0 && it.productVariantId > 0 && it.quantity > 0 }
    }
}
