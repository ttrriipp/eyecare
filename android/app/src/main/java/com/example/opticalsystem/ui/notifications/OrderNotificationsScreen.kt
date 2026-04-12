package com.example.opticalsystem.ui.notifications

import android.text.format.DateUtils
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.runtime.livedata.observeAsState
import com.example.opticalsystem.R
import com.example.opticalsystem.notifications.OrderStatusNotifier

@Composable
fun OrderNotificationsScreen(
    viewModel: OrderNotificationsViewModel,
    onBack: () -> Unit,
    onOpenOrder: (orderId: Int) -> Unit,
) {
    val notifications by viewModel.notifications.observeAsState(emptyList())

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
            IconButton(
                onClick = onBack,
                modifier = Modifier.size(40.dp),
            ) {
                Icon(
                    painter = painterResource(R.drawable.ic_back_24),
                    contentDescription = stringResource(R.string.back),
                    tint = colorResource(R.color.text_primary),
                )
            }
            Text(
                text = stringResource(R.string.order_notifications_title),
                modifier = Modifier.weight(1f).padding(start = 4.dp),
                color = colorResource(R.color.text_primary),
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
            )
            if (notifications.isNotEmpty()) {
                Text(
                    text = stringResource(R.string.order_notifications_clear_all),
                    modifier = Modifier
                        .clickable { viewModel.clearAllNotifications() }
                        .padding(horizontal = 8.dp, vertical = 6.dp),
                    color = colorResource(R.color.primary),
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Bold,
                )
            }
        }

        Box(
            modifier = Modifier
                .fillMaxWidth()
                .weight(1f),
        ) {
            if (notifications.isEmpty()) {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(24.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.Center,
                ) {
                    Icon(
                        painter = painterResource(R.drawable.ic_order_status_notification),
                        contentDescription = stringResource(R.string.order_notifications_title),
                        tint = colorResource(R.color.divider),
                        modifier = Modifier.size(72.dp),
                    )
                    Text(
                        text = stringResource(R.string.order_notifications_empty),
                        modifier = Modifier.padding(top = 12.dp),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 14.sp,
                    )
                }
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(
                        start = 16.dp,
                        end = 16.dp,
                        top = 8.dp,
                        bottom = 16.dp,
                    ),
                    verticalArrangement = Arrangement.spacedBy(10.dp),
                ) {
                    items(
                        items = notifications,
                        key = { "${it.orderId}_${it.changedAtMillis}" },
                    ) { item ->
                        OrderNotificationCard(
                            item = item,
                            onClick = { onOpenOrder(item.orderId) },
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun OrderNotificationCard(
    item: OrderStatusNotifier.InAppNotification,
    onClick: () -> Unit,
) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        border = BorderStroke(1.dp, colorResource(R.color.divider)),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Icon(
                painter = painterResource(R.drawable.ic_order_status_notification),
                contentDescription = stringResource(R.string.order_notifications_title),
                tint = colorResource(R.color.primary),
                modifier = Modifier.size(22.dp),
            )
            Column(
                modifier = Modifier
                    .weight(1f)
                    .padding(start = 10.dp),
            ) {
                Text(
                    text = stringResource(
                        R.string.order_notification_item_title,
                        item.orderNumber,
                    ),
                    color = colorResource(R.color.text_primary),
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                )
                Text(
                    text = stringResource(
                        R.string.order_notification_item_message,
                        item.statusLabel,
                    ),
                    modifier = Modifier.padding(top = 2.dp),
                    color = colorResource(R.color.text_secondary),
                    fontSize = 13.sp,
                )
            }
            Text(
                text = DateUtils.getRelativeTimeSpanString(
                    item.changedAtMillis,
                    System.currentTimeMillis(),
                    DateUtils.MINUTE_IN_MILLIS,
                ).toString(),
                color = colorResource(R.color.text_secondary),
                fontSize = 12.sp,
            )
        }
    }
}
