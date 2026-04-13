package com.example.opticalsystem.ui.profile

import android.widget.ImageView
import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.Edit
import androidx.compose.material.icons.outlined.LocalShipping
import androidx.compose.material.icons.outlined.Notifications
import androidx.compose.material.icons.outlined.ReceiptLong
import androidx.compose.material.icons.outlined.ShoppingBag
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.runtime.livedata.observeAsState
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import com.bumptech.glide.Glide
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.User
import com.example.opticalsystem.util.BackendImageUrl
import com.example.opticalsystem.util.Resource

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProfileScreen(
    viewModel: ProfileViewModel,
    onNavigateToEditProfile: () -> Unit,
    onNavigateToOrders: () -> Unit,
    onNavigateToBills: () -> Unit,
    onNavigateToNotifications: () -> Unit,
    onNavigateToCatalog: () -> Unit,
    onLoggedOut: () -> Unit,
) {
    val context = LocalContext.current
    val lifecycleOwner = LocalLifecycleOwner.current

    DisposableEffect(lifecycleOwner) {
        val observer = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_RESUME) {
                viewModel.loadProfile()
            }
        }
        lifecycleOwner.lifecycle.addObserver(observer)
        onDispose { lifecycleOwner.lifecycle.removeObserver(observer) }
    }

    val profile by viewModel.profile.observeAsState()
    val logoutResult by viewModel.logoutResult.observeAsState()

    LaunchedEffect(logoutResult) {
        when (val r = logoutResult) {
            is Resource.Success -> onLoggedOut()
            is Resource.Error ->
                Toast.makeText(context, r.message, Toast.LENGTH_SHORT).show()
            else -> {}
        }
    }

    val loadingLogout = logoutResult is Resource.Loading
    var showLogoutConfirm by remember { mutableStateOf(false) }

    val user = (profile as? Resource.Success)?.data
    val initialLoading = profile is Resource.Loading && user == null
    val loadError = profile is Resource.Error && user == null

    val versionName = remember {
        try {
            context.packageManager.getPackageInfo(context.packageName, 0).versionName ?: "1.0"
        } catch (_: Exception) {
            "1.0"
        }
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
                .padding(start = 20.dp, end = 20.dp, top = 16.dp, bottom = 16.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Text(
                text = stringResource(R.string.nav_profile),
                color = colorResource(R.color.on_primary),
                fontSize = 22.sp,
                fontWeight = FontWeight.Bold,
            )
        }

        Column(
            modifier = Modifier
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 16.dp),
        ) {
            Spacer(Modifier.height(16.dp))
            when {
                initialLoading -> {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(200.dp),
                        contentAlignment = Alignment.Center,
                    ) {
                        CircularProgressIndicator(color = colorResource(R.color.primary))
                    }
                }
                loadError -> {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(24.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                    ) {
                        Text(
                            text = (profile as Resource.Error).message,
                            color = colorResource(R.color.text_primary),
                        )
                        Spacer(Modifier.height(16.dp))
                        OutlinedButton(onClick = { viewModel.loadProfile() }) {
                            Text(stringResource(R.string.retry))
                        }
                    }
                }
                user != null -> {
                    ProfileSummaryCard(user = user)
                    Spacer(Modifier.height(20.dp))
                    ProfileSectionTitle(stringResource(R.string.profile_section_account))
                    Spacer(Modifier.height(8.dp))
                    ProfileMenuRow(
                        icon = Icons.Outlined.Edit,
                        label = stringResource(R.string.profile_edit_profile),
                        onClick = onNavigateToEditProfile,
                    )
                    Spacer(Modifier.height(8.dp))
                    ProfileMenuRow(
                        icon = Icons.Outlined.LocalShipping,
                        label = stringResource(R.string.profile_my_orders),
                        onClick = onNavigateToOrders,
                    )
                    Spacer(Modifier.height(8.dp))
                    ProfileMenuRow(
                        icon = Icons.Outlined.ReceiptLong,
                        label = stringResource(R.string.profile_my_bills),
                        onClick = onNavigateToBills,
                    )
                    Spacer(Modifier.height(20.dp))
                    ProfileSectionTitle(stringResource(R.string.profile_section_clinic))
                    Spacer(Modifier.height(8.dp))
                    ProfileMenuRow(
                        icon = Icons.Outlined.Notifications,
                        label = stringResource(R.string.order_notifications_title),
                        subtitle = stringResource(R.string.profile_notifications_sub),
                        onClick = onNavigateToNotifications,
                    )
                    Spacer(Modifier.height(20.dp))
                    ProfileSectionTitle(stringResource(R.string.profile_section_shopping))
                    Spacer(Modifier.height(8.dp))
                    ProfileMenuRow(
                        icon = Icons.Outlined.ShoppingBag,
                        label = stringResource(R.string.profile_browse_catalog),
                        subtitle = stringResource(R.string.profile_browse_catalog_sub),
                        onClick = onNavigateToCatalog,
                    )
                }
            }

            if (user != null) {
                Spacer(Modifier.height(24.dp))
                Text(
                    text = stringResource(R.string.profile_app_version, versionName),
                    modifier = Modifier.fillMaxWidth(),
                    color = colorResource(R.color.text_secondary),
                    fontSize = 12.sp,
                    textAlign = TextAlign.Center,
                )
                Spacer(Modifier.height(16.dp))
                OutlinedButton(
                    onClick = { showLogoutConfirm = true },
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 20.dp),
                    enabled = !loadingLogout,
                    shape = RoundedCornerShape(14.dp),
                    colors = ButtonDefaults.outlinedButtonColors(
                        contentColor = colorResource(R.color.status_cancelled),
                    ),
                    border = BorderStroke(1.dp, colorResource(R.color.status_cancelled)),
                ) {
                    Text(stringResource(R.string.logout))
                }
            }
        }
    }

    if (showLogoutConfirm) {
        AlertDialog(
            onDismissRequest = { if (!loadingLogout) showLogoutConfirm = false },
            title = {
                Text(
                    stringResource(R.string.logout_confirm_title),
                    color = colorResource(R.color.text_primary),
                )
            },
            text = {
                Text(
                    stringResource(R.string.logout_confirm_message),
                    color = colorResource(R.color.text_secondary),
                )
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        showLogoutConfirm = false
                        viewModel.logout()
                    },
                    enabled = !loadingLogout,
                ) {
                    Text(
                        stringResource(R.string.logout_confirm_action),
                        color = colorResource(R.color.status_cancelled),
                    )
                }
            },
            dismissButton = {
                TextButton(
                    onClick = { showLogoutConfirm = false },
                    enabled = !loadingLogout,
                ) {
                    Text(stringResource(R.string.action_cancel))
                }
            },
        )
    }
}

