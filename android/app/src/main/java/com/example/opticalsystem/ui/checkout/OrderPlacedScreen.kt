package com.example.opticalsystem.ui.checkout

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Check
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
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
    val primary = colorResource(R.color.primary)
    val onPrimary = colorResource(R.color.on_primary)
    val divider = colorResource(R.color.divider)
    val surface = colorResource(R.color.surface)

    val steps = listOf(
        TimelineStep(
            title = stringResource(R.string.timeline_order_submitted),
            subtitle = stringResource(R.string.timeline_order_submitted_sub),
            isComplete = true,
        ),
        TimelineStep(
            title = stringResource(R.string.timeline_staff_confirms),
            subtitle = stringResource(R.string.timeline_staff_confirms_sub),
            isComplete = false,
        ),
        TimelineStep(
            title = stringResource(R.string.timeline_appointment_pickup),
            subtitle = stringResource(R.string.timeline_appointment_pickup_sub),
            isComplete = false,
        ),
        TimelineStep(
            title = stringResource(R.string.timeline_payment_collected),
            subtitle = stringResource(
                R.string.timeline_payment_due_format,
                orderTotal,
            ),
            isComplete = false,
        ),
    )

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(colorResource(R.color.background)),
    ) {
        Column(
            modifier = Modifier
                .weight(1f)
                .fillMaxWidth()
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 20.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Spacer(modifier = Modifier.height(28.dp))
            Box(
                modifier = Modifier
                    .size(88.dp)
                    .clip(CircleShape)
                    .background(primary),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    imageVector = Icons.Filled.Check,
                    contentDescription = null,
                    modifier = Modifier.size(44.dp),
                    tint = onPrimary,
                )
            }
            Text(
                text = stringResource(R.string.order_number_compact, orderNumber),
                modifier = Modifier.padding(top = 20.dp),
                color = colorResource(R.color.text_secondary),
                fontSize = 13.sp,
                fontWeight = FontWeight.Medium,
                textAlign = TextAlign.Center,
            )
            Text(
                text = stringResource(R.string.order_submitted_title),
                modifier = Modifier.padding(top = 8.dp),
                color = colorResource(R.color.text_primary),
                fontSize = 28.sp,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center,
                lineHeight = 34.sp,
            )
            Text(
                text = stringResource(R.string.order_placed_message),
                modifier = Modifier.padding(top = 12.dp),
                color = colorResource(R.color.text_primary),
                fontSize = 15.sp,
                fontWeight = FontWeight.Normal,
                textAlign = TextAlign.Center,
                lineHeight = 22.sp,
            )

            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 28.dp),
                shape = RoundedCornerShape(20.dp),
                colors = CardDefaults.cardColors(containerColor = surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 6.dp),
                border = BorderStroke(1.dp, divider),
            ) {
                Column(modifier = Modifier.padding(horizontal = 20.dp, vertical = 22.dp)) {
                    steps.forEachIndexed { index, step ->
                        TimelineRow(
                            step = step,
                            showConnector = index < steps.lastIndex,
                            connectorColor = divider,
                            surfaceColor = surface,
                            primaryColor = primary,
                        )
                    }
                }
            }
            Spacer(modifier = Modifier.height(32.dp))
        }

        Row(
            modifier = Modifier
                .fillMaxWidth()
                .navigationBarsPadding()
                .padding(horizontal = 20.dp, vertical = 16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            val buttonShape = RoundedCornerShape(16.dp)
            val footerButtonPadding = PaddingValues(horizontal = 8.dp, vertical = 12.dp)
            OutlinedButton(
                onClick = onViewOrder,
                modifier = Modifier
                    .weight(1f)
                    .heightIn(min = 52.dp),
                shape = buttonShape,
                border = BorderStroke(1.5.dp, primary),
                contentPadding = footerButtonPadding,
            ) {
                Text(
                    text = stringResource(R.string.view_order),
                    modifier = Modifier.fillMaxWidth(),
                    fontSize = 15.sp,
                    fontWeight = FontWeight.SemiBold,
                    textAlign = TextAlign.Center,
                    maxLines = 1,
                )
            }
            OutlinedButton(
                onClick = onBackToCatalog,
                modifier = Modifier
                    .weight(1f)
                    .heightIn(min = 52.dp),
                shape = buttonShape,
                border = BorderStroke(1.5.dp, primary),
                contentPadding = footerButtonPadding,
            ) {
                Text(
                    text = stringResource(R.string.back_to_catalog),
                    modifier = Modifier.fillMaxWidth(),
                    fontSize = 14.sp,
                    fontWeight = FontWeight.SemiBold,
                    textAlign = TextAlign.Center,
                    lineHeight = 18.sp,
                    maxLines = 2,
                )
            }
        }
    }
}

private data class TimelineStep(
    val title: String,
    val subtitle: String,
    val isComplete: Boolean,
)

@Composable
private fun TimelineRow(
    step: TimelineStep,
    showConnector: Boolean,
    connectorColor: Color,
    surfaceColor: Color,
    primaryColor: Color,
) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        verticalAlignment = Alignment.Top,
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.padding(end = 14.dp),
        ) {
            val dotSize = 12.dp
            if (step.isComplete) {
                Box(
                    modifier = Modifier
                        .size(dotSize)
                        .clip(CircleShape)
                        .background(primaryColor),
                )
            } else {
                Box(
                    modifier = Modifier
                        .size(dotSize)
                        .clip(CircleShape)
                        .background(surfaceColor)
                        .border(2.dp, connectorColor, CircleShape),
                )
            }
            if (showConnector) {
                Box(
                    modifier = Modifier
                        .width(2.dp)
                        .height(36.dp)
                        .background(connectorColor),
                )
            }
        }
        Column(
            modifier = Modifier
                .weight(1f)
                .padding(bottom = if (showConnector) 4.dp else 0.dp),
        ) {
            Text(
                text = step.title,
                color = colorResource(R.color.text_primary),
                fontSize = 15.sp,
                fontWeight = FontWeight.Bold,
                lineHeight = 20.sp,
            )
            Text(
                text = step.subtitle,
                modifier = Modifier.padding(top = 4.dp),
                color = colorResource(R.color.text_secondary),
                fontSize = 13.sp,
                lineHeight = 18.sp,
            )
        }
    }
}
