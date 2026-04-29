package com.example.agrisort_ai.features.dashboard.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.ExperimentalMaterialApi
import androidx.compose.material.pullrefresh.PullRefreshIndicator
import androidx.compose.material.pullrefresh.pullRefresh
import androidx.compose.material.pullrefresh.rememberPullRefreshState
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
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
import com.example.agrisort_ai.features.dashboard.domain.model.AdminDashboardSummary
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.LoadingState
import com.example.agrisort_ai.ui.components.MessageType

private fun adminAudienceTitle(title: String, audience: String): String = "$title ($audience)"

@OptIn(ExperimentalMaterial3Api::class, ExperimentalMaterialApi::class)
@Composable
fun AdminDashboardScreen(
    viewModel: AdminDashboardViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val showLoadingFallback = shouldShowAdminLoading(uiState)
    val showFullScreenLoading = showLoadingFallback ||
        (uiState.isLoading && uiState.users.isEmpty() && uiState.events.isEmpty())
    val pullRefreshState = rememberPullRefreshState(
        refreshing = uiState.isLoading,
        onRefresh = viewModel::refreshAll
    )

    Scaffold(topBar = { TopAppBar(title = { Text(adminAudienceTitle("Trung tâm điều hành", "cho toàn hệ thống")) }) }) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .pullRefresh(pullRefreshState)
        ) {
            if (!showLoadingFallback) {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .verticalScroll(rememberScrollState())
                        .padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    DataSourceBanner(
                        state = uiState.dataSourceState,
                        errorMessage = uiState.error
                    )

                    if (!uiState.message.isNullOrBlank()) {
                        AppMessage(
                            message = uiState.message.orEmpty(),
                            type = MessageType.SUCCESS
                        )
                    }

                    AdminOverviewCard(
                        summary = uiState.summary
                    )

                    AdminOperationsCard(
                        summary = uiState.summary
                    )

                    DashboardSectionCard(
                        title = adminAudienceTitle("Tài khoản cần chú ý", "cho mọi role"),
                        subtitle = "Ưu tiên phê duyệt đúng vai trò và rà soát tài khoản đang ở trạng thái chưa sẵn sàng."
                    ) {
                        if (uiState.users.isEmpty() && !uiState.isLoading) {
                            Text(
                                text = "Chưa có dữ liệu tài khoản để hiển thị.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        } else {
                            uiState.users.take(5).forEach { user ->
                                TimelineEntryCard(
                                    title = user.fullName,
                                    subtitle = "${formatRoleLabel(user.role)} • ${formatPublishStatus(user.status)}",
                                    description = user.email
                                )
                            }
                        }
                    }

                    DashboardSectionCard(
                        title = adminAudienceTitle("Sự kiện truy xuất gần nhất", "cho vận chuyển, kho, người bán"),
                        subtitle = "Giúp phát hiện stage nào đang cập nhật tốt và stage nào còn thiếu dữ liệu."
                    ) {
                        if (uiState.events.isEmpty() && !uiState.isLoading) {
                            Text(
                                text = "Chưa có sự kiện truy xuất nào được đồng bộ.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        } else {
                            uiState.events.take(6).forEach { event ->
                                TimelineEntryCard(
                                    title = formatStageLabel(event.stageCode),
                                    subtitle = event.eventTime,
                                    description = listOfNotNull(
                                        event.locationName,
                                        event.actorName,
                                        event.note
                                    ).joinToString(" • ")
                                )
                            }
                        }
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

private fun shouldShowAdminLoading(uiState: AdminDashboardUiState): Boolean {
    return uiState.dataSourceState == DataSourceState.ERROR &&
        uiState.summary == AdminDashboardSummary() &&
        uiState.users.isEmpty() &&
        uiState.events.isEmpty() &&
        uiState.assignments.isEmpty()
}

@OptIn(ExperimentalLayoutApi::class)
@Composable
private fun AdminOverviewCard(
    summary: AdminDashboardSummary
) {
    DashboardSectionCard(
        title = adminAudienceTitle("Tổng quan điều hành", "cho toàn hệ thống"),
        subtitle = "Theo dõi tài khoản, lô hàng và độ sẵn sàng truy xuất trên cùng một màn hình."
    ) {
        FlowRow(
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            StatusBadge("Dữ liệu theo vai trò", StatusTone.NEUTRAL)
            StatusBadge("Truy xuất toàn chuỗi", StatusTone.POSITIVE)
            StatusBadge("Kiểm soát công khai", StatusTone.WARNING)
        }

        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            KpiCard(
                title = "Người dùng",
                value = summary.totalUsers.toString(),
                caption = "Tổng tài khoản đã đăng ký",
                modifier = Modifier.weight(1f)
            )
            KpiCard(
                title = "Đang hoạt động",
                value = summary.activeUsers.toString(),
                caption = "Tài khoản đã sẵn sàng vận hành",
                modifier = Modifier.weight(1f)
            )
        }
    }
}

@Composable
private fun AdminOperationsCard(
    summary: AdminDashboardSummary
) {
    DashboardSectionCard(
        title = adminAudienceTitle("Ưu tiên điều hành", "cho toàn hệ thống"),
        subtitle = "Theo dõi nhanh tồn đọng công khai, duyệt đối tác và các điểm cần ưu tiên xử lý."
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            KpiCard(
                title = "Tổng lô hàng",
                value = summary.totalLots.toString(),
                caption = "Bao gồm lô nháp và lô công khai",
                modifier = Modifier.weight(1f)
            )
            KpiCard(
                title = "Đã công khai",
                value = summary.publishedLots.toString(),
                caption = "Sẵn sàng cho người mua truy xuất",
                modifier = Modifier.weight(1f)
            )
        }

        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            Column(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                Text(
                    text = "Đối tác chờ duyệt",
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.SemiBold
                )
                Text(
                    text = "Cần xác minh trước khi cấp quyền vận hành sâu.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            Text(
                text = summary.pendingPartners.toString(),
                style = MaterialTheme.typography.displaySmall,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary
            )
        }

    }
}
