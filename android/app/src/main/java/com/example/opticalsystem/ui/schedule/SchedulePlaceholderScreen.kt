package com.example.opticalsystem.ui.schedule

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.sp
import com.example.opticalsystem.R
import com.example.opticalsystem.ui.theme.EyeCareTheme

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SchedulePlaceholderScreen() {
    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = stringResource(R.string.nav_book),
                        color = colorResource(R.color.text_primary),
                    )
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = colorResource(R.color.surface),
                ),
            )
        },
    ) { padding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(colorResource(R.color.background))
                .padding(padding),
            contentAlignment = Alignment.Center,
        ) {
            Text(
                text = "Coming soon",
                color = colorResource(R.color.text_secondary),
                fontSize = 16.sp,
            )
        }
    }
}

@Preview(showBackground = true, name = "Schedule (placeholder)")
@Composable
private fun SchedulePlaceholderScreenPreview() {
    EyeCareTheme {
        SchedulePlaceholderScreen()
    }
}
