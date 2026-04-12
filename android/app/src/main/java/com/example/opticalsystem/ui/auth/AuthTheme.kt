package com.example.opticalsystem.ui.auth

import android.widget.ImageView
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.colorResource
import androidx.compose.ui.viewinterop.AndroidView
import com.bumptech.glide.Glide
import com.bumptech.glide.load.engine.DiskCacheStrategy
import com.example.opticalsystem.R

@Composable
fun AuthRemoteLogo(logoUrl: String, modifier: Modifier = Modifier) {
    AndroidView(
        factory = { ctx ->
            ImageView(ctx).apply {
                scaleType = ImageView.ScaleType.FIT_CENTER
                contentDescription = ctx.getString(R.string.app_name)
            }
        },
        modifier = modifier,
        update = { imageView ->
            Glide.with(imageView)
                .load(logoUrl)
                .placeholder(R.drawable.login_logo)
                .error(R.drawable.login_logo)
                .diskCacheStrategy(DiskCacheStrategy.AUTOMATIC)
                .into(imageView)
        },
    )
}

@Composable
internal fun authFieldColors() = OutlinedTextFieldDefaults.colors(
    focusedBorderColor = colorResource(R.color.login_outline),
    unfocusedBorderColor = colorResource(R.color.login_outline),
    focusedLabelColor = colorResource(R.color.login_hint),
    unfocusedLabelColor = colorResource(R.color.login_hint),
    cursorColor = colorResource(R.color.login_primary),
)

