package com.example.opticalsystem.ui.auth

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.ClickableText
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.withStyle
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.navigationBars
import androidx.compose.foundation.layout.windowInsetsPadding
import androidx.compose.runtime.livedata.observeAsState
import com.example.opticalsystem.R
import com.example.opticalsystem.util.Resource

@Composable
fun RegisterScreen(
    viewModel: RegisterViewModel,
    logoUrl: String,
    onNavigateToLogin: () -> Unit,
    onNavigateToMain: () -> Unit,
) {
    val context = LocalContext.current
    var name by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }
    var passwordVisible by remember { mutableStateOf(false) }
    var confirmVisible by remember { mutableStateOf(false) }

    val result by viewModel.registerResult.observeAsState()

    LaunchedEffect(result) {
        when (val r = result) {
            is Resource.Success -> onNavigateToMain()
            is Resource.Error ->
                Toast.makeText(context, r.message, Toast.LENGTH_LONG).show()
            else -> {}
        }
    }

    val loading = result is Resource.Loading
    val gradient = Brush.verticalGradient(
        colors = listOf(Color(0xFFDCEEFF), Color.White),
    )

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(gradient)
            .windowInsetsPadding(WindowInsets.navigationBars)
            .padding(20.dp),
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .verticalScroll(rememberScrollState()),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Spacer(Modifier.height(20.dp))
            AuthRemoteLogo(
                logoUrl = logoUrl,
                modifier = Modifier.size(72.dp),
            )
            Spacer(Modifier.height(14.dp))
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(18.dp),
                elevation = CardDefaults.cardElevation(defaultElevation = 6.dp),
                colors = CardDefaults.cardColors(
                    containerColor = MaterialTheme.colorScheme.surface,
                ),
            ) {
                Column(Modifier.padding(18.dp)) {
                    Text(
                        text = stringResource(R.string.register_title),
                        style = MaterialTheme.typography.headlineSmall,
                        color = colorResource(R.color.login_title),
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.align(Alignment.CenterHorizontally),
                    )
                    Text(
                        text = stringResource(R.string.register_subtitle),
                        fontSize = 12.sp,
                        color = colorResource(R.color.login_hint),
                        modifier = Modifier
                            .align(Alignment.CenterHorizontally)
                            .padding(top = 4.dp),
                    )
                    OutlinedTextField(
                        value = name,
                        onValueChange = { name = it },
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 16.dp),
                        label = { Text(stringResource(R.string.full_name)) },
                        singleLine = true,
                        shape = RoundedCornerShape(10.dp),
                        colors = authFieldColors(),
                    )
                    OutlinedTextField(
                        value = email,
                        onValueChange = { email = it },
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 10.dp),
                        label = { Text(stringResource(R.string.email)) },
                        singleLine = true,
                        leadingIcon = {
                            Icon(
                                painter = painterResource(R.drawable.ic_email_24),
                                contentDescription = null,
                                tint = colorResource(R.color.login_hint),
                            )
                        },
                        shape = RoundedCornerShape(10.dp),
                        colors = authFieldColors(),
                    )
                    OutlinedTextField(
                        value = phone,
                        onValueChange = { phone = it },
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 10.dp),
                        label = { Text(stringResource(R.string.phone)) },
                        singleLine = true,
                        shape = RoundedCornerShape(10.dp),
                        colors = authFieldColors(),
                    )
                    OutlinedTextField(
                        value = password,
                        onValueChange = { password = it },
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 10.dp),
                        label = { Text(stringResource(R.string.password)) },
                        singleLine = true,
                        visualTransformation = if (passwordVisible) {
                            VisualTransformation.None
                        } else {
                            PasswordVisualTransformation()
                        },
                        leadingIcon = {
                            Icon(
                                painter = painterResource(R.drawable.ic_lock_24),
                                contentDescription = null,
                                tint = colorResource(R.color.login_hint),
                            )
                        },
                        trailingIcon = {
                            IconButton(onClick = { passwordVisible = !passwordVisible }) {
                                Icon(
                                    imageVector = if (passwordVisible) {
                                        Icons.Filled.VisibilityOff
                                    } else {
                                        Icons.Filled.Visibility
                                    },
                                    contentDescription = null,
                                    tint = colorResource(R.color.login_hint),
                                )
                            }
                        },
                        shape = RoundedCornerShape(10.dp),
                        colors = authFieldColors(),
                    )
                    OutlinedTextField(
                        value = confirmPassword,
                        onValueChange = { confirmPassword = it },
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 10.dp),
                        label = { Text(stringResource(R.string.confirm_password)) },
                        singleLine = true,
                        visualTransformation = if (confirmVisible) {
                            VisualTransformation.None
                        } else {
                            PasswordVisualTransformation()
                        },
                        leadingIcon = {
                            Icon(
                                painter = painterResource(R.drawable.ic_lock_24),
                                contentDescription = null,
                                tint = colorResource(R.color.login_hint),
                            )
                        },
                        trailingIcon = {
                            IconButton(onClick = { confirmVisible = !confirmVisible }) {
                                Icon(
                                    imageVector = if (confirmVisible) {
                                        Icons.Filled.VisibilityOff
                                    } else {
                                        Icons.Filled.Visibility
                                    },
                                    contentDescription = null,
                                    tint = colorResource(R.color.login_hint),
                                )
                            }
                        },
                        shape = RoundedCornerShape(10.dp),
                        colors = authFieldColors(),
                    )
                    Button(
                        onClick = {
                            val n = name.trim()
                            val e = email.trim()
                            val ph = phone.trim().ifEmpty { null }
                            val p = password.trim()
                            val c = confirmPassword.trim()
                            if (n.isEmpty() || e.isEmpty() || p.isEmpty() || c.isEmpty()) {
                                Toast.makeText(
                                    context,
                                    "Please fill in all required fields",
                                    Toast.LENGTH_SHORT,
                                ).show()
                            } else if (p != c) {
                                Toast.makeText(
                                    context,
                                    "Passwords do not match",
                                    Toast.LENGTH_SHORT,
                                ).show()
                            } else {
                                viewModel.register(n, e, ph, p, c)
                            }
                        },
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 14.dp)
                            .height(48.dp),
                        enabled = !loading,
                        shape = RoundedCornerShape(14.dp),
                        colors = ButtonDefaults.buttonColors(
                            containerColor = colorResource(R.color.login_primary),
                            contentColor = Color.White,
                            disabledContainerColor = colorResource(R.color.login_primary).copy(alpha = 0.5f),
                        ),
                    ) {
                        Text(
                            stringResource(R.string.register),
                            color = Color.White,
                        )
                    }
                    Spacer(Modifier.height(10.dp))
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(24.dp),
                        contentAlignment = Alignment.Center,
                    ) {
                        if (loading) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(24.dp),
                                color = colorResource(R.color.login_primary),
                                strokeWidth = 2.dp,
                            )
                        }
                    }
                    Spacer(Modifier.height(14.dp))
                    val signInAnnotated = buildAnnotatedString {
                        withStyle(
                            SpanStyle(color = colorResource(R.color.login_hint), fontSize = 12.sp),
                        ) {
                            append(stringResource(R.string.have_account))
                        }
                        append(" ")
                        pushStringAnnotation(tag = "signin", annotation = "signin")
                        withStyle(
                            SpanStyle(
                                color = colorResource(R.color.login_link),
                                fontSize = 12.sp,
                                fontWeight = FontWeight.Bold,
                            ),
                        ) {
                            append(stringResource(R.string.sign_in))
                        }
                        pop()
                    }
                    Box(
                        modifier = Modifier.fillMaxWidth(),
                        contentAlignment = Alignment.Center,
                    ) {
                        ClickableText(
                            text = signInAnnotated,
                            onClick = { offset ->
                                signInAnnotated.getStringAnnotations(
                                    tag = "signin",
                                    start = offset,
                                    end = offset,
                                ).firstOrNull()?.let { onNavigateToLogin() }
                            },
                        )
                    }
                }
            }
        }
    }
}
