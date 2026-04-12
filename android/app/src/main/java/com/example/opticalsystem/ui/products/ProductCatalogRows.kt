package com.example.opticalsystem.ui.products

import com.example.opticalsystem.data.model.Product

/** Same sectioning rules as the former [ProductAdapter] (grid vs list rows). */
object ProductCatalogRows {
    sealed interface RowModel {
        data class Header(val title: String) : RowModel
        data class GridProduct(val product: Product) : RowModel
        data class ListProduct(val product: Product) : RowModel
    }

    fun buildRows(products: List<Product>): List<RowModel> {
        if (products.isEmpty()) return emptyList()
        val featured = products.filter(::isVisualProduct)
        val consumables = products.filterNot(::isVisualProduct)
        val rows = mutableListOf<RowModel>()
        if (featured.isNotEmpty()) {
            rows += featured.map { RowModel.GridProduct(it) }
        }
        consumables
            .groupBy { sectionNameFor(it) }
            .forEach { (section, sectionProducts) ->
                rows += RowModel.Header(section)
                rows += sectionProducts.map { RowModel.ListProduct(it) }
            }
        return rows
    }

    private fun isVisualProduct(product: Product): Boolean {
        val slug = product.category?.slug?.lowercase().orEmpty()
        return slug.contains("frame") || slug.contains("sunglass")
    }

    private fun sectionNameFor(product: Product): String {
        val name = product.category?.name?.trim().orEmpty()
        if (name.isNotEmpty()) return name
        return "Products"
    }

    fun rowKey(row: RowModel): String = when (row) {
        is RowModel.Header -> "h_${row.title}"
        is RowModel.GridProduct -> "g_${row.product.id}"
        is RowModel.ListProduct -> "l_${row.product.id}"
    }
}
