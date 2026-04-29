package com.example.agrisort_ai

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CameraAlt
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.QrCode2
import androidx.compose.material.icons.filled.Route
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import androidx.navigation.navDeepLink
import com.example.agrisort_ai.features.auth.presentation.AuthViewModel
import com.example.agrisort_ai.features.auth.presentation.ForgotPasswordScreen
import com.example.agrisort_ai.features.auth.presentation.GeneralScreen
import com.example.agrisort_ai.features.auth.presentation.InactiveAccountScreen
import com.example.agrisort_ai.features.auth.presentation.LoginScreen
import com.example.agrisort_ai.features.auth.presentation.ProfileScreen
import com.example.agrisort_ai.features.auth.presentation.RegisterScreen
import com.example.agrisort_ai.features.auth.presentation.ResetPasswordScreen
import com.example.agrisort_ai.features.auth.presentation.SessionState
import com.example.agrisort_ai.features.dashboard.domain.model.AppRoleDestination
import com.example.agrisort_ai.features.dashboard.presentation.AdminDashboardScreen
import com.example.agrisort_ai.features.dashboard.presentation.AdminControlScreen
import com.example.agrisort_ai.features.dashboard.presentation.AdminTraceScreen
import com.example.agrisort_ai.features.dashboard.presentation.AdminUsersScreen
import com.example.agrisort_ai.features.dashboard.presentation.BlogDetailScreen
import com.example.agrisort_ai.features.dashboard.presentation.BlogScreen
import com.example.agrisort_ai.features.dashboard.presentation.PartnerDashboardScreen
import com.example.agrisort_ai.features.dashboard.presentation.PartnerQrScreen
import com.example.agrisort_ai.features.dashboard.presentation.SupplyDashboardScreen
import com.example.agrisort_ai.features.dashboard.presentation.TraceHistoryScreen
import com.example.agrisort_ai.features.dashboard.presentation.TraceQuickScanScreen
import com.example.agrisort_ai.features.dashboard.presentation.TraceResultScreen
import com.example.agrisort_ai.features.dashboard.presentation.TraceScanScreen
import com.example.agrisort_ai.navigation.AppRoutes
import com.example.agrisort_ai.navigation.roleDashboardRoute
import com.example.agrisort_ai.ui.components.AgriSortBrandMark
import com.example.agrisort_ai.ui.components.AuthGradientBackground
import com.example.agrisort_ai.ui.theme.AgriSortAITheme
import com.example.agrisort_ai.R
import dagger.hilt.android.AndroidEntryPoint

private data class BottomNavItem(
    val route: String,
    val label: String,
    val icon: ImageVector
)

