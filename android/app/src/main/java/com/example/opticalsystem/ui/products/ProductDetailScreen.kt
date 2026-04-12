@file:OptIn(
    androidx.compose.foundation.ExperimentalFoundationApi::class,
    androidx.compose.material3.ExperimentalMaterial3Api::class,
)

package com.example.opticalsystem.ui.products

import android.widget.ImageView
import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.outlined.StarBorder
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.runtime.livedata.observeAsState
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.appcompat.widget.AppCompatRatingBar
import com.bumptech.glide.Glide
import com.bumptech.glide.signature.ObjectKey
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Feedback
import com.example.opticalsystem.data.model.FeedbackListResponse
import com.example.opticalsystem.data.model.Product
import com.example.opticalsystem.data.model.ProductCategory
import com.example.opticalsystem.data.model.ProductImage
import com.example.opticalsystem.data.model.ProductVariant
import com.example.opticalsystem.data.model.displayUnitPrice
import com.example.opticalsystem.data.model.hasArTryOn
import com.example.opticalsystem.data.model.selectableVariants
import com.example.opticalsystem.util.BackendImageUrl
import com.example.opticalsystem.util.Resource
import kotlinx.coroutines.launch

@Composable
fun ProductDetailScreen(
    productId: Int,
    viewModel: ProductDetailViewModel,
    onBack: () -> Unit,
    onOpenCart: () -> Unit,
) {
    val context = LocalContext.current
    val snackbarHostState = remember { SnackbarHostState() }
    val scope = rememberCoroutineScope()

    val productResult by viewModel.product.observeAsState()
    val cartMessage by viewModel.cartMessage.observeAsState()
    val feedbacksResult by viewModel.feedbacks.observeAsState()
    val submitFeedback by viewModel.submitFeedback.observeAsState()
    val deleteReview by viewModel.deleteReview.observeAsState()
    val myFeedback by viewModel.myFeedback.observeAsState()
    val canReview by viewModel.canReview.observeAsState(initial = false)
    val isInWishlist by viewModel.isInWishlist.observeAsState(initial = false)
    val cartCount by viewModel.cartItemCount.collectAsStateWithLifecycle()

    LaunchedEffect(productId) {
        viewModel.loadProduct(productId)
        viewModel.loadFeedbacks(productId)
    }

    LaunchedEffect(cartMessage) {
        val m = cartMessage ?: return@LaunchedEffect
        snackbarHostState.showSnackbar(m)
        viewModel.clearCartMessage()
    }

    var pendingUpdateReview by remember { mutableStateOf(false) }

    LaunchedEffect(submitFeedback) {
        when (val r = submitFeedback) {
            is Resource.Success -> {
                val msgRes = if (pendingUpdateReview) {
                    R.string.review_update_success
                } else {
                    R.string.review_submit_success
                }
                snackbarHostState.showSnackbar(context.getString(msgRes))
                pendingUpdateReview = false
                viewModel.loadFeedbacks(productId)
                viewModel.loadProduct(productId)
                viewModel.clearSubmitFeedbackResult()
            }
            is Resource.Error -> {
                Toast.makeText(context, r.message, Toast.LENGTH_LONG).show()
                viewModel.clearSubmitFeedbackResult()
            }
            else -> Unit
        }
    }

    LaunchedEffect(deleteReview) {
        when (val r = deleteReview) {
            is Resource.Success -> {
                snackbarHostState.showSnackbar(context.getString(R.string.review_deleted))
                viewModel.clearDeleteReviewResult()
            }
            is Resource.Error -> {
                Toast.makeText(context, r.message, Toast.LENGTH_LONG).show()
                viewModel.clearDeleteReviewResult()
            }
            else -> Unit
        }
    }

    LaunchedEffect((feedbacksResult as? Resource.Error)?.message) {
        val msg = (feedbacksResult as? Resource.Error)?.message ?: return@LaunchedEffect
        Toast.makeText(context, msg, Toast.LENGTH_LONG).show()
    }

    var showDeleteDialog by remember { mutableStateOf(false) }

    if (showDeleteDialog) {
        val fb = myFeedback
        AlertDialog(
            onDismissRequest = { showDeleteDialog = false },
            title = { Text(stringResource(R.string.review_delete_confirm_title)) },
            text = { Text(stringResource(R.string.review_delete_confirm_message)) },
            confirmButton = {
                TextButton(
                    onClick = {
                        showDeleteDialog = false
                        if (fb != null) {
                            viewModel.deleteReview(feedbackId = fb.id, productId = productId)
                        }
                    },
                ) {
                    Text(stringResource(R.string.delete_review))
                }
            },
            dismissButton = {
                TextButton(onClick = { showDeleteDialog = false }) {
                    Text(stringResource(R.string.action_cancel))
                }
            },
        )
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .background(colorResource(R.color.background)),
        ) {
            DetailToolbar(
                onBack = onBack,
                onWishlist = {
                    val p = (productResult as? Resource.Success)?.data ?: return@DetailToolbar
                    viewModel.toggleWishlist(p)
                    val msgRes = if (isInWishlist) R.string.wishlist_removed else R.string.wishlist_added
                    scope.launch {
                        snackbarHostState.showSnackbar(context.getString(msgRes))
                    }
                },
                onCart = onOpenCart,
                wishlistFilled = isInWishlist,
                cartCount = cartCount,
            )

            when (val result = productResult) {
                is Resource.Loading -> {
                    Box(
                        modifier = Modifier
                            .weight(1f)
                            .fillMaxWidth(),
                        contentAlignment = Alignment.Center,
                    ) {
                        CircularProgressIndicator()
                    }
                }
                is Resource.Error -> {
                    LaunchedEffect(result) {
                        Toast.makeText(context, result.message, Toast.LENGTH_LONG).show()
                    }
                    Box(
                        modifier = Modifier
                            .weight(1f)
                            .fillMaxWidth(),
                        contentAlignment = Alignment.Center,
                    ) {
                        Text(text = result.message, color = colorResource(R.color.text_secondary))
                    }
                }
                is Resource.Success -> {
                    val product = result.data
                    var selectedVariant by remember(product.id, product.updatedAt) {
                        val variants = product.selectableVariants()
                        val hasSwatches = hasColorVariants(product, variants)
                        mutableStateOf(
                            if (hasSwatches) null else (product.defaultVariant ?: variants.firstOrNull()),
                        )
                    }

                    var writeRating by remember(myFeedback?.id, myFeedback?.rating) {
                        mutableStateOf(myFeedback?.rating ?: 0)
                    }
                    var reviewComment by remember(myFeedback?.id, myFeedback?.comment) {
                        mutableStateOf(myFeedback?.comment.orEmpty())
                    }

                    LaunchedEffect(myFeedback) {
                        writeRating = myFeedback?.rating ?: 0
                        reviewComment = myFeedback?.comment.orEmpty()
                    }

                    Column(modifier = Modifier.weight(1f)) {
                        Column(
                            modifier = Modifier
                                .weight(1f)
                                .verticalScroll(rememberScrollState()),
                        ) {
                            ImageGalleryCard(product = product)
                            ProductInfoBlock(
                                product = product,
                                selectedVariant = selectedVariant,
                                onVariantSelected = { selectedVariant = it },
                            )
                            SpecCard(
                                product = product,
                                chosenVariant = selectedVariant,
                            )
                            ReviewsCard(
                                feedbacksResult = feedbacksResult,
                                canReview = canReview == true,
                                myFeedback = myFeedback,
                                writeRating = writeRating,
                                onWriteRatingChange = { writeRating = it },
                                reviewComment = reviewComment,
                                onReviewCommentChange = { reviewComment = it },
                                submitLoading = submitFeedback is Resource.Loading,
                                deleteLoading = deleteReview is Resource.Loading,
                                onSubmitReview = {
                                    if (canReview != true) {
                                        Toast.makeText(
                                            context,
                                            context.getString(R.string.review_requires_completed_order),
                                            Toast.LENGTH_LONG,
                                        ).show()
                                        return@ReviewsCard
                                    }
                                    if (writeRating < 1) {
                                        Toast.makeText(
                                            context,
                                            context.getString(R.string.review_pick_rating_first),
                                            Toast.LENGTH_SHORT,
                                        ).show()
                                        return@ReviewsCard
                                    }
                                    val c = reviewComment.trim().takeIf { it.isNotBlank() }
                                    pendingUpdateReview = myFeedback != null
                                    viewModel.saveReview(productId, writeRating, c)
                                },
                                onRequestDelete = { showDeleteDialog = true },
                            )
                            Spacer(modifier = Modifier.height(8.dp))
                        }

                        val addEnabled = addToCartEnabled(product, selectedVariant)
                        OutlinedButton(
                            onClick = {
                                if (requiresColorSelection(product) && selectedVariant == null) {
                                    scope.launch {
                                        snackbarHostState.showSnackbar(
                                            context.getString(R.string.select_in_stock_color_first),
                                        )
                                    }
                                    return@OutlinedButton
                                }
                                viewModel.addToCart(product, 1, selectedVariant)
                            },
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp)
                                .height(52.dp),
                            enabled = addEnabled,
                            shape = RoundedCornerShape(26.dp),
                            border = BorderStroke(1.5.dp, colorResource(R.color.primary)),
                            colors = ButtonDefaults.outlinedButtonColors(
                                contentColor = colorResource(R.color.primary),
                            ),
                        ) {
                            Text(stringResource(R.string.add_to_order))
                        }
                    }
                }
                null -> {
                    Box(
                        modifier = Modifier
                            .weight(1f)
                            .fillMaxWidth(),
                        contentAlignment = Alignment.Center,
                    ) {
                        CircularProgressIndicator()
                    }
                }
            }
        }
    }
}

