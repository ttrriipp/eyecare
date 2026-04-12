package com.example.opticalsystem.ui.bills

import android.widget.Toast
import androidx.compose.foundation.background
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
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
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
import androidx.compose.runtime.livedata.observeAsState
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Bill
import com.example.opticalsystem.util.Resource
import com.example.opticalsystem.util.StatusHelper

private data class BillStatusFilter(
    val paymentStatus: String?,
    val labelRes: Int,
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun BillsScreen(
    viewModel: BillsViewModel,
    onBack: () -> Unit,
    onOpenBill: (Bill) -> Unit,
) {
    val context = LocalContext.current
    val billsResult by viewModel.bills.observeAsState()

    val filters = remember {
        listOf(
            BillStatusFilter(null, R.string.bill_status_all),
            BillStatusFilter("unpaid", R.string.bill_status_unpaid),
            BillStatusFilter("partially_paid", R.string.bill_status_partially_paid),
            BillStatusFilter("paid", R.string.bill_status_paid),
            BillStatusFilter("refunded", R.string.bill_status_refunded),
            BillStatusFilter("voided", R.string.bill_status_voided),
        )
    }

    var selectedStatus by remember { mutableStateOf<String?>(null) }
    var hasCompletedLoad by remember { mutableStateOf(false) }
    var lastSuccessBills by remember { mutableStateOf<List<Bill>>(emptyList()) }

    LaunchedEffect(billsResult) {
        when (val r = billsResult) {
            is Resource.Success -> {
                hasCompletedLoad = true
                lastSuccessBills = r.data
            }
            is Resource.Error -> {
                hasCompletedLoad = true
                lastSuccessBills = emptyList()
                Toast.makeText(context, r.message, Toast.LENGTH_SHORT).show()
            }
            else -> {}
        }
    }

    val pullState = rememberPullToRefreshState()
    val initialLoading = billsResult is Resource.Loading && !hasCompletedLoad
    val showListWhileRefreshing =
        billsResult is Resource.Loading && hasCompletedLoad && lastSuccessBills.isNotEmpty()

    Column(modifier = Modifier.fillMaxSize()) {
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
                text = stringResource(R.string.bills_title),
                modifier = Modifier.weight(1f).padding(start = 4.dp),
                color = colorResource(R.color.on_primary),
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
            )
        }

        LazyRow(
            modifier = Modifier.fillMaxWidth(),
            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 12.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            items(filters) { f ->
                FilterChip(
                    selected = selectedStatus == f.paymentStatus,
                    onClick = {
                        selectedStatus = f.paymentStatus
                        viewModel.filterByStatus(f.paymentStatus)
                    },
                    label = { Text(stringResource(f.labelRes)) },
                    colors = FilterChipDefaults.filterChipColors(
                        selectedContainerColor = colorResource(R.color.primary),
                        selectedLabelColor = colorResource(R.color.on_primary),
                    ),
                )
            }
        }

        Box(
            modifier = Modifier
                .fillMaxWidth()
                .weight(1f)
                .background(colorResource(R.color.background)),
        ) {
            when (val result = billsResult) {
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
                                BillsLazyList(bills = lastSuccessBills, onOpenBill = onOpenBill)
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
                    BillsEmptyState(modifier = Modifier.fillMaxSize())
                }
                is Resource.Success -> {
                    val list = result.data
                    if (list.isEmpty()) {
                        BillsEmptyState(modifier = Modifier.fillMaxSize())
                    } else {
                        PullToRefreshBox(
                            isRefreshing = false,
                            onRefresh = { viewModel.refresh() },
                            state = pullState,
                        ) {
                            BillsLazyList(bills = list, onOpenBill = onOpenBill)
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
private fun BillsLazyList(
    bills: List<Bill>,
    onOpenBill: (Bill) -> Unit,
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(start = 16.dp, end = 16.dp, bottom = 16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        items(items = bills, key = { it.id }) { bill ->
            BillListCard(bill = bill, onClick = { onOpenBill(bill) })
        }
    }
}

@Composable
private fun BillsEmptyState(modifier: Modifier = Modifier) {
    Column(
        modifier = modifier
            .fillMaxSize()
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Icon(
            painter = painterResource(R.drawable.ic_orders_24),
            contentDescription = stringResource(R.string.bills_empty_title),
            tint = colorResource(R.color.divider),
            modifier = Modifier.size(80.dp),
        )
        Text(
            text = stringResource(R.string.bills_empty_title),
            modifier = Modifier.padding(top = 16.dp),
            color = colorResource(R.color.text_primary),
            fontSize = 18.sp,
            fontWeight = FontWeight.Bold,
        )
        Text(
            text = stringResource(R.string.bills_empty_subtitle),
            modifier = Modifier.padding(top = 8.dp),
            color = colorResource(R.color.text_secondary),
            fontSize = 14.sp,
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun BillListCard(
    bill: Bill,
    onClick: () -> Unit,
) {
    val context = LocalContext.current
    val (payTextColor, payBgColor) = StatusHelper.paymentStatusBadgeColors(
        context,
        bill.paymentStatus,
    )
    val paid = StatusHelper.formatPrice(bill.amountPaid ?: "0")
    val total = StatusHelper.formatPrice(bill.amount)
    val terminal = bill.paymentStatus == "voided" ||
        bill.paymentStatus == "refunded" ||
        bill.paymentStatus == "partially_refunded"
    val balanceVal = bill.balanceDue?.toDoubleOrNull() ?: 0.0
    val showBalance = !terminal && balanceVal > 0 && bill.paymentStatus != "unpaid"

    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Text(
                    text = bill.invoiceNumber,
                    modifier = Modifier.weight(1f),
                    color = colorResource(R.color.text_primary),
                    fontSize = 15.sp,
                    fontWeight = FontWeight.Bold,
                )
                Text(
                    text = bill.paymentStatusLabel,
                    modifier = Modifier
                        .clip(RoundedCornerShape(50))
                        .background(Color(payBgColor))
                        .padding(horizontal = 10.dp, vertical = 4.dp),
                    color = Color(payTextColor),
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Bold,
                )
            }
            if (bill.orderId != null) {
                Text(
                    text = "Order #${bill.order?.orderNumber ?: bill.orderId}",
                    modifier = Modifier.padding(top = 6.dp),
                    color = colorResource(R.color.text_secondary),
                    fontSize = 13.sp,
                )
            }
            Text(
                text = "$paid / $total",
                modifier = Modifier.padding(top = 6.dp),
                color = colorResource(R.color.price_color),
                fontSize = 15.sp,
                fontWeight = FontWeight.Bold,
            )
            if (showBalance && bill.balanceDue != null) {
                Text(
                    text = "Balance: ${StatusHelper.formatPrice(bill.balanceDue)}",
                    modifier = Modifier.padding(top = 2.dp),
                    color = colorResource(R.color.text_secondary),
                    fontSize = 12.sp,
                )
            }
            Text(
                text = StatusHelper.formatDateShort(bill.createdAt),
                modifier = Modifier.padding(top = 6.dp),
                color = colorResource(R.color.text_secondary),
                fontSize = 13.sp,
            )
        }
    }
}
