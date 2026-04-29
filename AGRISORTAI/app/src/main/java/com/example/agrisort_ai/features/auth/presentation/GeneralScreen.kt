package com.example.agrisort_ai.features.auth.presentation

import android.widget.Toast
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountCircle
import androidx.compose.material.icons.filled.Article
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Logout
import androidx.compose.material.icons.filled.QrCode2
import androidx.compose.material.icons.filled.Route
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.agrisort_ai.R
import com.example.agrisort_ai.features.dashboard.domain.model.AppRoleDestination
import com.example.agrisort_ai.ui.components.AppCard

private data class GeneralMenuItem(
    val title: String,
    val subtitle: String,
    val icon: ImageVector,
    val onClick: () -> Unit
)

private fun adminAudienceTitle(
    title: String,
    roleDestination: AppRoleDestination,
    audience: String?
): String {
    return if (roleDestination == AppRoleDestination.ADMIN && !audience.isNullOrBlank()) {
        "$title ($audience)"
    } else {
        title
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun GeneralScreen(
    roleDestination: AppRoleDestination,
    onNavigateProfile: () -> Unit,
    onNavigateBlog: () -> Unit,
    onNavigateHistory: () -> Unit,
    onNavigateAdminUsers: () -> Unit,
    onNavigateAdminTrace: () -> Unit,
    onNavigateAdminControl: () -> Unit,
    onNavigatePartnerQr: () -> Unit,
    onLogout: () -> Unit,
    viewModel: AuthViewModel,
    settingsViewModel: GeneralSettingsViewModel = hiltViewModel()
) {
    val currentUser by viewModel.currentUser.collectAsState()
    val settingsState by settingsViewModel.uiState.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(Unit) {
        if (currentUser == null) {
            viewModel.getProfile()
        }
    }

    val profileTitle = stringResource(R.string.general_profile_title)
    val profileSubtitle = stringResource(R.string.general_profile_subtitle)
    val historyTitle = stringResource(R.string.general_history_title)
    val historySubtitle = stringResource(R.string.general_history_subtitle)
    val blogTitle = stringResource(R.string.general_blog_title)
    val blogSubtitle = stringResource(R.string.general_blog_subtitle)
    val adminUsersTitle = stringResource(R.string.general_admin_users_title)
    val adminUsersSubtitle = stringResource(R.string.general_admin_users_subtitle)
    val adminTraceTitle = stringResource(R.string.general_admin_trace_title)
    val adminTraceSubtitle = stringResource(R.string.general_admin_trace_subtitle)
    val adminControlTitle = stringResource(R.string.general_admin_control_title)
    val adminControlSubtitle = stringResource(R.string.general_admin_control_subtitle)
    val partnerQrTitle = stringResource(R.string.general_partner_qr_title)
    val partnerQrSubtitle = stringResource(R.string.general_partner_qr_subtitle)

    val menuItems = buildList {
        add(
            GeneralMenuItem(
                title = adminAudienceTitle(profileTitle, roleDestination, "cho chính admin"),
                subtitle = profileSubtitle,
                icon = Icons.Default.AccountCircle,
                onClick = onNavigateProfile
            )
        )
        add(
            GeneralMenuItem(
                title = adminAudienceTitle(blogTitle, roleDestination, "cho website công khai"),
                subtitle = blogSubtitle,
                icon = Icons.Default.Article,
                onClick = onNavigateBlog
            )
        )
        add(
            GeneralMenuItem(
                title = adminAudienceTitle(historyTitle, roleDestination, "cho truy xuất QR"),
                subtitle = historySubtitle,
                icon = Icons.Default.History,
                onClick = onNavigateHistory
            )
        )
        when (roleDestination) {
            AppRoleDestination.ADMIN -> {
                add(
                    GeneralMenuItem(
                        title = adminAudienceTitle(adminUsersTitle, roleDestination, "cho mọi role"),
                        subtitle = adminUsersSubtitle,
                        icon = Icons.Default.Groups,
                        onClick = onNavigateAdminUsers
                    )
                )
                add(
                    GeneralMenuItem(
                        title = adminAudienceTitle(adminTraceTitle, roleDestination, "cho vận chuyển, kho, người bán"),
                        subtitle = adminTraceSubtitle,
                        icon = Icons.Default.Route,
                        onClick = onNavigateAdminTrace
                    )
                )
                add(
                    GeneralMenuItem(
                        title = adminAudienceTitle(adminControlTitle, roleDestination, "cho website và hệ thống"),
                        subtitle = adminControlSubtitle,
                        icon = Icons.Default.Article,
                        onClick = onNavigateAdminControl
                    )
                )
            }

            AppRoleDestination.PARTNER -> {
                add(
                    GeneralMenuItem(
                        title = partnerQrTitle,
                        subtitle = partnerQrSubtitle,
                        icon = Icons.Default.QrCode2,
                        onClick = onNavigatePartnerQr
                    )
                )
            }
 
            AppRoleDestination.SUPPLY,
            AppRoleDestination.BUYER,
            AppRoleDestination.UNKNOWN -> Unit
        }
    }       

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(stringResource(R.string.general_title)) }
            )
        }
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            item {
                AppCard(modifier = Modifier.fillMaxWidth()) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        Text(
                            text = currentUser?.fullName ?: stringResource(R.string.general_account_fallback),
                            style = MaterialTheme.typography.titleLarge
                        )
                        Text(
                            text = currentUser?.email ?: "",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                        Text(
                            text = stringResource(
                                R.string.general_role_value,
                                currentUser?.roleLabel?.ifBlank { currentUser?.role }
                                    ?: stringResource(R.string.general_default_role)
                            ),
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }

            item {
                Text(
                    text = stringResource(R.string.general_section_menu),
                    style = MaterialTheme.typography.titleMedium
                )
            }

            item {
                AppCard(modifier = Modifier.fillMaxWidth()) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        horizontalArrangement = Arrangement.spacedBy(12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                text = stringResource(R.string.general_deduplicate_title),
                                style = MaterialTheme.typography.titleSmall
                            )
                            Text(
                                text = stringResource(R.string.general_deduplicate_subtitle),
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                        Switch(
                            checked = settingsState.deduplicateScanHistory,
                            onCheckedChange = { enabled ->
                                settingsViewModel.setDeduplicateScanHistory(enabled)
                                Toast.makeText(
                                    context,
                                    if (enabled) {
                                        context.getString(R.string.general_deduplicate_on_toast)
                                    } else {
                                        context.getString(R.string.general_deduplicate_off_toast)
                                    },
                                    Toast.LENGTH_SHORT
                                ).show()
                            }
                        )
                    }
                }
            }

            menuItems.forEach { menuItem ->
                item {
                    GeneralMenuCard(item = menuItem)
                }
            }

            item {
                AppCard(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable(onClick = onLogout)
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        horizontalArrangement = Arrangement.spacedBy(12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            imageVector = Icons.Default.Logout,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.error
                        )
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                text = stringResource(R.string.general_logout_title),
                                style = MaterialTheme.typography.titleSmall,
                                color = MaterialTheme.colorScheme.error
                            )
                            Text(
                                text = stringResource(R.string.general_logout_subtitle),
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                        Icon(
                            imageVector = Icons.Default.ChevronRight,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.error
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun GeneralMenuCard(item: GeneralMenuItem) {
    AppCard(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = item.onClick)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                imageVector = item.icon,
                contentDescription = null,
                tint = MaterialTheme.colorScheme.primary
            )
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = item.title,
                    style = MaterialTheme.typography.titleSmall
                )
                Text(
                    text = item.subtitle,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            Icon(
                imageVector = Icons.Default.ChevronRight,
                contentDescription = null,
                tint = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}
