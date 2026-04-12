package com.example.opticalsystem.ui.orders

import android.widget.ImageView
import android.widget.Toast
import androidx.compose.foundation.background
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
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Text
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.material3.pulltorefresh.rememberPullToRefreshState
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
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.runtime.livedata.observeAsState
import com.bumptech.glide.Glide
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Order
import com.example.opticalsystem.util.BackendImageUrl
import com.example.opticalsystem.util.Resource
import com.example.opticalsystem.util.StatusHelper

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OrdersScreen(
    viewModel: OrdersViewModel,
    onBack: () -> Unit,
    onOpenOrder: (Order) -> Unit,
) {
    val context = LocalContext.current
    val ordersResult by viewModel.orders.observeAsState()
    val activeCount by viewModel.activeCount.observeAsState(0)
    val pastCount by viewModel.pastCount.observeAsState(0)

    var activeTab by remember { mutableStateOf(viewModel.showingActive) }
    var hasCompletedLoad by remember { mutableStateOf(false) }
    var lastSuccessOrders by remember { mutableStateOf<List<Order>>(emptyList()) }

    LaunchedEffect(ordersResult) {
        when (val r = ordersResult) {
            is Resource.Success -> {
                hasCompletedLoad = true
                lastSuccessOrders = r.data
            }
            is Resource.Error -> {
                hasCompletedLoad = true
                lastSuccessOrders = emptyList()
                Toast.makeText(context, r.message, Toast.LENGTH_SHORT).show()
            }
            else -> {}
        }
    }

    val pullState = rememberPullToRefreshState()
    val initialLoading = ordersResult is Resource.Loading && !hasCompletedLoad
    val showListWhileRefreshing =
        ordersResult is Resource.Loading && hasCompletedLoad && lastSuccessOrders.isNotEmpty()

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(colorResource(R.color.background)),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(start = 8.dp, top = 16.dp, end = 16.dp, bottom = 8.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            IconButton(onClick = onBack, modifier = Modifier.size(40.dp)) {
                Icon(
                    painter = painterResource(R.drawable.ic_back_24),
                    contentDescription = stringResource(R.string.back),
                    tint = colorResource(R.color.text_primary),
                )
            }
            Text(
                text = stringResource(R.string.orders_title),
                modifier = Modifier.weight(1f).padding(start = 4.dp),
                color = colorResource(R.color.text_primary),
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
            )
        }

        OrderTabRow(
            activeSelected = activeTab,
            activeLabel = if (activeCount > 0) "Active ($activeCount)" else stringResource(R.string.order_tab_active),
            pastLabel = if (pastCount > 0) "Past ($pastCount)" else stringResource(R.string.order_tab_past),
            onActive = {
                activeTab = true
                viewModel.showActive()
            },
            onPast = {
                activeTab = false
                viewModel.showPast()
            },
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 8.dp),
        )

        Box(
            modifier = Modifier
                .fillMaxWidth()
                .weight(1f),
        ) {
            when (val result = ordersResult) {
                is Resource.Loading -> {
                    when {
                        initialLoading -> {
                            Box(
                                modifier = Modifier.fillMaxSize(),
                                contentAlignment = Alignment.Center,
                            ) {
                                CircularProgressIndicator(color = colorResource(R.color.primary))
                            }
                        }
                        showListWhileRefreshing -> {
                            PullToRefreshBox(
                                isRefreshing = true,
                                onRefresh = { viewModel.refresh() },
                                state = pullState,
                            ) {
                                OrdersLazyList(
                                    orders = lastSuccessOrders,
                                    onOpenOrder = onOpenOrder,
                                )
                            }
                        }
                        else -> {
                            Box(
                                modifier = Modifier.fillMaxSize(),
                                contentAlignment = Alignment.Center,
                            ) {
                                CircularProgressIndicator(color = colorResource(R.color.primary))
                            }
                        }
                    }
                }
                is Resource.Error -> {
                    OrdersEmptyState(modifier = Modifier.fillMaxSize())
                }
                is Resource.Success -> {
                    val list = result.data
                    if (list.isEmpty()) {
                        OrdersEmptyState(modifier = Modifier.fillMaxSize())
                    } else {
                        PullToRefreshBox(
                            isRefreshing = false,
                            onRefresh = { viewModel.refresh() },
                            state = pullState,
                        ) {
                            OrdersLazyList(orders = list, onOpenOrder = onOpenOrder)
                        }
                    }
                }
                null -> {
                    Box(
                        modifier = Modifier.fillMaxSize(),
                        contentAlignment = Alignment.Center,
                    ) {
                        CircularProgressIndicator(color = colorResource(R.color.primary))
                    }
                }
            }
        }
    }
}

@Composable
private fun OrdersLazyList(
    orders: List<Order>,
    onOpenOrder: (Order) -> Unit,
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(
            start = 16.dp,
            end = 16.dp,
            top = 12.dp,
            bottom = 16.dp,
        ),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        items(items = orders, key = { it.id }) { order ->
            OrderListCard(order = order, onClick = { onOpenOrder(order) })
        }
    }
}

