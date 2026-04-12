@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)

package com.example.opticalsystem.ui.products

import android.widget.ImageView
import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.BasicTextField
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Text
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.SolidColor
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardCapitalization
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.runtime.livedata.observeAsState
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.bumptech.glide.Glide
import com.bumptech.glide.signature.ObjectKey
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Product
import com.example.opticalsystem.data.model.ProductCategory
import com.example.opticalsystem.data.model.ProductVariant
import com.example.opticalsystem.data.model.hasArTryOn
import com.example.opticalsystem.ui.components.CartIconWithBadge
import com.example.opticalsystem.ui.components.RatingStarsRow
import com.example.opticalsystem.ui.theme.EyeCareTheme
import com.example.opticalsystem.util.BackendImageUrl
import com.example.opticalsystem.util.Resource
import kotlinx.coroutines.delay

private data class SortChoice(
    val label: String,
    val sortBy: String?,
    val sortDir: String?,
)

@Composable
fun ProductListScreen(
    viewModel: ProductListViewModel,
    onOpenProduct: (Product) -> Unit,
    onOpenCart: () -> Unit,
) {
    val context = LocalContext.current
    val productsResult by viewModel.products.observeAsState()
    val categoriesResult by viewModel.categories.observeAsState()
    val cartCount by viewModel.cartItemCount.collectAsStateWithLifecycle()

    var searchText by remember { mutableStateOf("") }
    LaunchedEffect(searchText) {
        delay(500)
        viewModel.applySearch(searchText)
    }

    val sortOptions = listOf(
        SortChoice(stringResource(R.string.sort_popular), null, null),
        SortChoice(stringResource(R.string.sort_price_low_high), "price", "asc"),
        SortChoice(stringResource(R.string.sort_price_high_low), "price", "desc"),
        SortChoice(stringResource(R.string.sort_name_az), "name", "asc"),
        SortChoice(stringResource(R.string.sort_name_za), "name", "desc"),
    )

    var sortChoice by remember { mutableStateOf(sortOptions.first()) }

    var sortMenuExpanded by remember { mutableStateOf(false) }

    var selectedCategoryId by remember { mutableStateOf<Int?>(null) }

    var lastSuccessRows by remember { mutableStateOf<List<ProductCatalogRows.RowModel>>(emptyList()) }
    LaunchedEffect(productsResult) {
        when (val r = productsResult) {
            is Resource.Success ->
                lastSuccessRows = ProductCatalogRows.buildRows(r.data)
            else -> Unit
        }
    }

    val rows = when (val r = productsResult) {
        is Resource.Success -> ProductCatalogRows.buildRows(r.data)
        is Resource.Loading -> lastSuccessRows
        else -> lastSuccessRows
    }

    LaunchedEffect(productsResult) {
        val err = productsResult as? Resource.Error ?: return@LaunchedEffect
        Toast.makeText(context, err.message, Toast.LENGTH_LONG).show()
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(colorResource(R.color.background)),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .background(colorResource(R.color.primary))
                .padding(start = 20.dp, end = 12.dp, top = 10.dp, bottom = 10.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Text(
                text = stringResource(R.string.nav_catalog),
                modifier = Modifier.weight(1f),
                color = colorResource(R.color.on_primary),
                fontSize = 26.sp,
                fontWeight = FontWeight.Bold,
            )
            CartIconWithBadge(
                cartCount = cartCount,
                onClick = onOpenCart,
                iconTint = colorResource(R.color.on_primary),
                contentDescription = stringResource(R.string.nav_cart),
            )
        }

        Card(
            modifier = Modifier
                .fillMaxWidth()
                .height(46.dp)
                .padding(start = 16.dp, end = 16.dp, top = 8.dp),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
            border = BorderStroke(1.dp, colorResource(R.color.divider)),
        ) {
            Row(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(horizontal = 12.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Icon(
                    painter = painterResource(R.drawable.ic_search_20),
                    contentDescription = stringResource(R.string.search_hint),
                    modifier = Modifier.size(20.dp),
                    tint = colorResource(R.color.text_secondary),
                )
                BasicTextField(
                    value = searchText,
                    onValueChange = { searchText = it },
                    modifier = Modifier
                        .weight(1f)
                        .padding(start = 8.dp),
                    textStyle = TextStyle(
                        color = colorResource(R.color.text_primary),
                        fontSize = 13.sp,
                    ),
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(
                        capitalization = KeyboardCapitalization.Sentences,
                    ),
                    cursorBrush = SolidColor(colorResource(R.color.primary)),
                    decorationBox = { inner ->
                        if (searchText.isEmpty()) {
                            Text(
                                text = stringResource(R.string.search_hint),
                                color = colorResource(R.color.text_secondary),
                                fontSize = 13.sp,
                            )
                        }
                        inner()
                    },
                )
            }
        }

        Row(
            modifier = Modifier
                .horizontalScroll(rememberScrollState())
                .padding(horizontal = 12.dp, vertical = 10.dp),
            horizontalArrangement = Arrangement.spacedBy(6.dp),
        ) {
            FilterChip(
                selected = selectedCategoryId == null,
                onClick = {
                    selectedCategoryId = null
                    viewModel.applyCategory(null)
                },
                label = { Text(stringResource(R.string.category_all)) },
                modifier = Modifier.height(36.dp),
                colors = FilterChipDefaults.filterChipColors(
                    selectedContainerColor = colorResource(R.color.primary),
                    selectedLabelColor = colorResource(R.color.on_primary),
                ),
            )
            if (categoriesResult is Resource.Success) {
                val cats = (categoriesResult as Resource.Success).data
                cats.forEach { cat ->
                    FilterChip(
                        selected = selectedCategoryId == cat.id,
                        onClick = {
                            selectedCategoryId = cat.id
                            viewModel.applyCategory(cat.id)
                        },
                        label = {
                            Text(
                                text = cat.name,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                            )
                        },
                        modifier = Modifier.height(36.dp),
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = colorResource(R.color.primary),
                            selectedLabelColor = colorResource(R.color.on_primary),
                        ),
                    )
                }
            }
        }

        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Text(
                text = stringResource(R.string.popular_picks),
                modifier = Modifier.weight(1f),
                color = colorResource(R.color.text_primary),
                fontSize = 15.sp,
                fontWeight = FontWeight.Bold,
            )
            Box {
                Text(
                    text = stringResource(R.string.sort_by_label_format, sortChoice.label),
                    modifier = Modifier.clickable { sortMenuExpanded = true },
                    color = colorResource(R.color.text_secondary),
                    fontSize = 13.sp,
                )
                DropdownMenu(
                    expanded = sortMenuExpanded,
                    onDismissRequest = { sortMenuExpanded = false },
                ) {
                    sortOptions.forEach { opt ->
                        DropdownMenuItem(
                            text = { Text(opt.label) },
                            onClick = {
                                sortChoice = opt
                                sortMenuExpanded = false
                                viewModel.applySort(opt.sortBy, opt.sortDir)
                            },
                        )
                    }
                }
            }
        }

        Box(
            modifier = Modifier
                .weight(1f)
                .fillMaxWidth(),
        ) {
            val initialLoading = productsResult is Resource.Loading && rows.isEmpty()
            if (initialLoading) {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            } else {
                LazyVerticalGrid(
                    columns = GridCells.Fixed(2),
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = androidx.compose.foundation.layout.PaddingValues(
                        horizontal = 10.dp,
                        vertical = 8.dp,
                    ),
                ) {
                    items(
                        items = rows,
                        key = { ProductCatalogRows.rowKey(it) },
                        span = { item ->
                            when (item) {
                                is ProductCatalogRows.RowModel.GridProduct -> GridItemSpan(1)
                                else -> GridItemSpan(2)
                            }
                        },
                    ) { row ->
                        when (row) {
                            is ProductCatalogRows.RowModel.Header -> SectionHeader(row.title)
                            is ProductCatalogRows.RowModel.GridProduct ->
                                ProductGridCard(row.product, onOpenProduct)
                            is ProductCatalogRows.RowModel.ListProduct ->
                                ProductListRowCard(row.product, onOpenProduct)
                        }
                    }
                }
            }
            if (productsResult is Resource.Loading && rows.isNotEmpty()) {
                CircularProgressIndicator(
                    modifier = Modifier
                        .align(Alignment.TopCenter)
                        .padding(top = 8.dp)
                        .size(28.dp),
                )
            }
        }
    }
}

