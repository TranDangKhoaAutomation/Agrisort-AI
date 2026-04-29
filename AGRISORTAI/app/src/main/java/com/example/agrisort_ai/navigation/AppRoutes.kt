package com.example.agrisort_ai.navigation

import com.example.agrisort_ai.features.dashboard.domain.model.AppRoleDestination

object AppRoutes {
    const val SESSION_GATE = "session_gate"
    const val LOGIN = "login"
    const val REGISTER = "register"
    const val FORGOT_PASSWORD = "forgot_password"
    const val RESET_PASSWORD = "reset_password"
    const val INACTIVE = "inactive"
    const val HOME = "home"
    const val GENERAL = "general"
    const val PROFILE = "profile"
    const val BLOG = "blog"
    const val BLOG_DETAIL = "blog_detail"
    const val BLOG_DETAIL_PATTERN = "blog_detail/{slug}"

    const val ADMIN_DASHBOARD = "admin_dashboard"
    const val ADMIN_USERS = "admin_users"
    const val ADMIN_TRACE = "admin_trace"
    const val ADMIN_CONTROL = "admin_control"

    const val PARTNER_DASHBOARD = "partner_dashboard"
    const val PARTNER_QR = "partner_qr"

    const val SUPPLY_DASHBOARD = "supply_dashboard"

    const val TRACE_SCAN = "trace_scan"
    const val TRACE_SCAN_QUICK = "trace_scan_quick"
    const val TRACE_HISTORY = "trace_history"
    const val TRACE_RESULT = "trace_result"
    const val TRACE_RESULT_PATTERN = "trace_result/{token}"
}

fun roleDashboardRoute(roleDestination: AppRoleDestination): String {
    return when (roleDestination) {
        AppRoleDestination.ADMIN -> AppRoutes.ADMIN_DASHBOARD
        AppRoleDestination.PARTNER -> AppRoutes.PARTNER_DASHBOARD
        AppRoleDestination.SUPPLY -> AppRoutes.SUPPLY_DASHBOARD
        AppRoleDestination.BUYER -> AppRoutes.TRACE_SCAN
        AppRoleDestination.UNKNOWN -> AppRoutes.TRACE_SCAN
    }
}
