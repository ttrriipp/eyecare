package com.example.opticalsystem.ui.products

import android.content.Context
import android.net.Uri
import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.bumptech.glide.signature.ObjectKey
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.ProductImage
import com.example.opticalsystem.databinding.ItemProductImageBinding

class ProductImageAdapter(
    private val images: List<ProductImage>,
) : RecyclerView.Adapter<ProductImageAdapter.ImageViewHolder>() {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ImageViewHolder {
        val binding = ItemProductImageBinding.inflate(
            LayoutInflater.from(parent.context), parent, false,
        )
        return ImageViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ImageViewHolder, position: Int) {
        holder.bind(images[position])
    }

    override fun getItemCount(): Int = images.size

    class ImageViewHolder(
        private val binding: ItemProductImageBinding,
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(image: ProductImage) {
            val imageUrl = buildImageUrl(binding.root.context, image.imageUrl)
            if (imageUrl == null) {
                binding.ivProductImage.setImageResource(R.drawable.bg_product_placeholder)
                return
            }
            val imageCacheKey = "${image.id}_${image.createdAt}_${image.sortOrder}_${image.imageUrl}"
            Glide.with(binding.ivProductImage.context)
                .load(imageUrl)
                .signature(ObjectKey(imageCacheKey))
                .placeholder(R.drawable.bg_product_placeholder)
                .error(R.drawable.bg_product_placeholder)
                .centerCrop()
                .into(binding.ivProductImage)
        }

        private fun buildImageUrl(context: Context, url: String?): String? {
            val trimmed = url?.trim().orEmpty()
            if (trimmed.isBlank()) return null
            val backendRootUrl = context.getString(R.string.backend_root_url).trimEnd('/')

            // If backend returns a fully-qualified URL but the hostname isn't reachable from the device
            // (common with staging domains like `eyecare.test`), rewrite it to use backendRootUrl.
            if (trimmed.startsWith("http://") || trimmed.startsWith("https://")) {
                return try {
                    val uri = Uri.parse(trimmed)
                    val host = uri.host.orEmpty()
                    val backendHost = Uri.parse(backendRootUrl).host.orEmpty()

                    val shouldRewrite = host.equals("eyecare.test", ignoreCase = true) || (backendHost.isNotBlank() && !host.equals(backendHost, ignoreCase = true))
                    if (!shouldRewrite) return trimmed

                    val rebuilt = backendRootUrl + (uri.encodedPath ?: "")
                    val query = uri.encodedQuery
                    if (!query.isNullOrBlank()) "$rebuilt?$query" else rebuilt
                } catch (_: Exception) {
                    trimmed
                }
            }

            val relative = trimmed.trimStart('/')
            return "$backendRootUrl/$relative"
        }
    }
}
