package com.example.opticalsystem.util

import com.example.opticalsystem.R

object RegistrationPhoneValidator {

    /**
     * @return string resource id for an error message, or null if the value is valid.
     * Empty / whitespace is valid (phone is optional on the API).
     */
    fun validationMessageRes(phone: String): Int? {
        val t = phone.trim()
        if (t.isEmpty()) return null
        if (t.length > 20) return R.string.phone_error_too_long
        val hasInvalidChar = t.any { ch ->
            !ch.isDigit() && ch !in " +()-."
        }
        if (hasInvalidChar) return R.string.phone_error_invalid_chars
        val digitCount = t.count { it.isDigit() }
        if (digitCount < 10) return R.string.phone_error_too_short
        if (digitCount > 15) return R.string.phone_error_too_long
        return null
    }
}