@Composable
private fun SectionHeader(title: String) {
    Text(
        text = title,
        modifier = Modifier
            .fillMaxWidth()
            .padding(start = 16.dp, end = 16.dp, top = 12.dp, bottom = 8.dp),
        color = colorResource(R.color.text_secondary),
        fontSize = 13.sp,
        fontWeight = FontWeight.Bold,
    )
}

@Composable
private fun ProductRatingRow(rating: Float, reviewCount: Int) {
    RatingStarsRow(
        rating = rating,
        modifier = Modifier
            .fillMaxWidth()
            .padding(top = 4.dp),
        trailingText = "($reviewCount)",
    )
}

// Fixed height keeps rows aligned in LazyVerticalGrid; tall enough for 2-line title + rating + price.
private val ProductGridCardHeight = 318.dp

@Composable
private fun ProductGridCard(product: Product, onClick: (Product) -> Unit) {
    Card(
        onClick = { onClick(product) },
        modifier = Modifier
            .fillMaxWidth()
            .height(ProductGridCardHeight)
            .padding(6.dp),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        border = BorderStroke(1.dp, colorResource(R.color.divider)),
    ) {
        Column(
            modifier = Modifier.fillMaxSize(),
        ) {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(128.dp),
            ) {
                ProductThumb(
                    product = product,
                    modifier = Modifier.fillMaxSize(),
                    contentScale = ContentScale.Crop,
                )
                if (product.hasArTryOn()) {
                    Card(
                        modifier = Modifier
                            .align(Alignment.TopEnd)
                            .padding(7.dp),
                        shape = RoundedCornerShape(50),
                        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.primary)),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                    ) {
                        Text(
                            text = "● AR",
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                            color = colorResource(R.color.on_primary),
                            fontSize = 10.sp,
                            fontWeight = FontWeight.Bold,
                        )
                    }
                }
            }
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .weight(1f)
                    .padding(horizontal = 10.dp, vertical = 8.dp),
            ) {
                if (!product.brand.isNullOrEmpty()) {
                    Text(
                        text = product.brand.uppercase(),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 9.sp,
                        letterSpacing = 0.08.sp,
                        lineHeight = 12.sp,
                    )
                }
                Text(
                    text = product.name,
                    modifier = Modifier.padding(top = if (product.brand.isNullOrEmpty()) 0.dp else 4.dp),
                    color = colorResource(R.color.text_primary),
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Bold,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                    lineHeight = 17.sp,
                )
                val rating = product.averageRating
                val count = product.reviewsCount
                if (rating != null && count != null) {
                    ProductRatingRow(rating = rating, reviewCount = count)
                }
                Spacer(modifier = Modifier.weight(1f))
                Text(
                    text = formatDisplayPriceLine(product),
                    modifier = Modifier.padding(top = 4.dp),
                    color = colorResource(R.color.price_color),
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                    lineHeight = 18.sp,
                )
            }
        }
    }
}

