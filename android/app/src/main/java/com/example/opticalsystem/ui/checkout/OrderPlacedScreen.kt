package com.example.opticalsystem.ui.checkout

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.opticalsystem.R

@Composable
fun OrderPlacedScreen(
    orderNumber: String,
    orderTotal: String,
    onViewOrder: () -> Unit,
    onBackToCatalog: () -> Unit,
) {
    val timelineText = buildString {
        append("• ")
        append(stringResource(R.string.timeline_order_submitted))
        append("\n")
        append(stringResource(R.string.timeline_order_submitted_sub))
        append("\n\n")
        append("• ")
        append(stringResource(R.string.timeline_staff_confirms))
        append("\n")
        append(stringResource(R.string.timeline_staff_confirms_sub))
        append("\n\n")
        append("• ")
        append(stringResource(R.string.timeline_appointment_pickup))
        append("\n")
        append(stringResource(R.string.timeline_appointment_pickup_sub))
        append("\n\n")
        append("• ")
        append(stringResource(R.string.timeline_payment_collected))
        append("\n")
        append(stringResource(R.string.timeline_payment_due_format, orderTotal))
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(colorResource(R.color.background))
            .padding(16.dp),
    ) {
        Column(
            modifier = Modifier
                .weight(1f)
                .fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Card(
                modifier = Modifier
                    .padding(top = 8.dp)
                    .size(74.dp),
                shape = CircleShape,
                colors = CardDefaults.cardColors(
                    containerColor = colorResource(R.color.primary_light),
                ),
                elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
            ) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center,
                ) {
                    Text(
                        text = "✓",
                        color = colorResource(R.color.on_primary),
                        fontSize = 36.sp,
                    )
                }
            }
            Text(
                text = stringResource(R.string.order_number_compact, orderNumber),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 8.dp),
                color = colorResource(R.color.text_secondary),
                fontSize = 14.sp,
                textAlign = TextAlign.Center,
            )
            Text(
                text = stringResource(R.string.order_submitted_title),
                modifier = Modifier.fillMaxWidth(),
                color = colorResource(R.color.text_primary),
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center,
            )
            Text(
                text = stringResource(R.string.order_placed_message),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 8.dp),
                color = colorResource(R.color.text_primary),
                fontSize = 16.sp,
                textAlign = TextAlign.Center,
            )
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 16.dp),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                border = BorderStroke(1.dp, colorResource(R.color.divider)),
            ) {
                Text(
                    text = timelineText,
                    modifier = Modifier.padding(14.dp),
                    color = colorResource(R.color.text_primary),
                    fontSize = 14.sp,
                )
            }
        }
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(top = 12.dp),
            horizontalArrangement = Arrangement.spacedBy(16.dp),
        ) {
            Button(
                onClick = onViewOrder,
                modifier = Modifier
                    .weight(1f)
                    .height(52.dp),
                shape = RoundedCornerShape(14.dp),
                colors = ButtonDefaults.buttonColors(
                    containerColor = colorResource(R.color.primary),
                ),
            ) {
                Text(
                    text = stringResource(R.string.view_order),
                    color = colorResource(R.color.on_primary),
                )
            }
            OutlinedButton(
                onClick = onBackToCatalog,
                modifier = Modifier
                    .weight(1f)
                    .height(52.dp),
                shape = RoundedCornerShape(14.dp),
            ) {
                Text(stringResource(R.string.back_to_catalog))
            }
        }
    }
}
