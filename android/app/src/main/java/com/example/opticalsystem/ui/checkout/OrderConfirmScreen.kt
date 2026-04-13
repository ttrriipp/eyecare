package com.example.opticalsystem.ui.checkout

import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
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
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.runtime.livedata.observeAsState
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Appointment
import com.example.opticalsystem.data.model.Order
import com.example.opticalsystem.util.Resource

@Composable
fun OrderConfirmScreen(
    viewModel: CheckoutViewModel,
    onBack: () -> Unit,
    onEditOrder: () -> Unit,
    onOrderPlaced: (Order) -> Unit,
) {
    val context = LocalContext.current
    val cartItems by viewModel.cartItems.collectAsStateWithLifecycle()
    val totalPrice by viewModel.totalPrice.collectAsStateWithLifecycle()
    val orderNotes by viewModel.orderNotes.collectAsStateWithLifecycle()
    val selectedAppointmentId by viewModel.selectedAppointmentId.collectAsStateWithLifecycle()
    val upcoming by viewModel.upcomingAppointments.observeAsState()
    val orderResult by viewModel.orderResult.observeAsState()

    val appointmentsById = remember(upcoming) {
        when (val u = upcoming) {
            is Resource.Success -> u.data.associateBy { it.id }
            else -> emptyMap()
        }
    }

    LaunchedEffect(orderResult) {
        when (val r = orderResult) {
            is Resource.Success -> onOrderPlaced(r.data)
            is Resource.Error -> {
                Toast.makeText(context, r.message, Toast.LENGTH_LONG).show()
            }
            else -> Unit
        }
    }

    val appointmentSummary = appointmentSummaryText(
        selectedAppointmentId,
        appointmentsById,
    )

    val formattedTotal = remember(totalPrice) {
        "₱${String.format("%,.0f", totalPrice)}"
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(colorResource(R.color.background)),
    ) {
        CheckoutFlowHeader(
            title = stringResource(R.string.confirm_order_title),
            onBack = onBack,
        )

        Column(
            modifier = Modifier
                .weight(1f)
                .fillMaxWidth()
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 16.dp)
                .padding(bottom = 16.dp),
        ) {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 10.dp),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                border = BorderStroke(1.dp, colorResource(R.color.divider)),
            ) {
                Column(Modifier.padding(12.dp)) {
                    Text(
                        text = stringResource(R.string.items_label),
                        color = colorResource(R.color.text_secondary),
                        fontWeight = FontWeight.Bold,
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    cartItems.forEachIndexed { index, item ->
                        val unit = item.productPrice.toDoubleOrNull() ?: 0.0
                        val label = item.productName +
                            if (item.variantLabel.isNullOrBlank()) "" else " (${item.variantLabel})"
                        val amount = "₱${String.format("%,.0f", unit * item.quantity)}"
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            Text(
                                text = label,
                                modifier = Modifier.weight(1f),
                                color = colorResource(R.color.text_primary),
                                fontSize = 15.sp,
                            )
                            Text(
                                text = amount,
                                color = colorResource(R.color.text_primary),
                                fontSize = 15.sp,
                                fontWeight = FontWeight.Bold,
                            )
                        }
                        if (index < cartItems.lastIndex) {
                            Spacer(modifier = Modifier.height(8.dp))
                        }
                    }
                    HorizontalDivider(
                        modifier = Modifier.padding(top = 12.dp, bottom = 10.dp),
                        color = colorResource(R.color.divider),
                    )
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Text(
                            text = stringResource(R.string.cart_total),
                            modifier = Modifier.weight(1f),
                            color = colorResource(R.color.text_primary),
                            fontSize = 16.sp,
                            fontWeight = FontWeight.Bold,
                        )
                        Text(
                            text = formattedTotal,
                            color = colorResource(R.color.text_primary),
                            fontSize = 16.sp,
                            fontWeight = FontWeight.Bold,
                        )
                    }
                }
            }

            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 10.dp),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                border = BorderStroke(1.dp, colorResource(R.color.divider)),
            ) {
                Column(Modifier.padding(12.dp)) {
                    Text(
                        text = stringResource(R.string.appointment_label),
                        color = colorResource(R.color.text_secondary),
                        fontWeight = FontWeight.Bold,
                    )
                    Text(
                        text = appointmentSummary,
                        modifier = Modifier.padding(top = 8.dp),
                        color = colorResource(R.color.text_primary),
                        fontSize = 15.sp,
                    )
                }
            }

            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 10.dp),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                border = BorderStroke(1.dp, colorResource(R.color.divider)),
            ) {
                Column(Modifier.padding(12.dp)) {
                    Text(
                        text = stringResource(R.string.notes_label),
                        color = colorResource(R.color.text_secondary),
                        fontWeight = FontWeight.Bold,
                    )
                    Text(
                        text = if (orderNotes.isBlank()) {
                            stringResource(R.string.none_label)
                        } else {
                            orderNotes
                        },
                        modifier = Modifier.padding(top = 8.dp),
                        color = colorResource(R.color.text_primary),
                        fontSize = 15.sp,
                    )
                }
            }

            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 10.dp),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                border = BorderStroke(1.dp, colorResource(R.color.divider)),
            ) {
                Text(
                    text = stringResource(R.string.payment_due_visit_note),
                    modifier = Modifier.padding(12.dp),
                    color = colorResource(R.color.text_secondary),
                    fontSize = 14.sp,
                )
            }
        }

        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(20.dp),
            colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
            elevation = CardDefaults.cardElevation(defaultElevation = 12.dp),
        ) {
            Column(modifier = Modifier.padding(20.dp)) {
                val placing = orderResult is Resource.Loading
                Button(
                    onClick = { viewModel.placeOrder(orderNotes) },
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(52.dp),
                    enabled = !placing,
                    shape = RoundedCornerShape(14.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = colorResource(R.color.primary),
                    ),
                ) {
                    if (placing) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(22.dp),
                            color = colorResource(R.color.on_primary),
                            strokeWidth = 2.dp,
                        )
                    } else {
                        Text(
                            text = stringResource(R.string.checkout_place_order),
                            color = colorResource(R.color.on_primary),
                            fontSize = 15.sp,
                        )
                    }
                }
                OutlinedButton(
                    onClick = onEditOrder,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(52.dp)
                        .padding(top = 8.dp),
                    enabled = !placing,
                    shape = RoundedCornerShape(14.dp),
                ) {
                    Text(
                        text = stringResource(R.string.edit_order),
                        fontSize = 15.sp,
                    )
                }
            }
        }
    }
}

@Composable
private fun appointmentSummaryText(
    id: Int?,
    appointmentsById: Map<Int, Appointment>,
): String {
    if (id == null) return stringResource(R.string.none_linked)
    val selected = appointmentsById[id] ?: return "Appointment #$id"
    val type = selected.appointmentType ?: stringResource(R.string.appointment_label)
    val schedule = selected.scheduledAt ?: stringResource(R.string.schedule_tbd)
    val doctor = selected.doctorName ?: stringResource(R.string.doctor_tbd)
    return "$type\n$schedule\n$doctor"
}