@Composable
private fun ProductListRowCard(product: Product, onClick: (Product) -> Unit) {
    Card(
        onClick = { onClick(product) },
        modifier = Modifier
            .fillMaxWidth()
            .padding(start = 10.dp, end = 10.dp, top = 4.dp, bottom = 4.dp),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        border = BorderStroke(1.dp, colorResource(R.color.divider)),
    ) {
        Row(
            modifier = Modifier.padding(10.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            ProductThumb(
                product = product,
                modifier = Modifier.size(56.dp),
                contentScale = ContentScale.Crop,
            )
            Column(
                modifier = Modifier
                    .weight(1f)
                    .padding(start = 10.dp),
            ) {
                if (!product.brand.isNullOrBlank()) {
                    Text(
                        text = product.brand.uppercase(),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 9.sp,
                        letterSpacing = 0.08.sp,
                    )
                }
                Text(
                    text = product.name,
                    color = colorResource(R.color.text_primary),
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
                Text(
                    text = formatDisplayPriceLine(product),
                    modifier = Modifier.padding(top = 2.dp),
                    color = colorResource(R.color.price_color),
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                )
            }
            Text(
                text = "›",
                color = colorResource(R.color.text_secondary),
                fontSize = 20.sp,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(start = 8.dp),
            )
        }
    }
}

@Composable
private fun formatDisplayPriceLine(product: Product): String {
    val base = "₱${formatPriceNumber(product.price)}"
    val variantCount = product.variants?.size ?: 0
    return if (variantCount > 1) {
        stringResource(R.string.price_from_format, base)
    } else {
        base
    }
}

private fun formatPriceNumber(price: String): String =
    try {
        String.format("%,.0f", price.toDouble())
    } catch (_: NumberFormatException) {
        price
    }

@Composable
private fun ProductThumb(
    product: Product,
    modifier: Modifier = Modifier,
    contentScale: ContentScale,
) {
    val context = LocalContext.current
    val rawUrl = product.images?.firstOrNull()?.imageUrl
    val full = BackendImageUrl.resolve(context, rawUrl)
    val cacheKey = "${product.id}_${product.updatedAt}_${rawUrl.orEmpty()}"
    AndroidView(
        factory = { ctx ->
            ImageView(ctx).apply {
                scaleType = ImageView.ScaleType.CENTER_CROP
                setBackgroundResource(R.drawable.bg_product_placeholder)
                contentDescription = context.getString(R.string.product_image)
            }
        },
        modifier = modifier.clip(RoundedCornerShape(0.dp)),
        update = { iv ->
            if (full == null) {
                iv.setImageResource(R.drawable.bg_product_placeholder)
            } else {
                Glide.with(iv)
                    .load(full)
                    .signature(ObjectKey(cacheKey))
                    .placeholder(R.drawable.bg_product_placeholder)
                    .error(R.drawable.bg_product_placeholder)
                    .centerCrop()
                    .into(iv)
            }
        },
    )
}

private fun previewProductForGrid(
    withAr: Boolean = false,
    longTitle: Boolean = false,
): Product {
    val defaultVar = ProductVariant(
        id = 1,
        productId = 1,
        sku = null,
        color = null,
        frameSize = null,
        material = null,
        lensType = null,
        power = null,
        duration = null,
        baseCurve = null,
        diameter = null,
        price = null,
        stockQuantity = null,
        isDefault = true,
        arModelUrl = if (withAr) "https://example.com/model.bin" else null,
        unitPrice = null,
        images = null,
    )
    return Product(
        id = 1,
        category = ProductCategory(
            id = 1,
            name = "Sunglasses",
            slug = "sunglasses",
            description = null,
            createdAt = "",
            updatedAt = "",
            hasArSupport = withAr,
        ),
        name = if (longTitle) {
            "Polarized UV Protection Sunglasses Classic Aviator Style Full Rim"
        } else {
            "Classic Round Metal Frame"
        },
        description = null,
        price = "1500",
        brand = "BOLON",
        images = null,
        defaultVariant = defaultVar,
        variants = null,
        averageRating = 4.2f,
        reviewsCount = 18,
        createdAt = "2024-01-01T00:00:00Z",
        updatedAt = "2024-01-01T00:00:00Z",
    )
}

@Preview(showBackground = true, name = "Product grid card")
@Composable
private fun ProductGridCardPreview() {
    EyeCareTheme {
        ProductGridCard(
            product = previewProductForGrid(withAr = false, longTitle = false),
            onClick = {},
        )
    }
}

@Preview(showBackground = true, name = "Product grid card + AR")
@Composable
private fun ProductGridCardWithArPreview() {
    EyeCareTheme {
        ProductGridCard(
            product = previewProductForGrid(withAr = true, longTitle = false),
            onClick = {},
        )
    }
}

@Preview(showBackground = true, name = "Product grid card — long title")
@Composable
private fun ProductGridCardLongTitlePreview() {
    EyeCareTheme {
        ProductGridCard(
            product = previewProductForGrid(withAr = false, longTitle = true),
            onClick = {},
        )
    }
}

@Preview(showBackground = true, name = "Rating row")
@Composable
private fun ProductRatingRowPreview() {
    EyeCareTheme {
        ProductRatingRow(rating = 4.2f, reviewCount = 18)
    }
}
