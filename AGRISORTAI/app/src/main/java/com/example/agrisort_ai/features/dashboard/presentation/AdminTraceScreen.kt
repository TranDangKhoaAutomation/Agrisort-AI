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
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.AppTextField
import com.example.agrisort_ai.ui.components.LoadingState
import com.example.agrisort_ai.ui.components.MessageType

@OptIn(ExperimentalMaterial3Api::class, ExperimentalMaterialApi::class)
@Composable
fun AdminTraceScreen(
    onNavigateBack: () -> Unit,
    viewModel: AdminDashboardViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val showFullScreenLoading = uiState.isLoading && uiState.assignments.isEmpty()
    var entityType by remember { mutableStateOf("lot") }
    var entityId by remember { mutableStateOf("101") }
    var stageCode by remember { mutableStateOf("transport-checkin") }
    var actorUserId by remember { mutableStateOf("12") }

    val pullRefreshState = rememberPullRefreshState(
        refreshing = uiState.isLoading,
        onRefresh = {
            viewModel.loadAssignments()
            viewModel.loadEvents()
        }
    )

    Scaffold(topBar = { TopAppBar(title = { Text("Điều phối truy xuất (cho vận chuyển, kho, người bán)") }) }) { paddingValues ->
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
                        title = "Tạo phân công stage (cho vận chuyển, kho, người bán)",
                        subtitle = "Gán đúng người phụ trách cho đúng chặng để truy xuất không bị thiếu mắt xích."
                    ) {
                        AppTextField(
                            value = entityType,
                            onValueChange = { entityType = it },
                            label = "Loại đối tượng"
                        )
                        AppTextField(
                            value = entityId,
                            onValueChange = { entityId = it.filter(Char::isDigit) },
                            label = "ID đối tượng"
                        )
                        AppTextField(
                            value = stageCode,
                            onValueChange = { stageCode = it },
                            label = "Mã stage"
                        )
                        AppTextField(
                            value = actorUserId,
                            onValueChange = { actorUserId = it.filter(Char::isDigit) },
                            label = "ID người phụ trách"
                        )
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
                                onClick = {
                                    viewModel.saveAssignment(
                                        entityType = entityType.trim(),
                                        entityId = entityId.toIntOrNull() ?: 0,
                                        stageCode = stageCode.trim(),
                                        actorUserId = actorUserId.toIntOrNull() ?: 0
                                    )
                                },
                                modifier = Modifier.weight(1f)
                            ) {
                                Text("Lưu phân công")
                            }
                        }
                    }
                }

                item {
                    DashboardSectionCard(
                        title = "Phân công hiện có (cho vận chuyển, kho, người bán)",
                        subtitle = "Danh sách stage đang được giao cho các tác nhân trong chuỗi."
                    ) {
                        if (uiState.assignments.isEmpty() && !uiState.isLoading) {
                            Text(
                                text = "Chưa có phân công truy xuất nào được đồng bộ.",
                                style = androidx.compose.material3.MaterialTheme.typography.bodyMedium,
                                color = androidx.compose.material3.MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        } else {
                            uiState.assignments.take(10).forEach { assignment ->
                                DashboardSectionCard(
                                    title = formatStageLabel(assignment.stageCode),
                                    subtitle = "${formatEntityType(assignment.entityType)} #${assignment.entityId}"
                                ) {
                                    Text("Phụ trách: ${assignment.actorName ?: assignment.actorUserId?.toString().orEmpty()}")
                                    OutlinedButton(
                                        onClick = { viewModel.deleteAssignment(assignment.id) },
                                        modifier = Modifier.fillMaxWidth()
                                    ) {
                                        Text("Xóa phân công")
                                    }
                                }
                            }
                        }
                    }
                }

                item {
                    DashboardSectionCard(
                        title = "Dòng sự kiện truy xuất (cho vận chuyển, kho, người bán)",
                        subtitle = "Theo dõi stage nào đã có dữ liệu thời gian, vị trí và ghi chú đầy đủ."
                    ) {
                        if (uiState.events.isEmpty() && !uiState.isLoading) {
                            Text(
                                text = "Chưa có sự kiện truy xuất nào được đồng bộ.",
                                style = androidx.compose.material3.MaterialTheme.typography.bodyMedium,
                                color = androidx.compose.material3.MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        } else {
                            uiState.events.take(12).forEach { event ->
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
