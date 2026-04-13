package com.example.opticalsystem.data.model

import com.google.gson.annotations.SerializedName

/**
 * Catalog product from [GET /products]. Sellable units and SKUs are on [ProductVariant].
 */
data class Product(
    val id: Int,
    val category: ProductCategory?,
    val name: String,
    val description: String?,
    val price: String,
    /** Present for admin/staff only; customers omit this key. Default variant SKU. */
    val sku: String? = null,
    val brand: String?,
    @SerializedName("is_active")
    val isActive: Boolean? = null,
    val images: List<ProductImage>?,
    @SerializedName("default_variant")
    val defaultVariant: ProductVariant? = null,
    val variants: List<ProductVariant>? = null,
    @SerializedName("average_rating")
    val averageRating: Float?,
    @SerializedName("reviews_count")
    val reviewsCount: Int?,
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
    @SerializedName("created_at")
    val createdAt: String,
    @SerializedName("updated_at")
    val updatedAt: String,
    @SerializedName("has_ar_support")
    val hasArSupport: Boolean? = null,
    @SerializedName("requires_expiry_tracking")
    val requiresExpiryTracking: Boolean? = null,
    @SerializedName("requires_prescription")
    val requiresPrescription: Boolean? = null,
    @SerializedName("stock_unit")
    val stockUnit: String? = null,
    @SerializedName("is_system")
    val isSystem: Boolean? = null,
    @SerializedName("has_frame_size")
    val hasFrameSize: Boolean? = null,
    @SerializedName("has_color")
    val hasColor: Boolean? = null,
    @SerializedName("has_material")
    val hasMaterial: Boolean? = null,
    @SerializedName("has_lens_type")
    val hasLensType: Boolean? = null,
    @SerializedName("has_power_field")
    val hasPowerField: Boolean? = null,
    @SerializedName("has_duration")
    val hasDuration: Boolean? = null,
    @SerializedName("products_count")
    val productsCount: Int? = null,
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

/**
 * One sellable SKU (frame color/size, lens SKU, etc.). Mirrors [ProductVariantResource].
 */
data class ProductVariant(
    val id: Int,
    @SerializedName("product_id")
    val productId: Int,
    val sku: String?,
    val color: String?,
    @SerializedName("frame_size")
    val frameSize: String?,
    val material: String?,
    @SerializedName("lens_type")
    val lensType: String?,
    val power: String?,
    val duration: String?,
    @SerializedName("base_curve")
    val baseCurve: String?,
    val diameter: String?,
    val price: String?,
    @SerializedName("stock_quantity")
    val stockQuantity: Int? = null,
    @SerializedName("is_default")
    val isDefault: Boolean = false,
    @SerializedName("ar_model_url")
    val arModelUrl: String?,
    /** Present when the API includes computed price (detail + nested order variant). */
    @SerializedName("unit_price")
    val unitPrice: String? = null,
    val images: List<ProductImage>? = null,
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