@Composable
private fun ProfileSectionTitle(text: String) {
    Text(
        text = text,
        color = colorResource(R.color.text_secondary),
        fontSize = 13.sp,
        fontWeight = FontWeight.SemiBold,
        letterSpacing = 0.02.sp,
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun ProfileMenuRow(
    icon: ImageVector,
    label: String,
    subtitle: String? = null,
    onClick: () -> Unit,
) {
    Surface(
        onClick = onClick,
        shape = RoundedCornerShape(12.dp),
        color = colorResource(R.color.surface),
        border = BorderStroke(1.dp, colorResource(R.color.divider)),
        modifier = Modifier
            .fillMaxWidth()
            .height(if (subtitle != null) 72.dp else 56.dp),
    ) {
        Row(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 16.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Icon(
                imageVector = icon,
                contentDescription = null,
                tint = colorResource(R.color.primary),
                modifier = Modifier.size(24.dp),
            )
            Column(
                modifier = Modifier
                    .weight(1f)
                    .padding(start = 16.dp),
            ) {
                Text(
                    text = label,
                    color = colorResource(R.color.text_primary),
                    fontSize = 16.sp,
                )
                if (subtitle != null) {
                    Text(
                        text = subtitle,
                        color = colorResource(R.color.text_secondary),
                        fontSize = 12.sp,
                        modifier = Modifier.padding(top = 2.dp),
                    )
                }
            }
            Icon(
                painter = painterResource(R.drawable.ic_back_24),
                contentDescription = stringResource(R.string.cd_go),
                tint = colorResource(R.color.text_secondary),
                modifier = Modifier
                    .size(20.dp)
                    .graphicsLayer { rotationZ = 180f },
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun ProfileSummaryCard(user: User) {
    val context = LocalContext.current
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(14.dp),
        color = colorResource(R.color.surface),
        border = BorderStroke(1.dp, colorResource(R.color.divider)),
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(20.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            val avatarUrl = user.avatarUrl
            if (!avatarUrl.isNullOrBlank()) {
                AndroidView(
                    factory = { ctx ->
                        ImageView(ctx).apply {
                            scaleType = ImageView.ScaleType.CENTER_CROP
                        }
                    },
                    modifier = Modifier
                        .size(88.dp)
                        .clip(CircleShape),
                    update = { iv ->
                        val full = BackendImageUrl.resolve(context, avatarUrl)
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
            } else {
                Surface(
                    modifier = Modifier.size(88.dp),
                    shape = CircleShape,
                    color = colorResource(R.color.status_confirmed_bg),
                ) {
                    Box(
                        modifier = Modifier.fillMaxSize(),
                        contentAlignment = Alignment.Center,
                    ) {
                        Icon(
                            painter = painterResource(R.drawable.ic_person_24),
                            contentDescription = stringResource(R.string.profile_avatar),
                            tint = colorResource(R.color.primary),
                            modifier = Modifier.size(44.dp),
                        )
                    }
                }
            }
            Text(
                text = user.name,
                color = colorResource(R.color.text_primary),
                fontSize = 20.sp,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(top = 12.dp),
            )
            Text(
                text = user.email,
                color = colorResource(R.color.text_secondary),
                fontSize = 14.sp,
                modifier = Modifier.padding(top = 4.dp),
            )
            if (!user.phone.isNullOrBlank()) {
                Text(
                    text = user.phone,
                    color = colorResource(R.color.text_secondary),
                    fontSize = 14.sp,
                    modifier = Modifier.padding(top = 2.dp),
                )
            }
            Text(
                text = stringResource(R.string.profile_member_since, memberSinceLabel(user.createdAt)),
                color = colorResource(R.color.text_secondary),
                fontSize = 12.sp,
                modifier = Modifier.padding(top = 12.dp),
            )
        }
    }
}

private fun memberSinceLabel(iso: String): String {
    return try {
        iso.takeWhile { it != 'T' }.take(10).ifBlank { iso.take(10) }
    } catch (_: Exception) {
        iso.take(10)
    }
}
