package com.example.opticalsystem.ui.components

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.wrapContentHeight
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.StarHalf
import androidx.compose.material.icons.outlined.Star
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.text.PlatformTextStyle
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.opticalsystem.R

/**
 * Read-only 5-star display for catalog and product detail (avoids clipped [RatingBar] in Compose).
 */
@Composable
fun RatingStarsRow(
    rating: Float,
    modifier: Modifier = Modifier,
    starSize: Dp = 14.dp,
    showScore: Boolean = true,
    trailingText: String? = null,
    trailingTextSize: Float = 11f,
) {
    val starTint = colorResource(R.color.star_color)
    val secondary = colorResource(R.color.text_secondary)
    Row(
        modifier = modifier.wrapContentHeight(),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(1.dp),
        ) {
            repeat(5) { index ->
                val starIndex = index + 1
                val icon = when {
                    rating >= starIndex -> Icons.Filled.Star
                    rating >= starIndex - 0.5f -> Icons.Filled.StarHalf
                    else -> Icons.Outlined.Star
                }
                Icon(
                    imageVector = icon,
                    contentDescription = null,
                    modifier = Modifier.size(starSize),
                    tint = starTint,
                )
            }
        }
        if (showScore) {
            Text(
                text = String.format("%.1f", rating),
                modifier = Modifier.padding(start = 5.dp),
                color = secondary,
                fontSize = trailingTextSize.sp,
                lineHeight = (trailingTextSize + 3).sp,
                maxLines = 1,
                style = TextStyle(platformStyle = PlatformTextStyle(includeFontPadding = false)),
            )
        }
        if (trailingText != null) {
            Text(
                text = trailingText,
                modifier = Modifier.padding(start = if (showScore) 3.dp else 5.dp),
                color = secondary,
                fontSize = trailingTextSize.sp,
                lineHeight = (trailingTextSize + 3).sp,
                maxLines = 1,
                style = TextStyle(platformStyle = PlatformTextStyle(includeFontPadding = false)),
            )
        }
    }
}
