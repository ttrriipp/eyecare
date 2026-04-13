package com.example.opticalsystem.ui.profile

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.runtime.livedata.observeAsState
import com.example.opticalsystem.R
import com.example.opticalsystem.util.Resource

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EditProfileScreen(
    viewModel: EditProfileViewModel,
    onBack: () -> Unit,
    onSaved: () -> Unit,
) {
    val context = LocalContext.current
    val profileLoad by viewModel.profileLoad.observeAsState()
    val saveResult by viewModel.saveResult.observeAsState()

    var name by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }
    var dateOfBirth by remember { mutableStateOf("") }
    var address by remember { mutableStateOf("") }

    val loadedUser = (profileLoad as? Resource.Success)?.data
    LaunchedEffect(loadedUser?.id, loadedUser?.updatedAt) {
        loadedUser?.let { u ->
            name = u.name
            phone = u.phone.orEmpty()
            dateOfBirth = u.dateOfBirth.orEmpty()
            address = u.address.orEmpty()
        }
    }

    LaunchedEffect(saveResult) {
        when (val r = saveResult) {
            is Resource.Success -> {
                Toast.makeText(context, R.string.profile_update_success, Toast.LENGTH_SHORT).show()
                viewModel.clearSaveResult()
                onSaved()
            }
            is Resource.Error -> {
                Toast.makeText(context, r.message, Toast.LENGTH_LONG).show()
                viewModel.clearSaveResult()
            }
            else -> {}
        }
    }

    val primary = colorResource(R.color.primary)
    val onPrimary = colorResource(R.color.on_primary)
    val fieldColors = OutlinedTextFieldDefaults.colors(
        focusedBorderColor = primary,
        focusedLabelColor = primary,
        cursorColor = primary,
        unfocusedBorderColor = colorResource(R.color.divider),
    )

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
                    tint = onPrimary,
                )
            }
            Text(
                text = stringResource(R.string.profile_edit_title),
                modifier = Modifier
                    .weight(1f)
                    .padding(start = 4.dp),
                color = onPrimary,
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
            )
        }

        when (val load = profileLoad) {
            null, is Resource.Loading -> {
                Column(
                    modifier = Modifier.fillMaxSize(),
                    horizontalAlignment = Alignment.CenterHorizontally,
                ) {
                    Spacer(Modifier.weight(1f))
                    CircularProgressIndicator(color = primary)
                    Spacer(Modifier.weight(1f))
                }
            }
            is Resource.Error -> {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(24.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                ) {
                    Text(
                        text = load.message,
                        color = colorResource(R.color.text_primary),
                    )
                    Spacer(Modifier.height(16.dp))
                    Button(onClick = { viewModel.loadProfile() }) {
                        Text(stringResource(R.string.retry))
                    }
                }
            }
            is Resource.Success -> {
                val saving = saveResult is Resource.Loading
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .verticalScroll(rememberScrollState())
                        .padding(horizontal = 16.dp, vertical = 20.dp),
                ) {
                    Text(
                        text = stringResource(R.string.profile_edit_subtitle),
                        color = colorResource(R.color.text_secondary),
                        fontSize = 14.sp,
                    )
                    Spacer(Modifier.height(16.dp))
                    OutlinedTextField(
                        value = name,
                        onValueChange = { name = it },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text(stringResource(R.string.profile_field_name)) },
                        singleLine = true,
                        enabled = !saving,
                        colors = fieldColors,
                    )
                    Spacer(Modifier.height(12.dp))
                    OutlinedTextField(
                        value = phone,
                        onValueChange = { phone = it },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text(stringResource(R.string.profile_field_phone)) },
                        singleLine = true,
                        enabled = !saving,
                        colors = fieldColors,
                    )
                    Spacer(Modifier.height(12.dp))
                    OutlinedTextField(
                        value = dateOfBirth,
                        onValueChange = { dateOfBirth = it },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text(stringResource(R.string.profile_field_dob)) },
                        placeholder = { Text(stringResource(R.string.profile_field_dob_hint)) },
                        singleLine = true,
                        enabled = !saving,
                        colors = fieldColors,
                    )
                    Spacer(Modifier.height(12.dp))
                    OutlinedTextField(
                        value = address,
                        onValueChange = { address = it },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text(stringResource(R.string.profile_field_address)) },
                        minLines = 3,
                        enabled = !saving,
                        colors = fieldColors,
                    )
                    Spacer(Modifier.height(24.dp))
                    Button(
                        onClick = {
                            if (name.isBlank()) {
                                Toast.makeText(
                                    context,
                                    R.string.profile_error_name_required,
                                    Toast.LENGTH_SHORT,
                                ).show()
                            } else {
                                viewModel.save(name, phone, dateOfBirth, address)
                            }
                        },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !saving,
                        shape = RoundedCornerShape(14.dp),
                        colors = ButtonDefaults.buttonColors(
                            containerColor = primary,
                            contentColor = onPrimary,
                        ),
                    ) {
                        if (saving) {
                            CircularProgressIndicator(
                                modifier = Modifier
                                    .size(22.dp)
                                    .padding(vertical = 2.dp),
                                color = onPrimary,
                                strokeWidth = 2.dp,
                            )
                        } else {
                            Text(stringResource(R.string.profile_save))
                        }
                    }
                }
            }
        }
    }
}
