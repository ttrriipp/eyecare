package com.example.opticalsystem.ui.orders

import android.widget.ImageView
import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
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
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.runtime.livedata.observeAsState
import com.bumptech.glide.Glide
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Order
import com.example.opticalsystem.data.model.OrderItem
import com.example.opticalsystem.util.BackendImageUrl
import com.example.opticalsystem.util.Resource
import com.example.opticalsystem.util.StatusHelper

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OrderDetailScreen(viewModel: OrderDetailViewModel, onBack: () -> Unit) {
    val context = LocalContext.current
    val orderResult by viewModel.order.observeAsState()
    val cancelResult by viewModel.cancelResult.observeAsState()
    var showCancelDialog by remember { mutableStateOf(false) }
    var orderForCancel by remember { mutableStateOf<Order?>(null) }

    LaunchedEffect(cancelResult) {
        when (val r = cancelResult) {
            is Resource.Success -> {
                Toast.makeText(context, context.getString(R.string.order_cancelled_success), Toast.LENGTH_SHORT).show()
            }
            is Resource.Error -> {
                Toast.makeText(context, r.message, Toast.LENGTH_LONG).show()
            }
            else -> {}
        }
    }

    LaunchedEffect(orderResult) {
        val err = orderResult as? Resource.Error ?: return@LaunchedEffect
        Toast.makeText(context, err.message, Toast.LENGTH_SHORT).show()
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(colorResource(R.color.background)),
    ) {
        SurfaceHeader(order = (orderResult as? Resource.Success)?.data, onBack = onBack)

        Box(
            modifier = Modifier
                .fillMaxWidth()
                .weight(1f),
        ) {
            when (val r = orderResult) {
                is Resource.Loading -> {
                    Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = colorResource(R.color.primary))
                    }
                }
                is Resource.Success -> {
                    val order = r.data
                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .verticalScroll(rememberScrollState())
                            .padding(16.dp),
                    ) {
                        StatusBannerCard(order = order)
                        OrderProgressCard(status = order.status)
                        OrderItemsCard(items = order.items.orEmpty())
                        OrderSummaryCard(order = order)
                        if (!order.notes.isNullOrBlank()) {
                            NotesCard(notes = order.notes!!)
                        }
                        val canCancel = order.status == "pending" || order.status == "confirmed"
                        if (canCancel) {
                            val cancelling = cancelResult is Resource.Loading
                            OutlinedButton(
                                onClick = {
                                    orderForCancel = order
                                    showCancelDialog = true
                                },
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(top = 16.dp)
                                    .height(48.dp),
                                enabled = !cancelling,
                                shape = RoundedCornerShape(12.dp),
                                border = BorderStroke(1.dp, colorResource(R.color.status_cancelled)),
                                colors = ButtonDefaults.outlinedButtonColors(
                                    contentColor = colorResource(R.color.status_cancelled),
                                ),
                            ) {
                                Text(stringResource(R.string.order_cancel_button))
                            }
                        }
                        Spacer(Modifier.height(16.dp))
                    }
                }
                is Resource.Error -> {
                    Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = colorResource(R.color.primary))
                    }
                }
                null -> {
                    Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = colorResource(R.color.primary))
                    }
                }
            }
        }
    }

    if (showCancelDialog && orderForCancel != null) {
        val o = orderForCancel!!
        AlertDialog(
            onDismissRequest = { showCancelDialog = false },
            title = { Text(stringResource(R.string.order_cancel_title)) },
            text = {
                Text(stringResource(R.string.order_cancel_message, o.orderNumber))
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        showCancelDialog = false
                        viewModel.cancelOrder()
                    },
                ) {
                    Text(stringResource(R.string.order_cancel_button))
                }
            },
            dismissButton = {
                TextButton(onClick = { showCancelDialog = false }) {
                    Text(stringResource(R.string.action_cancel))
                }
            },
        )
    }
}

@Composable
private fun SurfaceHeader(order: Order?, onBack: () -> Unit) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .background(colorResource(R.color.surface))
            .padding(start = 8.dp, end = 16.dp, top = 12.dp, bottom = 12.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        IconButton(onClick = onBack, modifier = Modifier.size(40.dp)) {
            Icon(
                painter = painterResource(R.drawable.ic_back_24),
                contentDescription = stringResource(R.string.back),
                tint = colorResource(R.color.text_primary),
            )
        }
        Column(modifier = Modifier.padding(start = 4.dp)) {
            Text(
                text = order?.orderNumber.orEmpty(),
                color = colorResource(R.color.text_primary),
                fontSize = 17.sp,
                fontWeight = FontWeight.Bold,
            )
            if (order != null) {
                Text(
                    text = StatusHelper.formatDateShort(order.createdAt),
                    color = colorResource(R.color.text_secondary),
                    fontSize = 12.sp,
                )
            }
        }
    }
}

