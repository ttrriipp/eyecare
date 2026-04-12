package com.example.opticalsystem.util

import android.util.Patterns
import com.example.opticalsystem.R

object RegistrationEmailValidator {

    private const val MaxEmailLength = 254

    /**
     * @return string resource id for an error message, or null if the value is valid.
     * Empty / whitespace is treated as invalid for registration (required field).
     */
    fun validationMessageRes(email: String): Int? {
        val t = email.trim()
        if (t.isEmpty()) return R.string.email_error_required
        if (t.length > MaxEmailLength) return R.string.email_error_too_long
        if (!Patterns.EMAIL_ADDRESS.matcher(t).matches()) return R.string.email_error_invalid
        return null
    }
}
