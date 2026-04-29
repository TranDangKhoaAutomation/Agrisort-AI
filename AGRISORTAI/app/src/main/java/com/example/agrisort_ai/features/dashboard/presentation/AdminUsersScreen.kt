package com.example.agrisort_ai.features.dashboard.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.ExperimentalMaterialApi
import androidx.compose.material.pullrefresh.PullRefreshIndicator
import androidx.compose.material.pullrefresh.pullRefresh
import androidx.compose.material.pullrefresh.rememberPullRefreshState
import androidx.compose.material3.Button
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.agrisort_ai.features.dashboard.domain.model.AdminUser
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.AppTextField
import com.example.agrisort_ai.ui.components.LoadingState
import com.example.agrisort_ai.ui.components.MessageType

@OptIn(ExperimentalMaterial3Api::class, ExperimentalMaterialApi::class)
@Composable
fun AdminUsersScreen(
    onNavigateBack: () -> Unit,
    viewModel: AdminDashboardViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val showFullScreenLoading = uiState.isLoading && uiState.users.isEmpty()
    val pullRefreshState = rememberPullRefreshState(
        refreshing = uiState.isLoading,
        onRefresh = viewModel::loadUsers
    )

    Scaffold(topBar = { TopAppBar(title = { Text("Quản lý người dùng (cho mọi role)") }) }) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .pullRefresh(pullRefreshState)
        ) {
            LazyColumn(
                modifier = Modifier.fillMaxSize(),
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                item {
                    DataSourceBanner(
                        state = uiState.dataSourceState,
                        errorMessage = uiState.error
                    )
                }

                if (!uiState.message.isNullOrBlank()) {
                    item {
                        AppMessage(
                            message = uiState.message.orEmpty(),
                            type = MessageType.SUCCESS
                        )
                    }
                }

                item {
                    DashboardSectionCard(
                        title = "Bộ lọc người dùng",
                        subtitle = "Lọc theo từ khóa, vai trò và trạng thái để rà soát đúng nhóm tài khoản."
                    ) {
                        AppTextField(
                            value = uiState.query,
                            onValueChange = viewModel::onQueryChange,
                            label = "Từ khóa"
                        )
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            AppTextField(
                                value = uiState.roleFilter,
                                onValueChange = viewModel::onRoleFilterChange,
                                label = "Vai trò",
                                modifier = Modifier.weight(1f)
                            )
                            AppTextField(
                                value = uiState.statusFilter,
                                onValueChange = viewModel::onStatusFilterChange,
                                label = "Trạng thái",
                                modifier = Modifier.weight(1f)
                            )
                        }
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            OutlinedButton(
                                onClick = onNavigateBack,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text("Quay lại")
                            }
                            Button(
                                onClick = viewModel::loadUsers,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text("Áp dụng bộ lọc")
                            }
                        }
                    }
                }

                if (uiState.users.isEmpty() && !uiState.isLoading) {
                    item {
                        DashboardSectionCard(
                            title = "Danh sách người dùng",
                            subtitle = "Chưa có dữ liệu phù hợp với bộ lọc hiện tại."
                        ) {
                            Text(
                                text = "Hãy thử đổi từ khóa hoặc bỏ bớt bộ lọc.",
                                style = androidx.compose.material3.MaterialTheme.typography.bodyMedium,
                                color = androidx.compose.material3.MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                } else {
                    items(uiState.users, key = { it.id }) { user ->
                        UserCard(
                            user = user,
                            onActivate = { viewModel.updateUserStatus(user.id, "active") },
                            onDeactivate = { viewModel.updateUserStatus(user.id, "suspended") },
                            onApprove = if (
                                (user.role.equals("partner", ignoreCase = true) || user.role.equals("farmer", ignoreCase = true)) &&
                                !user.status.equals("active", ignoreCase = true)
                            ) {
                                { viewModel.approvePartner(user.id) }
                            } else {
                                null
                            }
                        )
                    }
                }
            }

            if (!showFullScreenLoading) {
                PullRefreshIndicator(
                    refreshing = uiState.isLoading,
                    state = pullRefreshState,
                    modifier = Modifier.align(Alignment.TopCenter)
                )
            }

            if (showFullScreenLoading) {
                LoadingState(
                    modifier = Modifier
                        .fillMaxSize()
                        .align(Alignment.Center)
                )
            }
        }
    }
}

@Composable
private fun UserCard(
    user: AdminUser,
    onActivate: () -> Unit,
    onDeactivate: () -> Unit,
    onApprove: (() -> Unit)?
) {
    DashboardSectionCard(
        title = user.fullName,
        subtitle = user.email
    ) {
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            StatusBadge(
                text = formatRoleLabel(user.role),
                tone = StatusTone.NEUTRAL
            )
            StatusBadge(
                text = formatPublishStatus(user.status),
                tone = if (user.status.equals("active", ignoreCase = true)) {
                    StatusTone.POSITIVE
                } else {
                    StatusTone.WARNING
                }
            )
        }
        user.organizationName?.takeIf { it.isNotBlank() }?.let { organization ->
            Text(
                text = organization,
                style = androidx.compose.material3.MaterialTheme.typography.bodySmall,
                color = androidx.compose.material3.MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            onApprove?.let { approve ->
                OutlinedButton(
                    onClick = approve,
                    modifier = Modifier.weight(1f)
                ) {
                    Text("Duyệt")
                }
            }
            OutlinedButton(
                onClick = onActivate,
                modifier = Modifier.weight(1f)
            ) {
                Text("Kích hoạt")
            }
            OutlinedButton(
                onClick = onDeactivate,
                modifier = Modifier.weight(1f)
            ) {
                Text("Tạm khóa")
            }
        }
    }
}
