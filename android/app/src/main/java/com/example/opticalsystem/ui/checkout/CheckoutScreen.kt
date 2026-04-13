package com.example.opticalsystem.ui.checkout

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.compose.runtime.livedata.observeAsState
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.Appointment
import com.example.opticalsystem.util.Resource

@Composable
fun CheckoutScreen(
    viewModel: CheckoutViewModel,
    onBack: () -> Unit,
    onReviewOrder: () -> Unit,
) {
    LaunchedEffect(Unit) {
        viewModel.loadOrderDetailsData()
    }

    val selectedAppointmentId by viewModel.selectedAppointmentId.collectAsStateWithLifecycle()
    val orderNotes by viewModel.orderNotes.collectAsStateWithLifecycle()
    val upcoming by viewModel.upcomingAppointments.observeAsState()
    val profile by viewModel.profile.observeAsState()

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(colorResource(R.color.background)),
    ) {
        CheckoutFlowHeader(
            title = stringResource(R.string.order_details_title),
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
                    .padding(top = 12.dp),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                border = BorderStroke(1.dp, colorResource(R.color.divider)),
            ) {
                Column(Modifier.padding(16.dp)) {
                    Text(
                        text = stringResource(R.string.link_appointment_optional),
                        color = colorResource(R.color.text_primary),
                        fontWeight = FontWeight.Bold,
                    )
                    Text(
                        text = stringResource(R.string.link_appointment_optional_suffix),
                        modifier = Modifier.padding(top = 2.dp),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 12.sp,
                    )
                    when (val res = upcoming) {
                        is Resource.Loading -> {
                            CircularProgressIndicator(
                                modifier = Modifier
                                    .padding(top = 12.dp)
                                    .size(32.dp)
                                    .align(Alignment.CenterHorizontally),
                            )
                        }
                        is Resource.Success -> {
                            Spacer(modifier = Modifier.height(8.dp))
                            res.data.forEach { appt ->
                                AppointmentOptionCard(
                                    appointment = appt,
                                    selected = appt.id == selectedAppointmentId,
                                    onClick = {
                                        val current = selectedAppointmentId
                                        viewModel.selectAppointment(
                                            if (current == appt.id) null else appt.id,
                                        )
                                    },
                                    modifier = Modifier.padding(bottom = 8.dp),
                                )
                            }
                        }
                        else -> Unit
                    }
                    if (selectedAppointmentId == null) {
                        Text(
                            text = stringResource(R.string.no_appointment_selected_note),
                            modifier = Modifier.padding(top = 8.dp),
                            color = colorResource(R.color.text_secondary),
                            fontSize = 12.sp,
                        )
                    }
                }
            }

            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 12.dp),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                border = BorderStroke(1.dp, colorResource(R.color.divider)),
            ) {
                Column(Modifier.padding(16.dp)) {
                    Text(
                        text = stringResource(R.string.order_notes_section),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Medium,
                    )
                    OutlinedTextField(
                        value = orderNotes,
                        onValueChange = { viewModel.setOrderNotes(it) },
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 8.dp)
                            .heightIn(min = 120.dp),
                        placeholder = {
                            Text(stringResource(R.string.order_notes_placeholder))
                        },
                        minLines = 5,
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedContainerColor = colorResource(R.color.surface),
                            unfocusedContainerColor = colorResource(R.color.surface),
                        ),
                    )
                }
            }

            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 12.dp),
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                border = BorderStroke(1.dp, colorResource(R.color.divider)),
            ) {
                Column(Modifier.padding(16.dp)) {
                    Text(
                        text = stringResource(R.string.contact_details),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Medium,
                    )
                    when (val res = profile) {
                        is Resource.Success -> {
                            Text(
                                text = res.data.name,
                                modifier = Modifier.padding(top = 8.dp),
                                color = colorResource(R.color.text_primary),
                                fontSize = 16.sp,
                                fontWeight = FontWeight.Bold,
                            )
                            Text(
                                text = res.data.phone ?: stringResource(R.string.not_provided),
                                modifier = Modifier.padding(top = 2.dp),
                                color = colorResource(R.color.text_secondary),
                                fontSize = 16.sp,
                            )
                        }
                        is Resource.Loading -> {
                            CircularProgressIndicator(
                                modifier = Modifier
                                    .padding(top = 12.dp)
                                    .size(28.dp),
                            )
                        }
                        else -> Unit
                    }
                }
            }
        }

        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(20.dp),
            colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
            elevation = CardDefaults.cardElevation(defaultElevation = 12.dp),
        ) {
            Column(modifier = Modifier.padding(20.dp)) {
                Button(
                    onClick = onReviewOrder,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(52.dp),
                    shape = RoundedCornerShape(14.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = colorResource(R.color.primary),
                    ),
                ) {
                    Text(
                        text = stringResource(R.string.review_order),
                        color = colorResource(R.color.on_primary),
                        fontSize = 15.sp,
                    )
                }
            }
        }
    }
}

/** Full-width primary bar: back icon + page title (same pattern as cart top bar). */
@Composable
internal fun CheckoutFlowHeader(
    title: String,
    onBack: () -> Unit,
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
            text = title,
            modifier = Modifier
                .weight(1f)
                .padding(start = 4.dp),
            color = colorResource(R.color.on_primary),
            fontSize = 22.sp,
            fontWeight = FontWeight.Bold,
        )
    }
}

@Composable
private fun AppointmentOptionCard(
    appointment: Appointment,
    selected: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
) {
    Card(
        onClick = onClick,
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = colorResource(R.color.surface)),
        border = BorderStroke(
            width = if (selected) 2.dp else 1.dp,
            color = if (selected) {
                colorResource(R.color.primary)
            } else {
                colorResource(R.color.divider)
            },
        ),
    ) {
        Column(Modifier.padding(12.dp)) {
            Text(
                text = appointment.appointmentType ?: stringResource(R.string.appointment_label),
                color = colorResource(R.color.text_primary),
                fontSize = 14.sp,
                fontWeight = FontWeight.Bold,
            )
            Text(
                text = appointment.doctorName ?: stringResource(R.string.doctor_tbd),
                modifier = Modifier.padding(top = 2.dp),
                color = colorResource(R.color.text_secondary),
                fontSize = 12.sp,
            )
            Text(
                text = appointment.scheduledAt ?: stringResource(R.string.schedule_tbd),
                modifier = Modifier.padding(top = 2.dp),
                color = colorResource(R.color.text_secondary),
                fontSize = 12.sp,
            )
        }
    }
}
