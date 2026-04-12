package com.example.opticalsystem.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Typography
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.sp
import androidx.core.content.ContextCompat
import com.example.opticalsystem.R

private fun color(ctx: android.content.Context, id: Int): Color =
    Color(ContextCompat.getColor(ctx, id))

private val EyeCareTypography = Typography(
    headlineLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 32.sp,
        lineHeight = 40.sp,
    ),
    headlineMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 28.sp,
        lineHeight = 36.sp,
    ),
    headlineSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 24.sp,
        lineHeight = 32.sp,
    ),
    titleLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 22.sp,
        lineHeight = 28.sp,
    ),
    titleMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.SemiBold,
        fontSize = 16.sp,
        lineHeight = 24.sp,
    ),
    titleSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Medium,
        fontSize = 14.sp,
        lineHeight = 20.sp,
    ),
    bodyLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 16.sp,
        lineHeight = 24.sp,
    ),
    bodyMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 14.sp,
        lineHeight = 20.sp,
    ),
    bodySmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 12.sp,
        lineHeight = 16.sp,
    ),
    labelLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Medium,
        fontSize = 14.sp,
        lineHeight = 20.sp,
    ),
    labelMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Medium,
        fontSize = 12.sp,
        lineHeight = 16.sp,
    ),
    labelSmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Medium,
        fontSize = 11.sp,
        lineHeight = 16.sp,
    ),
    displayLarge = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 57.sp,
        lineHeight = 64.sp,
    ),
    displayMedium = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 45.sp,
        lineHeight = 52.sp,
    ),
    displaySmall = TextStyle(
        fontFamily = FontFamily.SansSerif,
        fontWeight = FontWeight.Normal,
        fontSize = 36.sp,
        lineHeight = 44.sp,
    ),
)

@Composable
fun EyeCareTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit,
) {
    val ctx = LocalContext.current
    val lightScheme = lightColorScheme(
        primary = color(ctx, R.color.primary),
        onPrimary = color(ctx, R.color.on_primary),
        primaryContainer = color(ctx, R.color.primary_light),
        onPrimaryContainer = color(ctx, R.color.text_primary),
        secondary = color(ctx, R.color.login_primary),
        onSecondary = color(ctx, R.color.on_primary),
        secondaryContainer = color(ctx, R.color.primary_light),
        onSecondaryContainer = color(ctx, R.color.text_primary),
        tertiary = color(ctx, R.color.primary_dark),
        onTertiary = color(ctx, R.color.on_primary),
        background = color(ctx, R.color.background),
        onBackground = color(ctx, R.color.text_primary),
        surface = color(ctx, R.color.surface),
        onSurface = color(ctx, R.color.text_primary),
        surfaceVariant = color(ctx, R.color.spec_row_alt),
        onSurfaceVariant = color(ctx, R.color.text_secondary),
        outline = color(ctx, R.color.divider),
        outlineVariant = color(ctx, R.color.login_outline),
        error = Color(0xFFB3261E),
        onError = Color.White,
        errorContainer = Color(0xFFF9DEDC),
        onErrorContainer = Color(0xFF410E0B),
    )
    val darkScheme = darkColorScheme(
        primary = color(ctx, R.color.primary_light),
        onPrimary = Color(0xFF0A1E2E),
        primaryContainer = color(ctx, R.color.primary_dark),
        onPrimaryContainer = Color(0xFFD4E4F2),
        secondary = color(ctx, R.color.login_link),
        onSecondary = Color(0xFF001826),
        secondaryContainer = Color(0xFF0369A1),
        onSecondaryContainer = Color(0xFFC8E8FF),
        tertiary = color(ctx, R.color.primary),
        onTertiary = Color.White,
        background = Color(0xFF121417),
        onBackground = Color(0xFFE8EEF4),
        surface = Color(0xFF1C2128),
        onSurface = Color(0xFFE8EEF4),
        surfaceVariant = Color(0xFF2A3139),
        onSurfaceVariant = Color(0xFF9AA8B8),
        outline = Color(0xFF3D4654),
        outlineVariant = Color(0xFF2A3139),
        error = Color(0xFFF2B8B5),
        onError = Color(0xFF601410),
        errorContainer = Color(0xFF8C1D18),
        onErrorContainer = Color(0xFFF9DEDC),
    )
    val colorScheme = if (darkTheme) darkScheme else lightScheme
    MaterialTheme(
        colorScheme = colorScheme,
        typography = EyeCareTypography,
        content = content,
    )
}
