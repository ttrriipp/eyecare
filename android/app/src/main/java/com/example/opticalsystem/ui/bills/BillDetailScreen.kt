package com.example.opticalsystem.ui.bills

import android.widget.Toast
import androidx.compose.foundation.background
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
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.runtime.livedata.observeAsState
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Bill
import com.example.opticalsystem.util.Resource
import com.example.opticalsystem.util.StatusHelper

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun BillDetailScreen(
    viewModel: BillDetailViewModel,
    onBack: () -> Unit,
    onOpenLinkedOrder: (Int) -> Unit,
) {
    val context = LocalContext.current
    val billResult by viewModel.bill.observeAsState()

    LaunchedEffect(billResult) {
        val err = billResult as? Resource.Error ?: return@LaunchedEffect
        Toast.makeText(context, err.message, Toast.LENGTH_SHORT).show()
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
                text = stringResource(R.string.bill_detail_title),
                modifier = Modifier.padding(start = 4.dp),
                color = colorResource(R.color.on_primary),
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
            )
        }

        Box(
            modifier = Modifier
                .fillMaxWidth()
                .weight(1f),
        ) {
            when (val r = billResult) {
                is Resource.Loading -> {
                    Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = colorResource(R.color.primary))
                    }
                }
                is Resource.Success -> {
                    BillDetailScrollContent(
                        bill = r.data,
                        onOpenLinkedOrder = onOpenLinkedOrder,
                    )
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
}

@Composable
private fun BillDetailScrollContent(
    bill: Bill,
    onOpenLinkedOrder: (Int) -> Unit,
) {
    val context = LocalContext.current
    val (payTextColor, payBgColor) = StatusHelper.paymentStatusBadgeColors(
        context,
        bill.paymentStatus,
    )
    val terminal = bill.paymentStatus == "voided" ||
        bill.paymentStatus == "refunded" ||
        bill.paymentStatus == "partially_refunded"
    val balanceVal = bill.balanceDue?.toDoubleOrNull() ?: 0.0
    val showBalance = !terminal && balanceVal > 0 && !bill.balanceDue.isNullOrBlank()
    val methodDisplay = bill.paymentMethodLabel ?: bill.paymentMethod

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp),
    ) {
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        ) {
            Column(modifier = Modifier.padding(16.dp)) {
                Text(
                    text = bill.invoiceNumber,
                    color = colorResource(R.color.text_primary),
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                )
                if (!bill.officialReceiptNumber.isNullOrBlank()) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 8.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                    ) {
                        Text(
                            text = stringResource(R.string.bill_official_receipt),
                            color = colorResource(R.color.text_secondary),
                            fontSize = 14.sp,
                        )
                        Text(
                            text = bill.officialReceiptNumber!!,
                            color = colorResource(R.color.text_primary),
                            fontSize = 14.sp,
                            fontFamily = FontFamily.Monospace,
                        )
                    }
                }
                Text(
                    text = bill.paymentStatusLabel,
                    modifier = Modifier
                        .padding(top = 8.dp)
                        .clip(RoundedCornerShape(50))
                        .background(Color(payBgColor))
                        .padding(horizontal = 12.dp, vertical = 4.dp),
                    color = Color(payTextColor),
                    fontSize = 13.sp,
                    fontWeight = FontWeight.Bold,
                )
                HorizontalDivider(
                    modifier = Modifier.padding(vertical = 12.dp),
                    color = colorResource(R.color.divider),
                )
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                ) {
                    Text(
                        text = stringResource(R.string.bill_total_amount),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 14.sp,
                    )
                    Text(
                        text = StatusHelper.formatPrice(bill.amount),
                        color = colorResource(R.color.text_primary),
                        fontSize = 14.sp,
                    )
                }
                if (!bill.amountPaid.isNullOrBlank()) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 8.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                    ) {
                        Text(
                            text = stringResource(R.string.bill_amount_paid),
                            color = colorResource(R.color.text_secondary),
                            fontSize = 14.sp,
                        )
                        Text(
                            text = StatusHelper.formatPrice(bill.amountPaid!!),
                            color = colorResource(R.color.price_color),
                            fontSize = 16.sp,
                            fontWeight = FontWeight.Bold,
                        )
                    }
                }
                if (showBalance) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 8.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                    ) {
                        Text(
                            text = stringResource(R.string.bill_balance_due),
                            color = colorResource(R.color.text_secondary),
                            fontSize = 14.sp,
                        )
                        Text(
                            text = StatusHelper.formatPrice(bill.balanceDue!!),
                            color = colorResource(R.color.price_color),
                            fontSize = 16.sp,
                            fontWeight = FontWeight.Bold,
                        )
                    }
                }
                if (!methodDisplay.isNullOrBlank()) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 8.dp),
                    ) {
                        Text(
                            text = stringResource(R.string.bill_payment_method),
                            color = colorResource(R.color.text_secondary),
                            fontSize = 14.sp,
                        )
                        Text(
                            text = methodDisplay,
                            modifier = Modifier
                                .weight(1f)
                                .padding(start = 8.dp),
                            color = colorResource(R.color.text_primary),
                            fontSize = 14.sp,
                            textAlign = TextAlign.End,
                        )
                    }
                }
                if (!bill.paidAt.isNullOrBlank()) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 8.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                    ) {
                        Text(
                            text = stringResource(R.string.bill_paid_at),
                            color = colorResource(R.color.text_secondary),
                            fontSize = 14.sp,
                        )
                        Text(
                            text = StatusHelper.formatDate(bill.paidAt!!),
                            color = colorResource(R.color.text_primary),
                            fontSize = 14.sp,
                        )
                    }
                }
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 8.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                ) {
                    Text(
                        text = stringResource(R.string.order_detail_date),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 14.sp,
                    )
                    Text(
                        text = StatusHelper.formatDate(bill.createdAt),
                        color = colorResource(R.color.text_primary),
                        fontSize = 14.sp,
                    )
                }
            }
        }

        if (bill.orderId != null) {
            val orderNumber = bill.order?.orderNumber ?: bill.orderId.toString()
            Card(
                onClick = { onOpenLinkedOrder(bill.orderId!!) },
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 16.dp),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
            ) {
                Row(
                    modifier = Modifier.padding(16.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Column(modifier = Modifier.weight(1f)) {
                        Text(
                            text = stringResource(R.string.bill_linked_order),
                            color = colorResource(R.color.text_secondary),
                            fontSize = 12.sp,
                        )
                        Text(
                            text = stringResource(R.string.order_number_format, orderNumber),
                            modifier = Modifier.padding(top = 2.dp),
                            color = colorResource(R.color.primary),
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                        )
                    }
                    Icon(
                        painter = painterResource(R.drawable.ic_back_24),
                        contentDescription = stringResource(R.string.order_detail_title),
                        tint = colorResource(R.color.text_secondary),
                        modifier = Modifier
                            .size(24.dp)
                            .graphicsLayer { rotationZ = 180f },
                    )
                }
            }
        }

        Spacer(Modifier.height(16.dp))
    }
}