@Composable
private fun StatusBannerCard(order: Order) {
    val context = LocalContext.current
    val (bgColor, iconTint, description) = statusBannerStyle(context, order.status)

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = Color(bgColor)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Box(
                modifier = Modifier
                    .size(44.dp)
                    .clip(CircleShape)
                    .background(Color(iconTint)),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    painter = painterResource(R.drawable.ic_check_16),
                    contentDescription = stringResource(R.string.order_detail_status),
                    tint = Color.White,
                    modifier = Modifier.size(22.dp),
                )
            }
            Column(modifier = Modifier.padding(start = 12.dp)) {
                Text(
                    text = order.statusLabel,
                    color = colorResource(R.color.text_primary),
                    fontSize = 15.sp,
                    fontWeight = FontWeight.Bold,
                )
                if (description.isNotEmpty()) {
                    Text(
                        text = description,
                        modifier = Modifier.padding(top = 2.dp),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 13.sp,
                    )
                }
            }
        }
    }
}

private fun statusBannerStyle(
    context: android.content.Context,
    status: String,
): Triple<Int, Int, String> {
    return when (status) {
        "pending" -> Triple(
            androidx.core.content.ContextCompat.getColor(context, R.color.status_pending_bg),
            androidx.core.content.ContextCompat.getColor(context, R.color.status_pending),
            "Your order has been placed and is waiting to be confirmed.",
        )
        "confirmed" -> Triple(
            androidx.core.content.ContextCompat.getColor(context, R.color.status_confirmed_bg),
            androidx.core.content.ContextCompat.getColor(context, R.color.status_confirmed),
            "Your order has been confirmed and is being prepared.",
        )
        "ready", "ready_for_pickup" -> Triple(
            androidx.core.content.ContextCompat.getColor(context, R.color.status_ready_bg),
            androidx.core.content.ContextCompat.getColor(context, R.color.status_ready),
            "Your order is ready for pickup at our store.",
        )
        "completed" -> Triple(
            androidx.core.content.ContextCompat.getColor(context, R.color.status_completed_bg),
            androidx.core.content.ContextCompat.getColor(context, R.color.status_completed),
            "Your order has been picked up. Thank you!",
        )
        "cancelled" -> Triple(
            androidx.core.content.ContextCompat.getColor(context, R.color.status_cancelled_bg),
            androidx.core.content.ContextCompat.getColor(context, R.color.status_cancelled),
            "This order has been cancelled.",
        )
        else -> Triple(
            androidx.core.content.ContextCompat.getColor(context, R.color.divider),
            androidx.core.content.ContextCompat.getColor(context, R.color.text_secondary),
            "",
        )
    }
}

@Composable
private fun OrderProgressCard(status: String) {
    val primary = colorResource(R.color.primary)
    val divider = colorResource(R.color.divider)
    val inactiveText = colorResource(R.color.text_secondary)
    val stepsDone = when (status) {
        "pending" -> 1
        "confirmed" -> 2
        "ready", "ready_for_pickup" -> 3
        "completed" -> 4
        else -> 0
    }

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(top = 12.dp),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = stringResource(R.string.order_progress_title),
                color = colorResource(R.color.text_primary),
                fontSize = 15.sp,
                fontWeight = FontWeight.Bold,
            )
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 16.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                StepDotColumn(
                    stepIndex = 0,
                    stepsDone = stepsDone,
                    label = stringResource(R.string.order_step_processing),
                    activeColor = primary,
                    inactiveColor = divider,
                    inactiveTextColor = inactiveText,
                    modifier = Modifier.weight(1f),
                )
                StepLineConnector(done = 0 + 1 < stepsDone, activeColor = primary, inactiveColor = divider)
                StepDotColumn(
                    stepIndex = 1,
                    stepsDone = stepsDone,
                    label = stringResource(R.string.order_step_confirmed),
                    activeColor = primary,
                    inactiveColor = divider,
                    inactiveTextColor = inactiveText,
                    modifier = Modifier.weight(1f),
                )
                StepLineConnector(done = 1 + 1 < stepsDone, activeColor = primary, inactiveColor = divider)
                StepDotColumn(
                    stepIndex = 2,
                    stepsDone = stepsDone,
                    label = stringResource(R.string.order_step_ready),
                    activeColor = primary,
                    inactiveColor = divider,
                    inactiveTextColor = inactiveText,
                    modifier = Modifier.weight(1f),
                )
                StepLineConnector(done = 2 + 1 < stepsDone, activeColor = primary, inactiveColor = divider)
                StepDotColumn(
                    stepIndex = 3,
                    stepsDone = stepsDone,
                    label = stringResource(R.string.order_step_picked_up),
                    activeColor = primary,
                    inactiveColor = divider,
                    inactiveTextColor = inactiveText,
                    modifier = Modifier.weight(1f),
                )
            }
        }
    }
}

@Composable
private fun RowScope.StepLineConnector(
    done: Boolean,
    activeColor: Color,
    inactiveColor: Color,
) {
    Box(
        modifier = Modifier
            .width(0.dp)
            .weight(0.5f)
            .height(2.dp)
            .padding(bottom = 18.dp)
            .background(if (done) activeColor else inactiveColor),
    )
}

