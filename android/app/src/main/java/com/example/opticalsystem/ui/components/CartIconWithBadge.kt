package com.example.opticalsystem.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.PlatformTextStyle
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.opticalsystem.R

/**
 * Cart icon with a centered count badge (avoids font padding clipping in small circles).
 */
@Composable
fun CartIconWithBadge(
    cartCount: Int,
    onClick: () -> Unit,
    iconTint: Color,
    contentDescription: String,
    modifier: Modifier = Modifier,
) {
    // Slightly larger than IconButton so the count badge stays inside bounds (not clipped).
    Box(modifier = modifier.size(44.dp), contentAlignment = Alignment.Center) {
        IconButton(
            onClick = onClick,
            modifier = Modifier.size(40.dp),
        ) {
            Icon(
                painter = painterResource(R.drawable.ic_cart_24),
                contentDescription = contentDescription,
                tint = iconTint,
            )
        }
        if (cartCount > 0) {
            CartCountBadge(
                count = cartCount,
                modifier = Modifier
                    .align(Alignment.TopEnd)
                    .padding(top = 2.dp, end = 2.dp),
            )
        }
    }
}

@Composable
fun CartCountBadge(
    count: Int,
    modifier: Modifier = Modifier,
) {
    val label = if (count > 99) "99+" else count.toString()
    val fontSize = when {
        label.length > 2 -> 8.sp
        count >= 10 -> 9.sp
        else -> 10.sp
    }
    Box(
        modifier = modifier
            .defaultMinSize(minWidth = 18.dp, minHeight = 18.dp)
            .clip(CircleShape)
            .background(colorResource(R.color.nav_badge_background)),
        contentAlignment = Alignment.Center,
    ) {
        Text(
            text = label,
            color = Color.White,
            fontSize = fontSize,
            fontWeight = FontWeight.Bold,
            textAlign = TextAlign.Center,
            maxLines = 1,
            style = TextStyle(
                lineHeight = fontSize,
                platformStyle = PlatformTextStyle(includeFontPadding = false),
            ),
        )
    }
}
