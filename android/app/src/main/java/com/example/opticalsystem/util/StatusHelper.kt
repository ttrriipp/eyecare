package com.example.opticalsystem.util

import android.content.Context
import android.graphics.drawable.GradientDrawable
import android.widget.TextView
import androidx.core.content.ContextCompat
import com.example.opticalsystem.R
import java.text.SimpleDateFormat
import java.util.Locale
import java.util.TimeZone

object StatusHelper {

    /** ARGB colors for Compose: [textColor, backgroundColor]. */
    fun orderStatusBadgeColors(context: Context, status: String): Pair<Int, Int> =
        getOrderStatusColors(context, status)

    /** ARGB colors for Compose: [textColor, backgroundColor]. */
    fun paymentStatusBadgeColors(context: Context, status: String): Pair<Int, Int> =
        getPaymentStatusColors(context, status)

    fun applyOrderStatusBadge(textView: TextView, status: String, label: String) {
        val context = textView.context
        val (textColor, bgColor) = getOrderStatusColors(context, status)
        textView.text = label
        textView.setTextColor(textColor)
        val bg = GradientDrawable().apply {
            cornerRadius = 24f
            setColor(bgColor)
        }
        textView.background = bg
    }

    fun applyPaymentStatusBadge(textView: TextView, status: String, label: String) {
        val context = textView.context
        val (textColor, bgColor) = getPaymentStatusColors(context, status)
        textView.text = label
        textView.setTextColor(textColor)
        val bg = GradientDrawable().apply {
            cornerRadius = 24f
            setColor(bgColor)
        }
        textView.background = bg
    }

    private fun getOrderStatusColors(context: Context, status: String): Pair<Int, Int> {
        return when (status) {
            "pending" -> Pair(
                ContextCompat.getColor(context, R.color.status_pending),
                ContextCompat.getColor(context, R.color.status_pending_bg),
            )
            "confirmed" -> Pair(
                ContextCompat.getColor(context, R.color.status_confirmed),
                ContextCompat.getColor(context, R.color.status_confirmed_bg),
            )
            "ready" -> Pair(
                ContextCompat.getColor(context, R.color.status_ready),
                ContextCompat.getColor(context, R.color.status_ready_bg),
            )
            "completed" -> Pair(
                ContextCompat.getColor(context, R.color.status_completed),
                ContextCompat.getColor(context, R.color.status_completed_bg),
            )
            "cancelled" -> Pair(
                ContextCompat.getColor(context, R.color.status_cancelled),
                ContextCompat.getColor(context, R.color.status_cancelled_bg),
            )
            else -> Pair(
                ContextCompat.getColor(context, R.color.text_secondary),
                ContextCompat.getColor(context, R.color.divider),
            )
        }
    }

    private fun getPaymentStatusColors(context: Context, status: String): Pair<Int, Int> {
        return when (status) {
            "unpaid" -> Pair(
                ContextCompat.getColor(context, R.color.payment_unpaid),
                ContextCompat.getColor(context, R.color.payment_unpaid_bg),
            )
            "partially_paid" -> Pair(
                ContextCompat.getColor(context, R.color.payment_partially_paid),
                ContextCompat.getColor(context, R.color.payment_partially_paid_bg),
            )
            "paid" -> Pair(
                ContextCompat.getColor(context, R.color.payment_paid),
                ContextCompat.getColor(context, R.color.payment_paid_bg),
            )
            "refunded" -> Pair(
                ContextCompat.getColor(context, R.color.payment_refunded),
                ContextCompat.getColor(context, R.color.payment_refunded_bg),
            )
            "partially_refunded" -> Pair(
                ContextCompat.getColor(context, R.color.payment_partially_paid),
                ContextCompat.getColor(context, R.color.payment_partially_paid_bg),
            )
            "voided" -> Pair(
                ContextCompat.getColor(context, R.color.payment_voided),
                ContextCompat.getColor(context, R.color.payment_voided_bg),
            )
            else -> Pair(
                ContextCompat.getColor(context, R.color.text_secondary),
                ContextCompat.getColor(context, R.color.divider),
            )
        }
    }

    fun formatPrice(price: String): String {
        return try {
            val number = price.toDouble()
            "₱${String.format("%,.0f", number)}"
        } catch (e: NumberFormatException) {
            "₱$price"
        }
    }

    private val isoParser = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US).apply {
        timeZone = TimeZone.getTimeZone("UTC")
    }

    fun formatDate(isoDate: String): String {
        return try {
            // Strip fractional seconds and timezone suffix for parsing
            val cleaned = isoDate.replace(Regex("\\.[0-9]+Z$"), "Z")
                .replace("Z", "")
                .replace(Regex("[+-]\\d{2}:\\d{2}$"), "")
            val date = isoParser.parse(cleaned) ?: return isoDate
            val displayFormat = SimpleDateFormat("MMM dd, yyyy • h:mm a", Locale.US)
            displayFormat.format(date)
        } catch (e: Exception) {
            isoDate
        }
    }

    fun formatDateShort(isoDate: String): String {
        return try {
            val cleaned = isoDate.replace(Regex("\\.[0-9]+Z$"), "Z")
                .replace("Z", "")
                .replace(Regex("[+-]\\d{2}:\\d{2}$"), "")
            val date = isoParser.parse(cleaned) ?: return isoDate
            val displayFormat = SimpleDateFormat("MMM dd, yyyy", Locale.US)
            displayFormat.format(date)
        } catch (e: Exception) {
            isoDate
        }
    }
}