@Composable
private fun RowScope.StepDotColumn(
    stepIndex: Int,
    stepsDone: Int,
    label: String,
    activeColor: Color,
    inactiveColor: Color,
    inactiveTextColor: Color,
    modifier: Modifier = Modifier,
) {
    val done = stepIndex < stepsDone
    Column(modifier = modifier, horizontalAlignment = Alignment.CenterHorizontally) {
        Box(
            modifier = Modifier
                .size(32.dp)
                .clip(CircleShape)
                .background(if (done) activeColor else inactiveColor),
            contentAlignment = Alignment.Center,
        ) {
            if (done) {
                Icon(
                    painter = painterResource(R.drawable.ic_check_16),
                    contentDescription = null,
                    tint = Color.White,
                    modifier = Modifier.size(16.dp),
                )
            } else {
                Text(
                    text = "${stepIndex + 1}",
                    color = inactiveTextColor,
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Bold,
                )
            }
        }
        Text(
            text = label,
            modifier = Modifier.padding(top = 6.dp),
            color = inactiveTextColor,
            fontSize = 10.sp,
        )
    }
}

@Composable
private fun OrderItemsCard(items: List<OrderItem>) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(top = 12.dp),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = stringResource(R.string.order_detail_items),
                color = colorResource(R.color.text_primary),
                fontSize = 15.sp,
                fontWeight = FontWeight.Bold,
            )
            items.forEach { item ->
                OrderLineRow(item = item)
            }
        }
    }
}

@Composable
private fun OrderLineRow(item: OrderItem) {
    val context = LocalContext.current
    val product = item.product
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        AndroidView(
            factory = { ctx ->
                ImageView(ctx).apply { scaleType = ImageView.ScaleType.CENTER_CROP }
            },
            modifier = Modifier
                .size(52.dp)
                .clip(CircleShape),
            update = { iv ->
                val url = BackendImageUrl.resolve(context, product?.images?.firstOrNull()?.imageUrl)
                if (url == null) {
                    iv.setImageResource(R.drawable.bg_product_placeholder)
                } else {
                    Glide.with(iv)
                        .load(url)
                        .placeholder(R.drawable.bg_product_placeholder)
                        .error(R.drawable.bg_product_placeholder)
                        .centerCrop()
                        .into(iv)
                }
            },
        )
        Column(modifier = Modifier.weight(1f).padding(start = 12.dp)) {
            Text(
                text = product?.name ?: "Product #${item.productId}",
                color = colorResource(R.color.text_primary),
                fontSize = 14.sp,
                fontWeight = FontWeight.Bold,
                maxLines = 2,
                overflow = TextOverflow.Ellipsis,
            )
            val brand = product?.brand
            if (!brand.isNullOrBlank()) {
                Text(
                    text = brand,
                    modifier = Modifier.padding(top = 2.dp),
                    color = colorResource(R.color.text_secondary),
                    fontSize = 12.sp,
                )
            }
            Text(
                text = "Qty: ${item.quantity}",
                modifier = Modifier.padding(top = 2.dp),
                color = colorResource(R.color.text_secondary),
                fontSize = 12.sp,
            )
        }
        Text(
            text = StatusHelper.formatPrice(item.unitPrice),
            modifier = Modifier.padding(start = 12.dp),
            color = colorResource(R.color.price_color),
            fontSize = 14.sp,
            fontWeight = FontWeight.Bold,
        )
    }
}

@Composable
private fun OrderSummaryCard(order: Order) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(top = 12.dp),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = stringResource(R.string.order_summary_title),
                color = colorResource(R.color.text_primary),
                fontSize = 15.sp,
                fontWeight = FontWeight.Bold,
            )
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 12.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
            ) {
                Text(
                    text = stringResource(R.string.order_subtotal),
                    color = colorResource(R.color.text_secondary),
                    fontSize = 14.sp,
                    modifier = Modifier.weight(1f),
                )
                Text(
                    text = StatusHelper.formatPrice(order.totalAmount),
                    color = colorResource(R.color.text_primary),
                    fontSize = 14.sp,
                )
            }
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 8.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
            ) {
                Text(
                    text = stringResource(R.string.order_pickup_fee),
                    color = colorResource(R.color.text_secondary),
                    fontSize = 14.sp,
                    modifier = Modifier.weight(1f),
                )
                Text(
                    text = stringResource(R.string.order_pickup_fee_free),
                    color = colorResource(R.color.status_completed),
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                )
            }
            Spacer(Modifier.height(12.dp))
            HorizontalDivider(color = colorResource(R.color.divider))
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 12.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Text(
                    text = stringResource(R.string.order_detail_total),
                    color = colorResource(R.color.text_primary),
                    fontSize = 15.sp,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.weight(1f),
                )
                Text(
                    text = StatusHelper.formatPrice(order.totalAmount),
                    color = colorResource(R.color.price_color),
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                )
            }
        }
    }
}

@Composable
private fun NotesCard(notes: String) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(top = 12.dp),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = stringResource(R.string.order_detail_notes),
                color = colorResource(R.color.text_primary),
                fontSize = 15.sp,
                fontWeight = FontWeight.Bold,
            )
            Text(
                text = notes,
                modifier = Modifier.padding(top = 8.dp),
                color = colorResource(R.color.text_secondary),
                fontSize = 14.sp,
            )
        }
    }
}