@Composable
private fun OrderTabRow(
    activeSelected: Boolean,
    activeLabel: String,
    pastLabel: String,
    onActive: () -> Unit,
    onPast: () -> Unit,
    modifier: Modifier = Modifier,
) {
    Row(
        modifier = modifier
            .clip(RoundedCornerShape(32.dp))
            .background(Color(0xFFEDF0F4))
            .padding(4.dp),
    ) {
        TabSegment(
            text = activeLabel,
            selected = activeSelected,
            onClick = onActive,
            modifier = Modifier.weight(1f),
        )
        TabSegment(
            text = pastLabel,
            selected = !activeSelected,
            onClick = onPast,
            modifier = Modifier.weight(1f),
        )
    }
}

@Composable
private fun TabSegment(
    text: String,
    selected: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val bg = if (selected) Color.White else Color.Transparent
    val fg = if (selected) {
        colorResource(R.color.text_primary)
    } else {
        colorResource(R.color.text_secondary)
    }
    Box(
        modifier = modifier
            .height(44.dp)
            .clip(RoundedCornerShape(28.dp))
            .background(bg)
            .clickable(onClick = onClick),
        contentAlignment = Alignment.Center,
    ) {
        Text(
            text = text,
            color = fg,
            fontSize = 14.sp,
            fontWeight = if (selected) FontWeight.Bold else FontWeight.Normal,
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 8.dp),
        )
    }
}

@Composable
private fun OrdersEmptyState(modifier: Modifier = Modifier) {
    Column(
        modifier = modifier
            .fillMaxSize()
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Icon(
            painter = painterResource(R.drawable.ic_orders_24),
            contentDescription = stringResource(R.string.orders_empty_title),
            tint = colorResource(R.color.divider),
            modifier = Modifier.size(80.dp),
        )
        Text(
            text = stringResource(R.string.orders_empty_title),
            modifier = Modifier.padding(top = 16.dp),
            color = colorResource(R.color.text_primary),
            fontSize = 18.sp,
            fontWeight = FontWeight.Bold,
        )
        Text(
            text = stringResource(R.string.orders_empty_subtitle),
            modifier = Modifier.padding(top = 8.dp),
            color = colorResource(R.color.text_secondary),
            fontSize = 14.sp,
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun OrderListCard(
    order: Order,
    onClick: () -> Unit,
) {
    val context = LocalContext.current
    val (statusTextColor, statusBgColor) = StatusHelper.orderStatusBadgeColors(context, order.status)
    val images = order.items
        ?.mapNotNull { it.product?.images?.firstOrNull()?.imageUrl }
        ?.take(2)
    val url1 = images?.getOrNull(0)
    val url2 = images?.getOrNull(1)
    val itemCount = order.items?.size ?: 0
    val countLabel = if (itemCount == 1) "1 item" else "$itemCount items"

    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Text(
                    text = order.orderNumber,
                    modifier = Modifier.weight(1f),
                    color = colorResource(R.color.text_primary),
                    fontSize = 15.sp,
                    fontWeight = FontWeight.Bold,
                )
                Text(
                    text = order.statusLabel,
                    modifier = Modifier
                        .clip(RoundedCornerShape(50))
                        .background(Color(statusBgColor))
                        .padding(horizontal = 10.dp, vertical = 5.dp),
                    color = Color(statusTextColor),
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Bold,
                )
            }
            Text(
                text = StatusHelper.formatDateShort(order.createdAt),
                modifier = Modifier.padding(top = 4.dp),
                color = colorResource(R.color.primary),
                fontSize = 13.sp,
            )
            Row(
                modifier = Modifier.padding(top = 12.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Box(modifier = Modifier.width(72.dp).height(40.dp)) {
                    GlideCircleThumbnail(
                        imageUrl = url1,
                        modifier = Modifier
                            .size(40.dp)
                            .align(Alignment.CenterStart),
                    )
                    if (url2 != null) {
                        GlideCircleThumbnail(
                            imageUrl = url2,
                            modifier = Modifier
                                .size(40.dp)
                                .align(Alignment.CenterStart)
                                .padding(start = 26.dp),
                        )
                    }
                }
                Text(
                    text = countLabel,
                    modifier = Modifier
                        .weight(1f)
                        .padding(start = 10.dp),
                    color = colorResource(R.color.text_secondary),
                    fontSize = 13.sp,
                )
            }
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 12.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Text(
                    text = StatusHelper.formatPrice(order.totalAmount),
                    modifier = Modifier.weight(1f),
                    color = colorResource(R.color.price_color),
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                )
                Icon(
                    painter = painterResource(R.drawable.ic_chevron_right_24),
                    contentDescription = stringResource(R.string.order_detail_title),
                    tint = colorResource(R.color.text_secondary),
                    modifier = Modifier.size(24.dp),
                )
            }
        }
    }
}

@Composable
private fun GlideCircleThumbnail(
    imageUrl: String?,
    modifier: Modifier = Modifier,
) {
    val context = LocalContext.current
    AndroidView(
        factory = { ctx ->
            ImageView(ctx).apply {
                scaleType = ImageView.ScaleType.CENTER_CROP
            }
        },
        modifier = modifier.clip(CircleShape),
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
                    .circleCrop()
                    .into(iv)
            }
        },
    )
}