@Composable
private fun DetailToolbar(
    onBack: () -> Unit,
    onWishlist: () -> Unit,
    onCart: () -> Unit,
    wishlistFilled: Boolean,
    cartCount: Int,
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .background(colorResource(R.color.primary))
            .padding(start = 4.dp, end = 8.dp, top = 12.dp, bottom = 12.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        IconButton(onClick = onBack, modifier = Modifier.size(40.dp)) {
            Icon(
                painter = painterResource(R.drawable.ic_back_24),
                contentDescription = stringResource(R.string.back),
                tint = colorResource(R.color.on_primary),
            )
        }
        Text(
            text = stringResource(R.string.product_detail_title),
            modifier = Modifier.weight(1f),
            color = colorResource(R.color.on_primary),
            fontSize = 17.sp,
            fontWeight = FontWeight.Bold,
            textAlign = androidx.compose.ui.text.style.TextAlign.Center,
            maxLines = 1,
        )
        IconButton(onClick = onWishlist, modifier = Modifier.size(40.dp)) {
            Icon(
                painter = painterResource(
                    if (wishlistFilled) R.drawable.ic_heart_filled_24 else R.drawable.ic_heart_24,
                ),
                contentDescription = stringResource(
                    if (wishlistFilled) R.string.remove_from_wishlist else R.string.save_to_wishlist,
                ),
                tint = colorResource(R.color.on_primary),
            )
        }
        Box {
            IconButton(onClick = onCart, modifier = Modifier.size(40.dp)) {
                Icon(
                    painter = painterResource(R.drawable.ic_cart_24),
                    contentDescription = stringResource(R.string.nav_cart),
                    tint = colorResource(R.color.on_primary),
                )
            }
            if (cartCount > 0) {
                val badge = cartCount.coerceAtMost(99)
                Box(
                    modifier = Modifier
                        .align(Alignment.TopEnd)
                        .size(18.dp)
                        .clip(CircleShape)
                        .background(colorResource(R.color.nav_badge_background)),
                    contentAlignment = Alignment.Center,
                ) {
                    Text(
                        text = if (cartCount > 99) "99+" else badge.toString(),
                        color = Color.White,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold,
                    )
                }
            }
        }
    }
}

