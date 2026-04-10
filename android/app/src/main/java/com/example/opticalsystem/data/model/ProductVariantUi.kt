package com.example.opticalsystem.data.model

/** Variants to show in pickers: prefer explicit list, else default-only product. */
fun Product.selectableVariants(): List<ProductVariant> {
    val list = variants.orEmpty()
    if (list.isNotEmpty()) {
        return list.sortedWith(compareBy({ !it.isDefault }, { it.id }))
    }
    return defaultVariant?.let { listOf(it) } ?: emptyList()
}

/** AR badge: category allows AR and at least one variant has a model URL. */
fun Product.hasArTryOn(): Boolean {
    val categoryOk = category?.hasArSupport != false
    if (!categoryOk) return false
    return selectableVariants().any { !it.arModelUrl.isNullOrBlank() }
}

fun ProductVariant.displayLabel(): String {
    val parts = listOfNotNull(
        color?.trim()?.takeIf { it.isNotEmpty() },
        frameSize?.trim()?.takeIf { it.isNotEmpty() },
        material?.trim()?.takeIf { it.isNotEmpty() },
        lensType?.trim()?.takeIf { it.isNotEmpty() },
        power?.trim()?.takeIf { it.isNotEmpty() }?.let { "Power $it" },
        duration?.trim()?.takeIf { it.isNotEmpty() }?.let { "Duration $it" },
        baseCurve?.trim()?.takeIf { it.isNotEmpty() },
        diameter?.trim()?.takeIf { it.isNotEmpty() },
    )
    return if (parts.isNotEmpty()) parts.joinToString(" · ") else "Default"
}

/**
 * Line-item price for this variant. Uses API [ProductVariant.unitPrice], then variant [ProductVariant.price],
 * then product list [Product.price] (default variant) as fallback.
 */
fun ProductVariant.displayUnitPrice(product: Product): String {
    unitPrice?.trim()?.takeIf { it.isNotEmpty() }?.let { return it }
    price?.trim()?.takeIf { it.isNotEmpty() }?.let { return it }
    return product.price.trim().takeIf { it.isNotEmpty() } ?: "0.00"
}

fun ProductVariant.primaryImageUrl(): String? =
    images?.firstOrNull()?.imageUrl
