package com.example.opticalsystem.ui.cart

import android.widget.ImageView
import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.bumptech.glide.Glide
import com.example.opticalsystem.R
import com.example.opticalsystem.data.local.CartItem
import com.example.opticalsystem.ui.theme.EyeCareTheme
import com.example.opticalsystem.util.BackendImageUrl

@Composable
fun CartScreen(
    viewModel: CartViewModel,
    onBack: () -> Unit,
    onCheckout: () -> Unit,
    onAddMoreItems: () -> Unit,
) {
    val context = LocalContext.current
    val cartItems by viewModel.cartItems.collectAsStateWithLifecycle()
    val itemCount by viewModel.itemCount.collectAsStateWithLifecycle()
    val totalPrice by viewModel.totalPrice.collectAsStateWithLifecycle()

    var itemPendingRemove by remember { mutableStateOf<CartItem?>(null) }

    itemPendingRemove?.let { item ->
        AlertDialog(
            onDismissRequest = { itemPendingRemove = null },
            title = { Text(stringResource(R.string.cart_remove_item_title)) },
            text = {
                Text(stringResource(R.string.cart_remove_item_message, item.productName))
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        viewModel.remove(item)
                        Toast.makeText(
                            context,
                            context.getString(R.string.cart_removed_format, item.productName),
                            Toast.LENGTH_SHORT,
                        ).show()
                        itemPendingRemove = null
                    },
                ) {
                    Text(stringResource(R.string.action_remove))
                }
            },
            dismissButton = {
                TextButton(onClick = { itemPendingRemove = null }) {
                    Text(stringResource(R.string.action_cancel))
                }
            },
        )
    }

    val requestRemove: (CartItem) -> Unit = { itemPendingRemove = it }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(colorResource(R.color.background)),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .background(colorResource(R.color.primary))
                .padding(start = 8.dp, end = 16.dp, top = 12.dp, bottom = 12.dp),
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
                text = stringResource(R.string.cart_title),
                modifier = Modifier
                    .weight(1f)
                    .padding(start = 4.dp),
                color = colorResource(R.color.on_primary),
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
            )
            Text(
                text = if (itemCount > 0) {
                    stringResource(R.string.cart_item_count_format, itemCount)
                } else {
                    ""
                },
                color = Color(0xB3FFFFFF),
                fontSize = 13.sp,
            )
        }

        val formattedTotal = remember(totalPrice) {
            "₱${String.format("%,.0f", totalPrice)}"
        }

        if (cartItems.isEmpty()) {
            Box(
                modifier = Modifier
                    .weight(1f)
                    .fillMaxWidth(),
                contentAlignment = Alignment.Center,
            ) {
                Column(
                    horizontalAlignment = Alignment.CenterHorizontally,
                    modifier = Modifier.padding(32.dp),
                ) {
                    Icon(
                        painter = painterResource(R.drawable.ic_cart_24),
                        contentDescription = stringResource(R.string.cart_empty_title),
                        modifier = Modifier.size(80.dp),
                        tint = colorResource(R.color.divider),
                    )
                    Text(
                        text = stringResource(R.string.cart_empty_title),
                        modifier = Modifier.padding(top = 16.dp),
                        color = colorResource(R.color.text_primary),
                        fontSize = 18.sp,
                        fontWeight = FontWeight.Bold,
                    )
                    Text(
                        text = stringResource(R.string.cart_empty_subtitle),
                        modifier = Modifier.padding(top = 8.dp),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 14.sp,
                        textAlign = TextAlign.Center,
                    )
                }
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .weight(1f)
                    .fillMaxWidth(),
                contentPadding = PaddingValues(top = 16.dp, bottom = 12.dp),
            ) {
                items(
                    items = cartItems,
                    key = { "${it.productId}_${it.productVariantId}" },
                ) { item ->
                    CartLineCard(
                        item = item,
                        onIncrease = { viewModel.increase(item) },
                        onDecrease = {
                            if (item.quantity > 1) {
                                viewModel.decrease(item)
                            } else {
                                requestRemove(item)
                            }
                        },
                        onRemove = { requestRemove(item) },
                    )
                }
            }
        }

        if (cartItems.isNotEmpty()) {
            CartCheckoutFooter(
                formattedTotal = formattedTotal,
                onCheckout = onCheckout,
                onAddMoreItems = onAddMoreItems,
            )
        }
    }
}