@Composable
private fun ImageGalleryCard(product: Product) {
    val effectiveImages = (product.images ?: emptyList()).ifEmpty {
        listOf(ProductImage(id = 0, imageUrl = "", sortOrder = 0, createdAt = ""))
    }
    val pagerState = rememberPagerState(pageCount = { effectiveImages.size })

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 16.dp),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 3.dp),
        border = BorderStroke(1.dp, colorResource(R.color.divider)),
    ) {
        Column {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(220.dp),
            ) {
                HorizontalPager(
                    state = pagerState,
                    modifier = Modifier.fillMaxSize(),
                ) { page ->
                    DetailGalleryImage(image = effectiveImages[page])
                }
                if (product.hasArTryOn()) {
                    Card(
                        modifier = Modifier
                            .align(Alignment.TopEnd)
                            .padding(10.dp),
                        shape = RoundedCornerShape(50),
                        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.primary)),
                        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                    ) {
                        Text(
                            text = stringResource(R.string.ar_try_on),
                            modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp),
                            color = colorResource(R.color.on_primary),
                            fontSize = 11.sp,
                            fontWeight = FontWeight.Bold,
                        )
                    }
                }
            }
            if (effectiveImages.size > 1) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 10.dp),
                    horizontalArrangement = Arrangement.Center,
                ) {
                    repeat(effectiveImages.size) { i ->
                        val active = i == pagerState.currentPage
                        Box(
                            modifier = Modifier
                                .padding(horizontal = 4.dp)
                                .size(8.dp)
                                .clip(CircleShape)
                                .background(
                                    if (active) colorResource(R.color.primary)
                                    else colorResource(R.color.divider),
                                ),
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun DetailGalleryImage(image: ProductImage) {
    val context = LocalContext.current
    val full = BackendImageUrl.resolve(context, image.imageUrl)
    val cacheKey = "${image.id}_${image.createdAt}_${image.sortOrder}_${image.imageUrl}"
    AndroidView(
        factory = { ctx ->
            ImageView(ctx).apply {
                scaleType = ImageView.ScaleType.CENTER_CROP
                setBackgroundResource(R.drawable.bg_product_placeholder)
            }
        },
        modifier = Modifier.fillMaxSize(),
        update = { iv ->
            if (full.isNullOrBlank()) {
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

@Composable
private fun ProductInfoBlock(
    product: Product,
    selectedVariant: ProductVariant?,
    onVariantSelected: (ProductVariant) -> Unit,
) {
    val variants = product.selectableVariants()
    val hasSwatches = hasColorVariants(product, variants)

    Column(modifier = Modifier.padding(horizontal = 20.dp, vertical = 16.dp)) {
        if (product.category != null) {
            Text(
                text = product.category.name,
                color = colorResource(R.color.category_label_color),
                fontSize = 12.sp,
                fontWeight = FontWeight.Bold,
                letterSpacing = 0.05.sp,
            )
        }
        if (!product.brand.isNullOrBlank()) {
            Text(
                text = product.brand.uppercase(),
                modifier = Modifier.padding(top = 2.dp),
                color = colorResource(R.color.text_secondary),
                fontSize = 12.sp,
                letterSpacing = 0.08.sp,
            )
        }
        Text(
            text = product.name,
            modifier = Modifier.padding(top = 4.dp),
            color = colorResource(R.color.text_primary),
            fontSize = 22.sp,
            fontWeight = FontWeight.Bold,
            lineHeight = 26.sp,
        )

        val rating = product.averageRating
        val count = product.reviewsCount
        if (rating != null && count != null) {
            Row(
                modifier = Modifier.padding(top = 6.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                AndroidView(
                    factory = { ctx ->
                        AppCompatRatingBar(ctx).apply {
                            setIsIndicator(true)
                            numStars = 5
                            stepSize = 0.1f
                            this.rating = rating
                        }
                    },
                    modifier = Modifier.height(18.dp),
                )
                Text(
                    text = "$rating ($count reviews)",
                    modifier = Modifier.padding(start = 6.dp),
                    color = colorResource(R.color.text_secondary),
                    fontSize = 12.sp,
                )
            }
        }

        val priceText = if (hasSwatches && selectedVariant == null) {
            stringResource(R.string.select_color_to_see_price)
        } else {
            val basePrice = selectedVariant?.displayUnitPrice(product) ?: product.price
            "₱${formatDetailPrice(basePrice)}"
        }
        Text(
            text = priceText,
            modifier = Modifier.padding(top = 10.dp),
            color = colorResource(R.color.price_color),
            fontSize = 26.sp,
            fontWeight = FontWeight.Bold,
        )

        if (hasSwatches) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .horizontalScroll(rememberScrollState())
                    .padding(top = 12.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                variants.forEach { variant ->
                    val colorName = variant.color?.trim()?.takeIf { it.isNotEmpty() } ?: return@forEach
                    val inStock = (variant.stockQuantity ?: 0) > 0
                    val selected = selectedVariant?.id == variant.id
                    ColorSwatchChip(
                        label = colorName,
                        inStock = inStock,
                        selected = selected,
                        onClick = { if (inStock) onVariantSelected(variant) },
                    )
                }
            }
        }

        if (!product.description.isNullOrBlank()) {
            Text(
                text = product.description,
                modifier = Modifier.padding(top = 12.dp),
                color = colorResource(R.color.text_secondary),
                fontSize = 13.sp,
                lineHeight = 20.sp,
            )
        }
    }
}

@Composable
private fun ColorSwatchChip(
    label: String,
    inStock: Boolean,
    selected: Boolean,
    onClick: () -> Unit,
) {
    val borderColor = if (selected) colorResource(R.color.primary) else colorResource(R.color.divider)
    Card(
        onClick = onClick,
        enabled = inStock,
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        border = BorderStroke(1.dp, borderColor),
    ) {
        Text(
            text = label,
            modifier = Modifier
                .padding(horizontal = 12.dp, vertical = 8.dp)
                .alpha(if (inStock) 1f else 0.45f),
            color = colorResource(R.color.text_primary),
            fontSize = 12.sp,
            fontWeight = if (selected) FontWeight.Bold else FontWeight.Normal,
            textDecoration = if (!inStock) TextDecoration.LineThrough else null,
        )
    }
}

@Composable
private fun SpecCard(product: Product, chosenVariant: ProductVariant?) {
    val rows = buildSpecRows(product, chosenVariant)
    if (rows.isEmpty()) return
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 8.dp),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        border = BorderStroke(1.dp, colorResource(R.color.divider)),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = stringResource(R.string.specifications),
                color = colorResource(R.color.text_primary),
                fontSize = 15.sp,
                fontWeight = FontWeight.Bold,
            )
            Spacer(modifier = Modifier.height(12.dp))
            rows.forEachIndexed { index, pair ->
                if (index > 0) {
                    HorizontalDivider(
                        modifier = Modifier.padding(vertical = 4.dp),
                        color = colorResource(R.color.divider),
                    )
                }
                Row(modifier = Modifier.fillMaxWidth()) {
                    Text(
                        text = stringResource(pair.first),
                        modifier = Modifier.weight(1f),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 14.sp,
                    )
                    Text(
                        text = pair.second,
                        color = colorResource(R.color.text_primary),
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Medium,
                    )
                }
            }
        }
    }
}

@Composable
@Suppress("LongParameterList")
private fun ReviewsCard(
    feedbacksResult: Resource<FeedbackListResponse>?,
    canReview: Boolean,
    myFeedback: Feedback?,
    writeRating: Int,
    onWriteRatingChange: (Int) -> Unit,
    reviewComment: String,
    onReviewCommentChange: (String) -> Unit,
    submitLoading: Boolean,
    deleteLoading: Boolean,
    onSubmitReview: () -> Unit,
    onRequestDelete: () -> Unit,
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 8.dp),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        border = BorderStroke(1.dp, colorResource(R.color.divider)),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = stringResource(R.string.ratings_and_reviews),
                color = colorResource(R.color.text_primary),
                fontSize = 15.sp,
                fontWeight = FontWeight.Bold,
            )
            Spacer(modifier = Modifier.height(10.dp))

            if (canReview) {
                Text(
                    text = stringResource(R.string.write_a_review),
                    color = colorResource(R.color.text_secondary),
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Bold,
                )
                Spacer(modifier = Modifier.height(6.dp))
                Row {
                    for (i in 1..5) {
                        IconButton(
                            onClick = { onWriteRatingChange(i) },
                            modifier = Modifier.size(36.dp),
                        ) {
                            Icon(
                                imageVector = if (i <= writeRating) Icons.Filled.Star else Icons.Outlined.StarBorder,
                                contentDescription = "Rating $i of 5",
                                tint = colorResource(R.color.star_color),
                            )
                        }
                    }
                }
                OutlinedTextField(
                    value = reviewComment,
                    onValueChange = onReviewCommentChange,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 10.dp),
                    label = { Text(stringResource(R.string.review_comment_optional)) },
                    minLines = 3,
                )
                ReviewStatusText(myFeedback)
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 8.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    OutlinedButton(
                        onClick = onSubmitReview,
                        modifier = Modifier
                            .weight(1f)
                            .height(48.dp),
                        enabled = !submitLoading && writeRating >= 1,
                        border = BorderStroke(1.5.dp, colorResource(R.color.primary)),
                        colors = ButtonDefaults.outlinedButtonColors(
                            contentColor = colorResource(R.color.primary),
                        ),
                    ) {
                        Text(
                            text = stringResource(
                                if (myFeedback != null) R.string.update_review else R.string.submit_review,
                            ),
                        )
                    }
                    if (myFeedback != null) {
                        TextButton(
                            onClick = onRequestDelete,
                            modifier = Modifier.padding(start = 8.dp),
                            enabled = !deleteLoading,
                        ) {
                            Text(stringResource(R.string.delete_review))
                        }
                    }
                }
                if (submitLoading) {
                    CircularProgressIndicator(
                        modifier = Modifier
                            .padding(top = 10.dp)
                            .align(Alignment.CenterHorizontally)
                            .size(28.dp),
                    )
                }
            }

            HorizontalDivider(
                modifier = Modifier.padding(top = 14.dp, bottom = 12.dp),
                color = colorResource(R.color.divider),
            )

            when (val fr = feedbacksResult) {
                is Resource.Loading -> {
                    CircularProgressIndicator(
                        modifier = Modifier.align(Alignment.CenterHorizontally),
                    )
                }
                is Resource.Success -> {
                    val list = fr.data.data
                    val avg = fr.data.averageRating
                    val total = fr.data.meta?.total ?: list.size
                    if (avg != null && total > 0) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            AndroidView(
                                factory = { ctx ->
                                    AppCompatRatingBar(ctx).apply {
                                        setIsIndicator(true)
                                        numStars = 5
                                        stepSize = 0.1f
                                        rating = avg
                                    }
                                },
                                modifier = Modifier.height(18.dp),
                            )
                            Text(
                                text = "$avg ($total reviews)",
                                modifier = Modifier.padding(start = 6.dp),
                                color = colorResource(R.color.text_secondary),
                                fontSize = 12.sp,
                            )
                        }
                        Spacer(modifier = Modifier.height(8.dp))
                    }
                    if (list.isEmpty()) {
                        Text(
                            text = stringResource(R.string.no_reviews),
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(8.dp),
                            color = colorResource(R.color.text_secondary),
                            fontSize = 13.sp,
                        )
                    } else {
                        list.forEach { fb -> FeedbackRow(fb) }
                    }
                }
                is Resource.Error -> {
                    Text(
                        text = fr.message,
                        modifier = Modifier.padding(8.dp),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 13.sp,
                    )
                }
                null -> Unit
            }
        }
    }
}

