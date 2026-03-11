package com.example.opticalsystem.data.api

import com.example.opticalsystem.data.model.ProductCategoryListResponse
import com.example.opticalsystem.data.model.ProductDetailResponse
import com.example.opticalsystem.data.model.ProductListResponse
import retrofit2.Response
import retrofit2.http.GET
import retrofit2.http.Path
import retrofit2.http.Query

interface ProductApi {

    @GET("products")
    suspend fun getProducts(
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 15,
        @Query("category_id") categoryId: Int? = null,
        @Query("brand") brand: String? = null,
        @Query("search") search: String? = null,
        @Query("min_price") minPrice: Double? = null,
        @Query("max_price") maxPrice: Double? = null,
        @Query("sort_by") sortBy: String? = null,
        @Query("sort_dir") sortDir: String? = null,
    ): Response<ProductListResponse>

    @GET("products/{id}")
    suspend fun getProduct(@Path("id") id: Int): Response<ProductDetailResponse>

    @GET("product-categories")
    suspend fun getCategories(): Response<ProductCategoryListResponse>
}
