package com.example.opticalsystem.ui.profile

import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.graphicsLayer
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
fun ProfileScreen(
    viewModel: ProfileViewModel,
    onNavigateToOrders: () -> Unit,
    onNavigateToBills: () -> Unit,
    onLoggedOut: () -> Unit,
) {
    val context = LocalContext.current
    LaunchedEffect(Unit) {
        viewModel.loadProfile()
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
    var userName by remember { mutableStateOf("User") }
    var userEmail by remember { mutableStateOf("") }
    LaunchedEffect(profile) {
        when (val p = profile) {
            is Resource.Success -> {
                userName = p.data.name
                userEmail = p.data.email
            }
            is Resource.Error -> {
                userName = "User"
                userEmail = ""
            }
            else -> {}
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        stringResource(R.string.nav_profile),
                        color = colorResource(R.color.text_primary),
                    )
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = colorResource(R.color.background),
                ),
            )
        },
        containerColor = colorResource(R.color.background),
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
                .padding(horizontal = 16.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Spacer(Modifier.height(8.dp))
            Box(
                modifier = Modifier
                    .size(80.dp)
                    .padding(0.dp),
                contentAlignment = Alignment.Center,
            ) {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    shape = RoundedCornerShape(12.dp),
                    color = colorResource(R.color.status_confirmed_bg),
                ) {
                    Box(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(16.dp),
                        contentAlignment = Alignment.Center,
                    ) {
                        Icon(
                            painter = painterResource(R.drawable.ic_person_24),
                            contentDescription = stringResource(R.string.profile_avatar),
                            tint = colorResource(R.color.primary),
                            modifier = Modifier.fillMaxSize(),
                        )
                    }
                }
            }
            Text(
                text = userName,
                color = colorResource(R.color.text_primary),
                fontSize = 20.sp,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(top = 12.dp),
            )
            if (userEmail.isNotEmpty()) {
                Text(
                    text = userEmail,
                    color = colorResource(R.color.text_secondary),
                    fontSize = 14.sp,
                    modifier = Modifier.padding(top = 4.dp),
                )
            }
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 24.dp),
            ) {
                ProfileMenuRow(
                    icon = R.drawable.ic_orders_24,
                    label = stringResource(R.string.profile_my_orders),
                    iconContentDescription = stringResource(R.string.cd_orders_menu),
                    onClick = onNavigateToOrders,
                )
                Spacer(Modifier.height(8.dp))
                ProfileMenuRow(
                    icon = R.drawable.ic_orders_24,
                    label = stringResource(R.string.profile_my_bills),
                    iconContentDescription = stringResource(R.string.cd_bills_menu),
                    onClick = onNavigateToBills,
                )
            }
            Spacer(modifier = Modifier.weight(1f))
            OutlinedButton(
                onClick = { viewModel.logout() },
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 16.dp),
                enabled = !loadingLogout,
                shape = RoundedCornerShape(12.dp),
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

@Composable
private fun ProfileMenuRow(
    icon: Int,
    label: String,
    iconContentDescription: String,
    onClick: () -> Unit,
) {
    Surface(
        onClick = onClick,
        shape = RoundedCornerShape(12.dp),
        color = colorResource(R.color.surface),
        border = BorderStroke(1.dp, colorResource(R.color.divider)),
        modifier = Modifier
            .fillMaxWidth()
            .height(56.dp),
    ) {
        Row(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 16.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Icon(
                painter = painterResource(icon),
                contentDescription = iconContentDescription,
                tint = colorResource(R.color.primary),
                modifier = Modifier.size(24.dp),
            )
            Text(
                text = label,
                modifier = Modifier
                    .weight(1f)
                    .padding(start = 16.dp),
                color = colorResource(R.color.text_primary),
                fontSize = 16.sp,
            )
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