@Composable
private fun ReviewStatusText(myFeedback: Feedback?) {
    val fb = myFeedback ?: return
    val text: String? = when {
        fb.approvalStatus?.equals("rejected", ignoreCase = true) == true -> {
            val base = stringResource(R.string.review_status_rejected)
            val reason = fb.rejectionReason?.trim().orEmpty()
            if (reason.isNotEmpty()) "$base\n$reason" else base
        }
        fb.approvalStatus?.equals("pending", ignoreCase = true) == true ->
            stringResource(R.string.review_status_pending)
        !fb.isVisible ->
            fb.hiddenFromPublicMessage?.trim()?.takeIf { it.isNotEmpty() }
                ?: stringResource(R.string.review_hidden_by_moderator)
        else -> null
    }
    if (text != null) {
        Text(
            text = text,
            modifier = Modifier.padding(top = 8.dp),
            color = colorResource(R.color.text_secondary),
            fontSize = 12.sp,
        )
    }
}

@Composable
private fun FeedbackRow(feedback: Feedback) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 8.dp),
    ) {
        Text(
            text = feedback.user?.name ?: "Anonymous",
            color = colorResource(R.color.text_primary),
            fontWeight = FontWeight.Bold,
            fontSize = 14.sp,
        )
        AndroidView(
            factory = { ctx ->
                AppCompatRatingBar(ctx).apply {
                    setIsIndicator(true)
                    numStars = 5
                    stepSize = 1f
                    rating = feedback.rating.toFloat()
                }
            },
            modifier = Modifier.height(20.dp),
        )
        Text(
            text = feedback.comment?.takeIf { it.isNotBlank() } ?: "—",
            color = colorResource(R.color.text_secondary),
            fontSize = 14.sp,
        )
        val reply = feedback.adminReply?.takeIf { it.isNotBlank() }
        if (reply != null) {
            Text(
                text = stringResource(R.string.clinic_reply),
                modifier = Modifier.padding(top = 6.dp),
                color = colorResource(R.color.text_primary),
                fontWeight = FontWeight.Bold,
                fontSize = 12.sp,
            )
            Text(
                text = reply,
                color = colorResource(R.color.text_secondary),
                fontSize = 13.sp,
            )
        }
        Text(
            text = feedback.createdAt.take(10).ifBlank { "—" },
            modifier = Modifier.padding(top = 4.dp),
            color = colorResource(R.color.text_secondary),
            fontSize = 12.sp,
        )
    }
}

