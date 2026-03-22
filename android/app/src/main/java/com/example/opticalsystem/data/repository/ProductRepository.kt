package com.example.opticalsystem.data.repository

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
    suspend fun getProducts(
        page: Int = 1,
        perPage: Int = 15,
        categoryId: Int? = null,
        brand: String? = null,
        search: String? = null,
    ): Resource<List<Product>> {
        return try {
            val response = productApi.getProducts(
                page = page,
                perPage = perPage,
                categoryId = categoryId,
                brand = brand,
                search = search,
            )
            if (response.isSuccessful && response.body() != null) {
                Resource.Success(response.body()!!.data)
            } else {
                Resource.Error(response.errorBody()?.string() ?: "Failed to load products")
            }
        } catch (e: Exception) {
            Resource.Error(e.message ?: "Network error")
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
            Resource.Error(e.message ?: "Network error")
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
            Resource.Error(e.message ?: "Network error")
        }
    }
}