@Composable
private fun CartCheckoutFooter(
    formattedTotal: String,
    onCheckout: () -> Unit,
    onAddMoreItems: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(
            containerColor = colorResource(R.color.surface),
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 12.dp),
    ) {
        Column(modifier = Modifier.padding(20.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
            ) {
                Text(
                    text = stringResource(R.string.cart_total),
                    color = colorResource(R.color.text_primary),
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                )
                Text(
                    text = formattedTotal,
                    color = colorResource(R.color.price_color),
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                )
            }
            Text(
                text = stringResource(R.string.payment_due_visit_note),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 10.dp),
                color = colorResource(R.color.text_secondary),
                fontSize = 12.sp,
                textAlign = TextAlign.Center,
            )
            Button(
                onClick = onCheckout,
                modifier = Modifier
                    .fillMaxWidth()
                    .height(52.dp)
                    .padding(top = 16.dp),
                shape = RoundedCornerShape(14.dp),
                colors = ButtonDefaults.buttonColors(
                    containerColor = colorResource(R.color.primary),
                ),
            ) {
                Text(
                    text = stringResource(R.string.continue_label),
                    color = colorResource(R.color.on_primary),
                    fontSize = 15.sp,
                )
            }
            OutlinedButton(
                onClick = onAddMoreItems,
                modifier = Modifier
                    .fillMaxWidth()
                    .height(52.dp)
                    .padding(top = 8.dp),
                shape = RoundedCornerShape(14.dp),
            ) {
                Text(stringResource(R.string.add_more_items))
            }
        }
    }
}

@Composable
private fun CartLineCard(
    item: CartItem,
    onIncrease: () -> Unit,
    onDecrease: () -> Unit,
    onRemove: () -> Unit,
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp)
            .padding(bottom = 10.dp),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        border = BorderStroke(1.dp, colorResource(R.color.divider)),
    ) {
        Row(
            modifier = Modifier.padding(12.dp),
            verticalAlignment = Alignment.Top,
        ) {
            Box(
                modifier = Modifier
                    .size(80.dp)
                    .clip(RoundedCornerShape(10.dp))
                    .border(
                        1.dp,
                        colorResource(R.color.divider),
                        RoundedCornerShape(10.dp),
                    ),
            ) {
                GlideCartThumbnail(
                    imageUrl = item.productImageUrl,
                    modifier = Modifier.fillMaxSize(),
                )
            }
            Column(
                modifier = Modifier
                    .weight(1f)
                    .padding(start = 12.dp),
            ) {
                if (!item.productBrand.isNullOrBlank()) {
                    Text(
                        text = item.productBrand,
                        color = colorResource(R.color.category_label_color),
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold,
                    )
                }
                Text(
                    text = item.productName,
                    color = colorResource(R.color.text_primary),
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
                if (!item.variantLabel.isNullOrBlank()) {
                    Text(
                        text = item.variantLabel,
                        modifier = Modifier.padding(top = 2.dp),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 12.sp,
                    )
                }
                val unit = item.productPrice.toDoubleOrNull() ?: 0.0
                val lineTotal = unit * item.quantity
                Text(
                    text = "₱${String.format("%,.0f", lineTotal)}",
                    modifier = Modifier.padding(top = 4.dp),
                    color = colorResource(R.color.price_color),
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                )
                if (item.quantity > 1) {
                    Text(
                        text = stringResource(
                            R.string.cart_price_each_format,
                            "₱${formatCartUnitPrice(item.productPrice)}",
                        ),
                        modifier = Modifier.padding(top = 2.dp),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 12.sp,
                    )
                }
                Row(
                    modifier = Modifier.padding(top = 10.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    QuantityChip(onClick = onDecrease, contentDescriptionRes = R.string.cd_decrease_quantity) {
                        Icon(
                            painter = painterResource(R.drawable.ic_minus_16),
                            contentDescription = null,
                            modifier = Modifier.size(16.dp),
                        )
                    }
                    Text(
                        text = item.quantity.toString(),
                        modifier = Modifier.width(36.dp),
                        color = colorResource(R.color.text_primary),
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Bold,
                        textAlign = TextAlign.Center,
                    )
                    QuantityChip(onClick = onIncrease, contentDescriptionRes = R.string.cd_increase_quantity) {
                        Icon(
                            painter = painterResource(R.drawable.ic_plus_16),
                            contentDescription = null,
                            modifier = Modifier.size(16.dp),
                        )
                    }
                }
            }
            Box(
                modifier = Modifier
                    .size(36.dp)
                    .clickable(onClick = onRemove),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    painter = painterResource(R.drawable.ic_trash_24),
                    contentDescription = stringResource(R.string.action_remove),
                    modifier = Modifier.size(24.dp),
                    tint = colorResource(R.color.text_secondary),
                )
            }
        }
    }
}

