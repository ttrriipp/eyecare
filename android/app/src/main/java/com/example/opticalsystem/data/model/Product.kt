package com.example.opticalsystem.data.model

import com.google.gson.annotations.SerializedName

data class Product(
    val id: Int,
    val category: ProductCategory?,
    val name: String,
    val description: String?,
    val price: String,
    val sku: String,
    val brand: String?,
    @SerializedName("lens_type")
    val lensType: String?,
    @SerializedName("frame_material")
    val frameMaterial: String?,
    @SerializedName("ar_model_url")
    val arModelUrl: String?,
    @SerializedName("is_active")
    val isActive: Boolean,
    val images: List<ProductImage>?,
    @SerializedName("created_at")
    val createdAt: String,
    @SerializedName("updated_at")
    val updatedAt: String,
)

data class ProductCategory(
    val id: Int,
    val name: String,
    val slug: String,
    val description: String?,
    @SerializedName("products_count")
    val productsCount: Int?,
    @SerializedName("created_at")
    val createdAt: String,
    @SerializedName("updated_at")
    val updatedAt: String,
)

data class ProductImage(
    val id: Int,
    @SerializedName("image_url")
    val imageUrl: String,
    @SerializedName("sort_order")
    val sortOrder: Int,
    @SerializedName("created_at")
    val createdAt: String,
)

data class ProductListResponse(
    val data: List<Product>,
    val meta: PaginationMeta?,
)

data class ProductDetailResponse(
    val data: Product,
)

data class ProductCategoryListResponse(
    val data: List<ProductCategory>,
)

data class PaginationMeta(
    @SerializedName("current_page")
    val currentPage: Int,
    @SerializedName("last_page")
    val lastPage: Int,
    @SerializedName("per_page")
    val perPage: Int,
    val total: Int,
)
