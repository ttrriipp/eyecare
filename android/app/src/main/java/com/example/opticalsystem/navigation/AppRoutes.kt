package com.example.opticalsystem.navigation

import java.net.URLDecoder
import java.net.URLEncoder
import java.nio.charset.StandardCharsets

object AppRoutes {
    const val LOGIN = "login"
    const val REGISTER = "register"
    const val MAIN_GRAPH = "main"

    const val HOME = "home"
    const val CATALOG = "catalog"
    const val SCHEDULE = "schedule"
    const val CHAT = "chat"
    const val PROFILE = "profile"
    const val EDIT_PROFILE = "edit_profile"

    const val NOTIFICATIONS = "notifications"
    const val PRODUCT = "product/{productId}"
    const val CART = "cart"
    const val CHECKOUT = "checkout"
    const val ORDER_CONFIRM = "order_confirm"
    const val ORDER_PLACED = "order_placed/{orderId}/{orderNumber}/{orderTotal}"

    const val ORDERS = "orders"
    const val ORDER = "order/{orderId}"
    const val BILLS = "bills"
    const val BILL = "bill/{billId}"
    const val CONVERSATION = "conversation/{conversationId}"

    fun product(productId: Int) = "product/$productId"
    fun orderDetail(orderId: Int) = "order/$orderId"
    fun billDetail(billId: Int) = "bill/$billId"
    fun conversation(conversationId: Int) = "conversation/$conversationId"

    fun orderPlaced(orderId: Int, orderNumber: String, orderTotal: String): String {
        val enc = { s: String -> URLEncoder.encode(s, StandardCharsets.UTF_8) }
        return "order_placed/$orderId/${enc(orderNumber)}/${enc(orderTotal)}"
    }

    fun decodeArg(raw: String): String =
        URLDecoder.decode(raw, StandardCharsets.UTF_8)
}

object FragmentNavBridge {
    const val OPEN_CONVERSATION = "open_conversation"
    const val KEY_CONVERSATION_ID = "conversationId"
}