@Composable
private fun QuantityChip(
    onClick: () -> Unit,
    contentDescriptionRes: Int,
    icon: @Composable () -> Unit,
) {
    val cd = stringResource(contentDescriptionRes)
    Surface(
        modifier = Modifier
            .size(30.dp)
            .clip(RoundedCornerShape(8.dp))
            .semantics { contentDescription = cd }
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(8.dp),
        color = colorResource(R.color.surface),
        border = BorderStroke(1.dp, colorResource(R.color.divider)),
    ) {
        Box(
            modifier = Modifier.fillMaxSize(),
            contentAlignment = Alignment.Center,
        ) {
            icon()
        }
    }
}

private fun formatCartUnitPrice(price: String): String =
    try {
        String.format("%,.0f", price.toDouble())
    } catch (_: NumberFormatException) {
        price
    }

@Composable
private fun GlideCartThumbnail(
    imageUrl: String?,
    modifier: Modifier = Modifier,
) {
    val context = LocalContext.current
    AndroidView(
        factory = { ctx ->
            ImageView(ctx).apply {
                scaleType = ImageView.ScaleType.CENTER_CROP
                contentDescription = ctx.getString(R.string.product_image)
            }
        },
        modifier = modifier.clip(RoundedCornerShape(10.dp)),
        update = { iv ->
            val full = BackendImageUrl.resolve(context, imageUrl)
            if (full == null) {
                iv.setImageResource(R.drawable.bg_product_placeholder)
            } else {
                Glide.with(iv)
                    .load(full)
                    .placeholder(R.drawable.bg_product_placeholder)
                    .error(R.drawable.bg_product_placeholder)
                    .centerCrop()
                    .into(iv)
            }
        },
    )
}

private val previewCartItemQty1 = CartItem(
    productId = 1,
    productVariantId = 1,
    productName = "Bolon Classic Full-Rim Frame",
    productBrand = "Bolon",
    variantLabel = "Black · Medium (54mm) · Acetate · Clear",
    productPrice = "1500",
    productImageUrl = null,
    quantity = 1,
)

private val previewCartItemQty2 = previewCartItemQty1.copy(quantity = 2)

@Preview(showBackground = true, name = "Cart line — qty 1")
@Composable
private fun CartLineCardPreviewQty1() {
    EyeCareTheme {
        CartLineCard(
            item = previewCartItemQty1,
            onIncrease = {},
            onDecrease = {},
            onRemove = {},
        )
    }
}

@Preview(showBackground = true, name = "Cart line — qty 2 (each label)")
@Composable
private fun CartLineCardPreviewQty2() {
    EyeCareTheme {
        CartLineCard(
            item = previewCartItemQty2,
            onIncrease = {},
            onDecrease = {},
            onRemove = {},
        )
    }
}

@Preview(showBackground = true, name = "Cart checkout footer")
@Composable
private fun CartCheckoutFooterPreview() {
    EyeCareTheme {
        CartCheckoutFooter(
            formattedTotal = "₱2,300",
            onCheckout = {},
            onAddMoreItems = {},
        )
    }
}
