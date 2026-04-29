package com.example.agrisort_ai.features.dashboard.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Button
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalUriHandler
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.agrisort_ai.R
import com.example.agrisort_ai.features.dashboard.domain.model.TraceAttachment
import com.example.agrisort_ai.features.dashboard.domain.model.TraceEvent
import com.example.agrisort_ai.features.dashboard.domain.model.TraceLookupResult
import com.example.agrisort_ai.ui.components.AppToastEffect
import com.example.agrisort_ai.ui.components.EmptyState
import com.example.agrisort_ai.ui.components.LoadingState

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TraceResultScreen(
    token: String,
    onNavigateBack: () -> Unit,
    onNavigateHistory: () -> Unit,
    viewModel: TraceViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    LaunchedEffect(token) {
        viewModel.openToken(token)
    }

    AppToastEffect(
        message = uiState.error ?: uiState.message,
        eventKey = uiState.feedbackId
    )

    TraceResultStateContent(
        token = token,
        uiState = uiState,
        onRetry = { viewModel.openToken(token) },
        onNavigateBack = onNavigateBack,
        onNavigateHistory = onNavigateHistory
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TraceResultStateContent(
    token: String,
    uiState: TraceUiState,
    onRetry: () -> Unit,
    onNavigateBack: () -> Unit,
    onNavigateHistory: () -> Unit,
    modifier: Modifier = Modifier
) {
    val result = uiState.currentResult

    Scaffold(
        modifier = modifier,
        topBar = { TopAppBar(title = { Text(stringResource(R.string.trace_result_title)) }) }
    ) { paddingValues ->
        when {
            uiState.isLoading && result == null -> LoadingState(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
            )

            result == null -> EmptyState(
                title = stringResource(R.string.trace_result_empty_title),
                message = uiState.error ?: stringResource(R.string.trace_result_empty_message),
                actionLabel = stringResource(R.string.trace_result_retry),
                onAction = onRetry,
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .padding(16.dp)
            )

            else -> TraceResultContent(
                token = token,
                paddingValues = paddingValues,
                uiState = uiState,
                result = result,
                onNavigateBack = onNavigateBack,
                onNavigateHistory = onNavigateHistory
            )
        }
    }
}

@Composable
private fun TraceResultContent(
    token: String,
    paddingValues: PaddingValues,
    uiState: TraceUiState,
    result: TraceLookupResult,
    onNavigateBack: () -> Unit,
    onNavigateHistory: () -> Unit
) {
    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .padding(paddingValues),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        item {
            DataSourceBanner(
                state = uiState.dataSourceState,
                errorMessage = uiState.error
            )
        }

        item {
            DashboardSectionCard(
                title = "Thông tin truy xuất",
                subtitle = "Dữ liệu hiển thị theo token công khai và timeline đã được đồng bộ."
            ) {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    StatusBadge(
                        text = formatEntityType(result.entityType),
                        tone = StatusTone.NEUTRAL
                    )
                    StatusBadge(
                        text = formatPublishStatus(result.publishStatus),
                        tone = when (result.publishStatus?.lowercase()) {
                            "published" -> StatusTone.POSITIVE
                            "draft" -> StatusTone.WARNING
                            else -> StatusTone.NEUTRAL
                        }
                    )
                }
                TraceInfoRow("Token", token)
                result.lotCode?.takeIf { it.isNotBlank() }?.let { TraceInfoRow("Mã lô", it) }
                result.packageCode?.takeIf { it.isNotBlank() }?.let { TraceInfoRow("Mã kiện", it) }
                result.produceType?.takeIf { it.isNotBlank() }?.let { TraceInfoRow("Nông sản", it) }
                result.originRegion?.takeIf { it.isNotBlank() }?.let { TraceInfoRow("Vùng trồng", it) }
                result.harvestDate?.takeIf { it.isNotBlank() }?.let { TraceInfoRow("Ngày thu hoạch", it) }
                result.notes?.takeIf { it.isNotBlank() }?.let { TraceInfoRow("Ghi chú", it) }
            }
        }

        val grade1Count = result.grade1Count ?: 0
        val grade2Count = result.grade2Count ?: 0
        val defectCount = result.defectCount ?: 0
        if (grade1Count + grade2Count + defectCount > 0) {
            item {
                QualityBreakdownCard(
                    title = "Chất lượng theo cấp",
                    subtitle = "Tổng hợp từ dữ liệu lô được công khai theo token hiện tại.",
                    grade1Count = grade1Count,
                    grade2Count = grade2Count,
                    defectCount = defectCount
                )
            }
        }

        item {
            DashboardSectionCard(
                title = stringResource(R.string.trace_result_timeline_title),
                subtitle = "Mỗi sự kiện ghi lại thời gian, địa điểm và ghi chú trong hành trình của lô hoặc kiện hàng."
            ) {
                if (result.events.isEmpty()) {
                    Text(
                        text = stringResource(R.string.trace_result_no_timeline),
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                } else {
                    result.events.forEach { event ->
                        TraceEventRow(event = event)
                    }
                }
            }
        }

        item {
            AttachmentsSection(attachments = result.attachments)
        }

        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                OutlinedButton(
                    onClick = onNavigateBack,
                    modifier = Modifier.weight(1f)
                ) {
                    Text(stringResource(R.string.trace_result_back_to_scan))
                }
                Button(
                    onClick = onNavigateHistory,
                    modifier = Modifier.weight(1f)
                ) {
                    Text(stringResource(R.string.trace_result_history))
                }
            }
        }
    }
}

@Composable
private fun TraceEventRow(event: TraceEvent) {
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

@Composable
private fun AttachmentsSection(attachments: List<TraceAttachment>) {
    val uriHandler = LocalUriHandler.current

    DashboardSectionCard(
        title = stringResource(R.string.trace_result_attachments_title),
        subtitle = "Các tệp đính kèm hỗ trợ kiểm tra lại chứng từ hoặc hình ảnh liên quan."
    ) {
        if (attachments.isEmpty()) {
            Text(
                text = stringResource(R.string.trace_result_no_attachments),
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        } else {
            attachments.forEachIndexed { index, attachment ->
                DashboardSectionCard(
                    title = attachment.fileName?.ifBlank { null } ?: "Tệp ${index + 1}",
                    subtitle = attachment.fileUrl ?: "Chưa có liên kết"
                ) {
                    OutlinedButton(
                        onClick = {
                            attachment.fileUrl?.takeIf { it.isNotBlank() }?.let(uriHandler::openUri)
                        },
                        enabled = !attachment.fileUrl.isNullOrBlank()
                    ) {
                        Text(stringResource(R.string.trace_result_open_file))
                    }
                }
            }
        }
    }
}

@Composable
private fun TraceInfoRow(label: String, value: String) {
    Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
        Text(
            text = label,
            style = MaterialTheme.typography.labelMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Text(
            text = value,
            style = MaterialTheme.typography.bodyLarge,
            fontWeight = FontWeight.Medium
        )
    }
}
