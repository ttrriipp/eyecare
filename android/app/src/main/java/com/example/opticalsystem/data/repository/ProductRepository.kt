package com.example.opticalsystem.data.repository
import android.util.Log
import com.example.opticalsystem.data.api.ProductApi
import com.example.opticalsystem.data.model.Product
import com.example.opticalsystem.data.model.ProductCategory
import com.example.opticalsystem.util.Resource
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ProductRepository @Inject constructor(
    private val productApi: ProductApi,
) {
    companion object {
        private const val TAG = "ProductRepository"
    }

    private fun networkError(e: Exception, fallback: String): String {
        val msg = e.message?.trim()
        return if (!msg.isNullOrEmpty()) {
            "Network error (${e.javaClass.simpleName}): $msg"
        } else {
            "Network error (${e.javaClass.simpleName})"
        }.ifBlank { fallback }
    }

    suspend fun getProducts(
        page: Int = 1,
        perPage: Int = 15,
        categoryId: Int? = null,
        brand: String? = null,
        search: String? = null,
        sortBy: String? = null,
        sortDir: String? = null,
    ): Resource<Pair<List<Product>, Int>> {
        return try {
            val response = productApi.getProducts(
                page = page,
                perPage = perPage,
                categoryId = categoryId,
                brand = brand,
                search = search,
                sortBy = sortBy,
                sortDir = sortDir,
            )
            if (response.isSuccessful && response.body() != null) {
                val body = response.body()!!
                val total = body.meta?.total ?: body.data.size
                Resource.Success(Pair(body.data, total))
            } else {
                Resource.Error(response.errorBody()?.string() ?: "Failed to load products")
            }
        } catch (e: Exception) {
            Log.e(TAG, "getProducts failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }

    suspend fun getProduct(id: Int): Resource<Product> {
        return try {
            val response = productApi.getProduct(id)
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.data)
            } else {
                Resource.Error(response.errorBody()?.string() ?: "Product not found")
            }
        } catch (e: Exception) {
            Log.e(TAG, "getProduct failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }

    suspend fun getCategories(): Resource<List<ProductCategory>> {
        return try {
            val response = productApi.getCategories()
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.data)
            } else {
                Resource.Error(response.errorBody()?.string() ?: "Failed to load categories")
            }
        } catch (e: Exception) {
            Log.e(TAG, "getCategories failed", e)
            Resource.Error(networkError(e, fallback = "Network error"))
        }
    }
}
