package com.example.opticalsystem.ui.home

import androidx.compose.foundation.background
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.State
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.opticalsystem.R
import com.example.opticalsystem.ui.theme.EyeCareTheme

@Composable
fun HomeScreen(
    userName: String,
    onNotificationsClick: () -> Unit,
    notificationDot: State<Boolean>,
) {
    val showDot by notificationDot
    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(colorResource(R.color.background)),
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(start = 20.dp, top = 24.dp, end = 56.dp),
        ) {
            Text(
                text = stringResource(R.string.home_greeting),
                color = colorResource(R.color.text_primary),
                fontSize = 24.sp,
                fontWeight = FontWeight.Bold,
            )
            Text(
                text = userName,
                color = colorResource(R.color.primary),
                fontSize = 24.sp,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(top = 4.dp),
            )
            Text(
                text = stringResource(R.string.home_subtitle),
                color = colorResource(R.color.text_secondary),
                fontSize = 14.sp,
                modifier = Modifier.padding(top = 4.dp),
            )
        }
        Box(
            modifier = Modifier
                .align(Alignment.TopEnd)
                .padding(top = 20.dp, end = 16.dp)
                .size(40.dp),
        ) {
            IconButton(
                onClick = onNotificationsClick,
                modifier = Modifier.fillMaxSize(),
            ) {
                Icon(
                    painter = painterResource(R.drawable.ic_notifications_24),
                    contentDescription = stringResource(R.string.order_notifications_title),
                    tint = colorResource(R.color.text_primary),
                )
            }
            if (showDot) {
                Box(
                    modifier = Modifier
                        .align(Alignment.TopEnd)
                        .padding(top = 7.dp, end = 7.dp)
                        .size(9.dp)
                        .background(Color(0xFFE53935), shape = CircleShape),
                )
            }
        }
    }
}

@Preview(showBackground = true, name = "Home — notification dot")
@Composable
private fun HomeScreenPreviewWithDot() {
    EyeCareTheme {
        val dot = remember { mutableStateOf(true) }
        HomeScreen(
            userName = "Maria",
            onNotificationsClick = {},
            notificationDot = dot,
        )
    }
}

@Preview(showBackground = true, name = "Home — no dot")
@Composable
private fun HomeScreenPreviewNoDot() {
    EyeCareTheme {
        val dot = remember { mutableStateOf(false) }
        HomeScreen(
            userName = "Alex",
            onNotificationsClick = {},
            notificationDot = dot,
        )
    }
}