@AndroidEntryPoint
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        setTheme(R.style.Theme_AgriSortAI)
        super.onCreate(savedInstanceState)
        setContent {
            AgriSortAITheme {
                Surface(modifier = Modifier.fillMaxSize()) {
                    val navController = rememberNavController()
                    val authViewModel: AuthViewModel = hiltViewModel()
                    val sessionState by authViewModel.sessionState.collectAsState()
                    val roleDestination by authViewModel.roleDestination.collectAsState()
                    val navBackStackEntry by navController.currentBackStackEntryAsState()
                    val currentRoute = navBackStackEntry?.destination?.route

                    SessionNavigator(
                        navController = navController,
                        sessionState = sessionState,
                        roleDestination = roleDestination,
                        currentRoute = currentRoute
                    )

                    val showBottomNav = remember(sessionState, currentRoute) {
                        sessionState is SessionState.Authenticated && currentRoute in appNavRoutes
                    }

                    Scaffold(
                        bottomBar = {
                            if (showBottomNav) {
                                RoleBottomNavigation(
                                    navController = navController,
                                    roleDestination = roleDestination,
                                    currentRoute = currentRoute
                                )
                            }
                        }
                    ) { innerPadding ->
                        NavHost(
                            navController = navController,
                            startDestination = AppRoutes.SESSION_GATE,
                            modifier = Modifier.padding(innerPadding)
                        ) {
                            composable(AppRoutes.SESSION_GATE) {
                                SessionGateScreen()
                            }

                            composable(AppRoutes.LOGIN) {
                                LoginScreen(
                                    onNavigateToRegister = { navController.navigate(AppRoutes.REGISTER) },
                                    onNavigateToForgotPassword = { navController.navigate(AppRoutes.FORGOT_PASSWORD) },
                                    onNavigateToBlog = { navController.navigate(AppRoutes.BLOG) },
                                    viewModel = authViewModel
                                )
                            }

                            composable(AppRoutes.REGISTER) {
                                RegisterScreen(
                                    onRegisterSuccess = {
                                        navController.navigate(AppRoutes.LOGIN) {
                                            popUpTo(AppRoutes.REGISTER) { inclusive = true }
                                        }
                                    },
                                    onNavigateToLogin = { navController.popBackStack() },
                                    viewModel = authViewModel
                                )
                            }

                            composable(AppRoutes.FORGOT_PASSWORD) {
                                ForgotPasswordScreen(
                                    onNavigateBack = { navController.popBackStack() },
                                    onNavigateToResetPassword = { navController.navigate(AppRoutes.RESET_PASSWORD) },
                                    viewModel = authViewModel
                                )
                            }

                            composable(AppRoutes.RESET_PASSWORD) {
                                ResetPasswordScreen(
                                    onNavigateBack = { navController.popBackStack() },
                                    onResetPasswordSuccess = {
                                        navController.navigate(AppRoutes.LOGIN) {
                                            popUpTo(AppRoutes.RESET_PASSWORD) { inclusive = true }
                                            launchSingleTop = true
                                        }
                                    },
                                    viewModel = authViewModel
                                )
                            }

                            composable(AppRoutes.INACTIVE) {
                                val inactiveMessage = (sessionState as? SessionState.Inactive)?.message
                                    ?: stringResource(R.string.inactive_account_default)
                                InactiveAccountScreen(
                                    message = inactiveMessage,
                                    onLogout = { authViewModel.logout() }
                                )
                            }

                            composable(AppRoutes.GENERAL) {
                                GeneralScreen(
                                    roleDestination = roleDestination,
                                    onNavigateProfile = { navController.navigate(AppRoutes.PROFILE) },
                                    onNavigateBlog = { navController.navigate(AppRoutes.BLOG) },
                                    onNavigateHistory = { navController.navigate(AppRoutes.TRACE_HISTORY) },
                                    onNavigateAdminUsers = { navController.navigate(AppRoutes.ADMIN_USERS) },
                                    onNavigateAdminTrace = { navController.navigate(AppRoutes.ADMIN_TRACE) },
                                    onNavigateAdminControl = { navController.navigate(AppRoutes.ADMIN_CONTROL) },
                                    onNavigatePartnerQr = { navController.navigate(AppRoutes.PARTNER_QR) },
                                    onLogout = { authViewModel.logout() },
                                    viewModel = authViewModel
                                )
                            }

                            composable(AppRoutes.PROFILE) {
                                ProfileScreen(
                                    onNavigateBack = { navController.popBackStack() },
                                    viewModel = authViewModel
                                )
                            }

                            composable(AppRoutes.ADMIN_DASHBOARD) {
                                AdminDashboardScreen()
                            }

                            composable(AppRoutes.ADMIN_USERS) {
                                AdminUsersScreen(onNavigateBack = { navController.popBackStack() })
                            }

                            composable(AppRoutes.ADMIN_TRACE) {
                                AdminTraceScreen(onNavigateBack = { navController.popBackStack() })
                            }

                            composable(AppRoutes.ADMIN_CONTROL) {
                                AdminControlScreen(onNavigateBack = { navController.popBackStack() })
                            }

                            composable(AppRoutes.PARTNER_DASHBOARD) {
                                PartnerDashboardScreen()
                            }

                            composable(AppRoutes.PARTNER_QR) {
                                PartnerQrScreen(
                                    onNavigateBack = { navController.popBackStack() },
                                    onNavigateScan = { navController.navigate(AppRoutes.TRACE_SCAN) }
                                )
                            }

                            composable(AppRoutes.SUPPLY_DASHBOARD) {
                                SupplyDashboardScreen()
                            }

                            composable(AppRoutes.TRACE_SCAN) {
                                TraceScanScreen(
                                    onNavigateBack = { navController.popBackStack() },
                                    onNavigateQuickScan = { navController.navigate(AppRoutes.TRACE_SCAN_QUICK) },
                                    onNavigateHistory = { navController.navigate(AppRoutes.TRACE_HISTORY) },
                                    onNavigateResult = { token ->
                                        navController.navigate("${AppRoutes.TRACE_RESULT}/$token")
                                    }
                                )
                            }

                            composable(AppRoutes.TRACE_SCAN_QUICK) {
                                TraceQuickScanScreen(
                                    onNavigateBack = { navController.popBackStack() },
                                    onNavigateFullScan = { navController.navigate(AppRoutes.TRACE_SCAN) },
                                    onNavigateHistory = { navController.navigate(AppRoutes.TRACE_HISTORY) },
                                    onNavigateResult = { token ->
                                        navController.navigate("${AppRoutes.TRACE_RESULT}/$token")
                                    }
                                )
                            }

                            composable(AppRoutes.TRACE_HISTORY) {
                                TraceHistoryScreen(
                                    onNavigateBack = { navController.popBackStack() },
                                    onSelectToken = { token ->
                                        navController.navigate("${AppRoutes.TRACE_RESULT}/$token")
                                    }
                                )
                            }

                            composable(AppRoutes.BLOG) {
                                BlogScreen(
                                    onNavigateBack = { navController.popBackStack() },
                                    onOpenArticle = { slug ->
                                        navController.navigate("${AppRoutes.BLOG_DETAIL}/$slug")
                                    }
                                )
                            }

                            composable(
                                route = AppRoutes.BLOG_DETAIL_PATTERN,
                                arguments = listOf(
                                    navArgument("slug") {
                                        type = NavType.StringType
                                    }
                                )
                            ) { backStackEntry ->
                                val slug = backStackEntry.arguments?.getString("slug").orEmpty()
                                BlogDetailScreen(
                                    slug = slug,
                                    onNavigateBack = { navController.popBackStack() }
                                )
                            }

                            composable(
                                route = AppRoutes.TRACE_RESULT_PATTERN,
                                arguments = listOf(
                                    navArgument("token") {
                                        type = NavType.StringType
                                    }
                                ),
                                deepLinks = listOf(
                                    navDeepLink {
                                        uriPattern = "agrisort://trace/{token}"
                                    },
                                    navDeepLink {
                                        uriPattern = "${BuildConfig.PUBLIC_BASE_URL.trimEnd('/')}/trace/{token}"
                                    }
                                )
                            ) { backStackEntry ->
                                val token = backStackEntry.arguments?.getString("token").orEmpty()
                                TraceResultScreen(
                                    token = token,
                                    onNavigateBack = { navController.popBackStack() },
                                    onNavigateHistory = { navController.navigate(AppRoutes.TRACE_HISTORY) }
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun RoleBottomNavigation(
    navController: NavHostController,
    roleDestination: AppRoleDestination,
    currentRoute: String?
) {
    RoleBottomNavigationContent(
        roleDestination = roleDestination,
        currentRoute = currentRoute,
        onNavigate = { route ->
            navController.navigate(route) {
                launchSingleTop = true
                restoreState = true
                popUpTo(navController.graph.startDestinationId) {
                    saveState = true
                }
            }
        }
    )
}

@Composable
fun RoleBottomNavigationContent(
    roleDestination: AppRoleDestination,
    currentRoute: String?,
    onNavigate: (String) -> Unit
) {
    val items = bottomItemsForRole(roleDestination)
    val normalizedRoute = when (currentRoute) {
        AppRoutes.PROFILE,
        AppRoutes.GENERAL -> AppRoutes.GENERAL
        AppRoutes.TRACE_RESULT_PATTERN,
        AppRoutes.TRACE_SCAN_QUICK -> AppRoutes.TRACE_SCAN
        else -> currentRoute
    }

    NavigationBar {
        items.forEach { item ->
            val selected = normalizedRoute == item.route
            NavigationBarItem(
                selected = selected,
                onClick = {
                    if (!selected) {
                        onNavigate(item.route)
                    }
                },
                icon = {
                    Icon(
                        imageVector = item.icon,
                        contentDescription = item.label
                    )
                },
                label = {
                    Text(
                        text = item.label,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }
            )
        }
    }
}

@Composable
private fun bottomItemsForRole(roleDestination: AppRoleDestination): List<BottomNavItem> {
    return when (roleDestination) {
        AppRoleDestination.ADMIN -> listOf(
            BottomNavItem(AppRoutes.ADMIN_DASHBOARD, stringResource(R.string.nav_dashboard), Icons.Default.Home),
            BottomNavItem(AppRoutes.ADMIN_USERS, stringResource(R.string.nav_users), Icons.Default.Groups),
            BottomNavItem(AppRoutes.TRACE_SCAN, stringResource(R.string.nav_scan), Icons.Default.CameraAlt),
            BottomNavItem(AppRoutes.ADMIN_TRACE, stringResource(R.string.nav_trace), Icons.Default.Route),
            BottomNavItem(AppRoutes.GENERAL, stringResource(R.string.nav_account), Icons.Default.Settings)
        )

        AppRoleDestination.PARTNER -> listOf(
            BottomNavItem(AppRoutes.PARTNER_DASHBOARD, stringResource(R.string.nav_lots), Icons.Default.Home),
            BottomNavItem(AppRoutes.PARTNER_QR, stringResource(R.string.nav_qr), Icons.Default.QrCode2),
            BottomNavItem(AppRoutes.TRACE_SCAN, stringResource(R.string.nav_scan), Icons.Default.CameraAlt),
            BottomNavItem(AppRoutes.GENERAL, stringResource(R.string.nav_account), Icons.Default.Settings)
        )

        AppRoleDestination.SUPPLY -> listOf(
            BottomNavItem(AppRoutes.SUPPLY_DASHBOARD, stringResource(R.string.nav_handover), Icons.Default.Home),
            BottomNavItem(AppRoutes.TRACE_SCAN, stringResource(R.string.nav_scan), Icons.Default.CameraAlt),
            BottomNavItem(AppRoutes.GENERAL, stringResource(R.string.nav_account), Icons.Default.Settings)
        )

        AppRoleDestination.BUYER -> listOf(
            BottomNavItem(AppRoutes.TRACE_HISTORY, stringResource(R.string.nav_history), Icons.Default.History),
            BottomNavItem(AppRoutes.TRACE_SCAN, stringResource(R.string.nav_scan), Icons.Default.CameraAlt),
            BottomNavItem(AppRoutes.GENERAL, stringResource(R.string.nav_account), Icons.Default.Settings)
        )

        AppRoleDestination.UNKNOWN -> listOf(
            BottomNavItem(AppRoutes.TRACE_HISTORY, stringResource(R.string.nav_history), Icons.Default.History),
            BottomNavItem(AppRoutes.TRACE_SCAN, stringResource(R.string.nav_scan), Icons.Default.CameraAlt),
            BottomNavItem(AppRoutes.GENERAL, stringResource(R.string.nav_account), Icons.Default.Settings)
        )
    }
}

private val appNavRoutes = setOf(
    AppRoutes.GENERAL,
    AppRoutes.PROFILE,
    AppRoutes.BLOG,
    AppRoutes.BLOG_DETAIL_PATTERN,
    AppRoutes.ADMIN_DASHBOARD,
    AppRoutes.ADMIN_USERS,
    AppRoutes.ADMIN_TRACE,
    AppRoutes.ADMIN_CONTROL,
    AppRoutes.PARTNER_DASHBOARD,
    AppRoutes.PARTNER_QR,
    AppRoutes.SUPPLY_DASHBOARD,
    AppRoutes.TRACE_SCAN,
    AppRoutes.TRACE_SCAN_QUICK,
    AppRoutes.TRACE_HISTORY,
    AppRoutes.TRACE_RESULT_PATTERN
)

@Composable
private fun SessionNavigator(
    navController: NavHostController,
    sessionState: SessionState,
    roleDestination: AppRoleDestination,
    currentRoute: String?
) {
    LaunchedEffect(sessionState, currentRoute, roleDestination) {
        if (currentRoute == null) return@LaunchedEffect

        val authRoutes = setOf(
            AppRoutes.LOGIN,
            AppRoutes.REGISTER,
            AppRoutes.FORGOT_PASSWORD,
            AppRoutes.RESET_PASSWORD
        )

        val protectedRoutes = setOf(
            AppRoutes.GENERAL,
            AppRoutes.PROFILE,
            AppRoutes.ADMIN_DASHBOARD,
            AppRoutes.ADMIN_USERS,
            AppRoutes.ADMIN_TRACE,
            AppRoutes.ADMIN_CONTROL,
            AppRoutes.PARTNER_DASHBOARD,
            AppRoutes.PARTNER_QR,
            AppRoutes.SUPPLY_DASHBOARD,
            AppRoutes.TRACE_SCAN,
            AppRoutes.TRACE_SCAN_QUICK,
            AppRoutes.TRACE_HISTORY,
            AppRoutes.TRACE_RESULT_PATTERN,
            AppRoutes.INACTIVE
        )

        when (sessionState) {
            SessionState.Authenticated -> {
                val allowedRoutes = allowedRoutesForRole(roleDestination)
                if (currentRoute in protectedRoutes && currentRoute !in allowedRoutes) {
                    navController.navigate(roleDashboardRoute(roleDestination)) {
                        launchSingleTop = true
                        popUpTo(AppRoutes.SESSION_GATE) { inclusive = true }
                    }
                    return@LaunchedEffect
                }

                if (currentRoute == AppRoutes.SESSION_GATE || currentRoute in authRoutes) {
                    navController.navigate(roleDashboardRoute(roleDestination)) {
                        launchSingleTop = true
                        popUpTo(AppRoutes.SESSION_GATE) { inclusive = true }
                    }
                }
            }

            is SessionState.Inactive -> {
                if (currentRoute != AppRoutes.INACTIVE) {
                    navController.navigate(AppRoutes.INACTIVE) {
                        launchSingleTop = true
                        popUpTo(AppRoutes.SESSION_GATE) { inclusive = true }
                    }
                }
            }

            SessionState.Unauthenticated -> {
                if (currentRoute == AppRoutes.SESSION_GATE || currentRoute in protectedRoutes) {
                    navController.navigate(AppRoutes.LOGIN) {
                        launchSingleTop = true
                        popUpTo(AppRoutes.SESSION_GATE) { inclusive = true }
                    }
                }
            }

            SessionState.Loading -> Unit
        }
    }
}

private fun allowedRoutesForRole(roleDestination: AppRoleDestination): Set<String> {
    val commonRoutes = setOf(
        AppRoutes.GENERAL,
        AppRoutes.PROFILE,
        AppRoutes.BLOG,
        AppRoutes.BLOG_DETAIL_PATTERN,
        AppRoutes.TRACE_SCAN,
        AppRoutes.TRACE_SCAN_QUICK,
        AppRoutes.TRACE_HISTORY,
        AppRoutes.TRACE_RESULT_PATTERN
    )

    return when (roleDestination) {
        AppRoleDestination.ADMIN -> commonRoutes + setOf(
            AppRoutes.ADMIN_DASHBOARD,
            AppRoutes.ADMIN_USERS,
            AppRoutes.ADMIN_TRACE,
            AppRoutes.ADMIN_CONTROL
        )

        AppRoleDestination.PARTNER -> commonRoutes + setOf(
            AppRoutes.PARTNER_DASHBOARD,
            AppRoutes.PARTNER_QR
        )

        AppRoleDestination.SUPPLY -> commonRoutes + setOf(
            AppRoutes.SUPPLY_DASHBOARD
        )

        AppRoleDestination.BUYER -> commonRoutes
        AppRoleDestination.UNKNOWN -> commonRoutes
    }
}

@Composable
private fun SessionGateScreen() {
    AuthGradientBackground(modifier = Modifier.fillMaxSize()) {
        Box(
            modifier = Modifier.fillMaxSize(),
            contentAlignment = Alignment.Center
        ) {
            AgriSortBrandMark(modifier = Modifier.fillMaxWidth(0.62f))
        }
    }
}