private fun buildSpecRows(product: Product, chosenVariant: ProductVariant?): List<Pair<Int, String>> {
    val result = mutableListOf<Pair<Int, String>>()
    val variant = chosenVariant ?: product.defaultVariant ?: product.selectableVariants().firstOrNull()
    val category = product.category

    fun addRow(labelRes: Int, value: String?) {
        val display = value?.trim()?.takeIf { it.isNotEmpty() } ?: return
        result.add(labelRes to display)
    }

    if (category != null && category.specFlagsKnown()) {
        if (category.hasColor == true) addRow(R.string.spec_color, variant?.color)
        if (category.hasFrameSize == true) addRow(R.string.spec_frame_size, variant?.frameSize)
        if (category.hasMaterial == true) addRow(R.string.spec_material, variant?.material)
        if (category.hasLensType == true) addRow(R.string.spec_lens_type, variant?.lensType)
        if (category.hasPowerField == true) {
            val powerValue = variant?.power ?: variant?.baseCurve
            val powerLabel = if (!variant?.power.isNullOrBlank()) {
                R.string.spec_power
            } else {
                R.string.spec_base_curve
            }
            addRow(powerLabel, powerValue)
        }
        if (category.hasDuration == true) {
            val durationValue = variant?.duration ?: variant?.diameter
            val durationLabel = if (!variant?.duration.isNullOrBlank()) {
                R.string.spec_duration
            } else {
                R.string.spec_diameter
            }
            addRow(durationLabel, durationValue)
        }
    } else {
        addRow(R.string.spec_color, variant?.color)
        addRow(R.string.spec_frame_size, variant?.frameSize)
        addRow(R.string.spec_material, variant?.material)
        addRow(R.string.spec_lens_type, variant?.lensType)
        if (!variant?.power.isNullOrBlank()) {
            addRow(R.string.spec_power, variant?.power)
        } else {
            addRow(R.string.spec_base_curve, variant?.baseCurve)
        }
        if (!variant?.duration.isNullOrBlank()) {
            addRow(R.string.spec_duration, variant?.duration)
        } else {
            addRow(R.string.spec_diameter, variant?.diameter)
        }
    }
    return result
}

private fun ProductCategory.specFlagsKnown(): Boolean =
    listOf(hasColor, hasFrameSize, hasMaterial, hasLensType, hasPowerField, hasDuration).any { it != null }

private fun hasColorVariants(product: Product, variants: List<ProductVariant>): Boolean {
    if (product.category?.hasColor != true) return false
    val colors = variants.mapNotNull { it.color?.trim()?.takeIf(String::isNotBlank) }.distinct()
    return colors.isNotEmpty()
}

private fun requiresColorSelection(product: Product): Boolean =
    hasColorVariants(product, product.selectableVariants())

private fun addToCartEnabled(product: Product, selectedVariant: ProductVariant?): Boolean {
    val needs = requiresColorSelection(product)
    return if (!needs) true else ((selectedVariant?.stockQuantity ?: 0) > 0)
}

private fun formatDetailPrice(price: String): String =
    try {
        String.format("%,.0f", price.toDouble())
    } catch (_: NumberFormatException) {
        price
    }
