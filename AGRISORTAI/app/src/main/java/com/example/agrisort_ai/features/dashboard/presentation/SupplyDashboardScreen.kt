package com.example.agrisort_ai.features.dashboard.presentation

import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
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
import com.example.agrisort_ai.features.dashboard.data.remote.CreateSupplyEventRequest
import com.example.agrisort_ai.features.dashboard.data.remote.UpdateSupplyEventRequest
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.AppTextField
import com.example.agrisort_ai.ui.components.LoadingState
import com.example.agrisort_ai.ui.components.MessageType

@OptIn(ExperimentalMaterial3Api::class, ExperimentalMaterialApi::class)
@Composable
fun SupplyDashboardScreen(
    viewModel: SupplyDashboardViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val showLoadingFallback = shouldShowSupplyLoading(uiState)
    val showFullScreenLoading = showLoadingFallback ||
        (uiState.isLoading && uiState.assignments.isEmpty() && uiState.events.isEmpty())
    val assignments = uiState.assignments
    val events = uiState.events

    var entityType by remember { mutableStateOf("package") }
    var entityId by remember { mutableStateOf("305") }
    var stageCode by remember { mutableStateOf("warehouse-checkin") }
    var eventTime by remember { mutableStateOf("2026-03-06 09:30:00") }
    var locationName by remember { mutableStateOf("") }
    var note by remember { mutableStateOf("") }
    var updateEventId by remember { mutableStateOf("1") }
    var attachmentUris by remember { mutableStateOf<List<Uri>>(emptyList()) }

    val attachmentPicker = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.OpenMultipleDocuments()
    ) { uris ->
        attachmentUris = uris
    }

    val pullRefreshState = rememberPullRefreshState(
        refreshing = uiState.isLoading,
        onRefresh = viewModel::refreshAll
    )

    Scaffold(topBar = { TopAppBar(title = { Text("Bàn giao và vận hành chuỗi") }) }) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .pullRefresh(pullRefreshState)
        ) {
            if (!showLoadingFallback) {
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
                            title = "Bảng stage hiện trường",
                            subtitle = "Ghi nhận đúng thời điểm để timeline truy xuất không bị đứt mạch."
                        ) {
                            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                StatusBadge("Nhập kho", StatusTone.NEUTRAL)
                                StatusBadge("Vận chuyển", StatusTone.WARNING)
                                StatusBadge("Trưng bày", StatusTone.POSITIVE)
                            }
                        }
                    }

                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            KpiCard(
                                title = "Stage được giao",
                                value = assignments.size.toString(),
                                caption = "Các điểm xác nhận đang chờ xử lý",
                                modifier = Modifier.weight(1f)
                            )
                            KpiCard(
                                title = "Sự kiện đã ghi",
                                value = events.size.toString(),
                                caption = "Lịch sử vận hành đã được lưu",
                                modifier = Modifier.weight(1f)
                            )
                        }
                    }

                    item {
                        DashboardSectionCard(
                            title = "Stage đang phụ trách",
                            subtitle = "Các stage này nên được xác nhận tại hiện trường với thời gian và địa điểm rõ ràng."
                        ) {
                            if (assignments.isEmpty() && !uiState.isLoading) {
                                Text(
                                    text = "Chưa có stage nào được giao.",
                                    style = androidx.compose.material3.MaterialTheme.typography.bodyMedium,
                                    color = androidx.compose.material3.MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            } else {
                                assignments.take(10).forEach { assignment ->
                                    TimelineEntryCard(
                                        title = formatStageLabel(assignment.stageCode),
                                        subtitle = "${formatEntityType(assignment.entityType)} #${assignment.entityId}",
                                        description = "Mã stage: ${assignment.stageCode}"
                                    )
                                }
                            }
                        }
                    }

                    item {
                        DashboardSectionCard(
                            title = "Ghi nhận sự kiện",
                            subtitle = "Hỗ trợ upload tệp đính kèm để lưu biên bản, ảnh hiện trường và file PDF."
                        ) {
                            AppTextField(value = entityType, onValueChange = { entityType = it }, label = "Loại đối tượng")
                            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                AppTextField(
                                    value = entityId,
                                    onValueChange = { entityId = it.filter(Char::isDigit) },
                                    label = "ID đối tượng",
                                    modifier = Modifier.weight(1f)
                                )
                                AppTextField(
                                    value = stageCode,
                                    onValueChange = { stageCode = it },
                                    label = "Mã stage",
                                    modifier = Modifier.weight(1f)
                                )
                            }
                            AppTextField(value = eventTime, onValueChange = { eventTime = it }, label = "Thời gian sự kiện")
                            AppTextField(value = locationName, onValueChange = { locationName = it }, label = "Điểm xác nhận")
                            AppTextField(value = note, onValueChange = { note = it }, label = "Ghi chú", singleLine = false)
                            AppTextField(
                                value = updateEventId,
                                onValueChange = { updateEventId = it.filter(Char::isDigit) },
                                label = "ID sự kiện cần cập nhật"
                            )
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                OutlinedButton(
                                    onClick = { attachmentPicker.launch(arrayOf("*/*")) },
                                    modifier = Modifier.weight(1f)
                                ) {
                                    Text("Chọn tệp")
                                }
                                OutlinedButton(
                                    onClick = { attachmentUris = emptyList() },
                                    modifier = Modifier.weight(1f)
                                ) {
                                    Text("Bỏ tệp")
                                }
                            }
                            Text(
                                text = if (attachmentUris.isEmpty()) {
                                    "Chưa chọn tệp đính kèm."
                                } else {
                                    "Tệp đính kèm: ${attachmentUris.joinToString { it.lastPathSegment.orEmpty() }}"
                                },
                                style = androidx.compose.material3.MaterialTheme.typography.bodySmall,
                                color = androidx.compose.material3.MaterialTheme.colorScheme.onSurfaceVariant
                            )
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                Button(
                                    onClick = {
                                        viewModel.createEvent(
                                            CreateSupplyEventRequest(
                                                entityType = entityType.trim(),
                                                entityId = entityId.toIntOrNull() ?: 0,
                                                stageCode = stageCode.trim(),
                                                eventTime = eventTime.trim(),
                                                locationName = locationName.trim(),
                                                note = note.ifBlank { null },
                                                attachmentUris = attachmentUris
                                            )
                                        )
                                    },
                                    modifier = Modifier.weight(1f)
                                ) {
                                    Text("Tạo sự kiện")
                                }
                                Button(
                                    onClick = {
                                        viewModel.updateEvent(
                                            eventId = updateEventId.toIntOrNull() ?: 0,
                                            request = UpdateSupplyEventRequest(
                                                eventTime = eventTime.trim(),
                                                locationName = locationName.trim(),
                                                note = note.ifBlank { null },
                                                attachmentUris = attachmentUris
                                            )
                                        )
                                    },
                                    modifier = Modifier.weight(1f)
                                ) {
                                    Text("Cập nhật")
                                }
                            }
                        }
                    }

                    item {
                        DashboardSectionCard(
                            title = "Timeline gần đây",
                            subtitle = "Theo dõi các sự kiện đã được xác nhận và tệp đính kèm backend trả về."
                        ) {
                            if (events.isEmpty() && !uiState.isLoading) {
                                Text(
                                    text = "Chưa có sự kiện vận hành nào được ghi nhận.",
                                    style = androidx.compose.material3.MaterialTheme.typography.bodyMedium,
                                    color = androidx.compose.material3.MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            } else {
                                events.take(12).forEach { event ->
                                    DashboardSectionCard(
                                        title = formatStageLabel(event.stageCode),
                                        subtitle = "${event.eventTime} | ${formatEntityType(event.entityType)} #${event.entityId}"
                                    ) {
                                        Text(
                                            listOfNotNull(event.locationName, event.note).joinToString(" | "),
                                            style = androidx.compose.material3.MaterialTheme.typography.bodyMedium
                                        )
                                        if (event.attachments.isNotEmpty()) {
                                            Text(
                                                text = "Tệp đính kèm: ${event.attachments.joinToString { it.fileName ?: it.fileUrl.orEmpty() }}",
                                                style = androidx.compose.material3.MaterialTheme.typography.bodySmall,
                                                color = androidx.compose.material3.MaterialTheme.colorScheme.onSurfaceVariant
                                            )
                                        }
                                    }
                                }
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

private fun shouldShowSupplyLoading(uiState: SupplyDashboardUiState): Boolean {
    return uiState.dataSourceState == DataSourceState.ERROR &&
        uiState.assignments.isEmpty() &&
        uiState.events.isEmpty()
}
