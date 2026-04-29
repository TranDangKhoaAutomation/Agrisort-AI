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
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.agrisort_ai.features.dashboard.data.remote.CreateLotRequest
import com.example.agrisort_ai.features.dashboard.data.remote.UpdateLotRequest
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerLot
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.AppTextField
import com.example.agrisort_ai.ui.components.LoadingState
import com.example.agrisort_ai.ui.components.MessageType

@OptIn(ExperimentalMaterial3Api::class, ExperimentalMaterialApi::class)
@Composable
fun PartnerDashboardScreen(
    viewModel: PartnerDashboardViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    PartnerDashboardContent(
        uiState = uiState,
        onRefresh = viewModel::refreshAll,
        onCreateLot = viewModel::createLot,
        onUpdateLot = viewModel::updateLot,
        onDeleteLot = viewModel::deleteLot,
        onRegenerateLotQr = viewModel::regenerateLotQr
    )
}

@OptIn(ExperimentalMaterial3Api::class, ExperimentalMaterialApi::class)
@Composable
fun PartnerDashboardContent(
    uiState: PartnerDashboardUiState,
    onRefresh: () -> Unit,
    onCreateLot: (CreateLotRequest) -> Unit,
    onUpdateLot: (Int, UpdateLotRequest) -> Unit,
    onDeleteLot: (Int) -> Unit,
    onRegenerateLotQr: (Int) -> Unit,
    modifier: Modifier = Modifier
) {
    val showLoadingFallback = shouldShowPartnerLoading(uiState)
    val showFullScreenLoading = showLoadingFallback ||
        (uiState.isLoading && uiState.lots.isEmpty() && uiState.packages.isEmpty())
    val lots = uiState.lots
    val packages = uiState.packages
    val metrics = derivePartnerDashboardMetrics(lots, packages)
    val totalGrade1 = lots.sumOf { it.grade1Count ?: 0 }
    val totalGrade2 = lots.sumOf { it.grade2Count ?: 0 }
    val totalDefect = lots.sumOf { it.defectCount ?: 0 }

    var currentStep by remember { mutableIntStateOf(0) }
    var editingLotId by remember { mutableStateOf<Int?>(null) }
    var lotCode by remember { mutableStateOf("") }
    var produceType by remember { mutableStateOf("") }
    var originRegion by remember { mutableStateOf("") }
    var harvestDate by remember { mutableStateOf("") }
    var grade1Count by remember { mutableStateOf("") }
    var grade2Count by remember { mutableStateOf("") }
    var defectCount by remember { mutableStateOf("") }
    var notes by remember { mutableStateOf("") }
    var imageUri by remember { mutableStateOf<Uri?>(null) }

    val imagePicker = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri ->
        imageUri = uri
    }

    fun resetLotForm() {
        editingLotId = null
        currentStep = 0
        lotCode = ""
        produceType = ""
        originRegion = ""
        harvestDate = ""
        grade1Count = ""
        grade2Count = ""
        defectCount = ""
        notes = ""
        imageUri = null
    }

    val pullRefreshState = rememberPullRefreshState(
        refreshing = uiState.isLoading,
        onRefresh = onRefresh
    )

    Scaffold(
        modifier = modifier,
        topBar = { TopAppBar(title = { Text("Điều hành lô hàng") }) }
    ) { paddingValues ->
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
                            title = "Phiên phân loại hôm nay",
                            subtitle = "Theo dõi năng suất, mức lỗi và độ sẵn sàng truy xuất của từng lô."
                        ) {
                            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                StatusBadge(text = "Camera AI", tone = StatusTone.NEUTRAL)
                                StatusBadge(text = "<= 200 ms", tone = StatusTone.NEUTRAL)
                                StatusBadge(text = "QR theo lô", tone = StatusTone.POSITIVE)
                            }
                            Text(
                                text = "Ưu tiên hoàn thiện mã lô, vùng trồng, ngày thu hoạch, ảnh sản phẩm và kết quả phân loại trước khi phát hành QR.",
                                style = MaterialTheme.typography.bodyMedium
                            )
                        }
                    }

                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            KpiCard(
                                title = "Lô đang xử lý",
                                value = metrics.activeLots.toString(),
                                caption = "Toàn bộ lô hiện đang theo dõi",
                                modifier = Modifier.weight(1f)
                            )
                            KpiCard(
                                title = "Lô sẵn sàng QR",
                                value = metrics.readyQrLots.toString(),
                                caption = "Đã đủ dữ liệu để cấp mã truy xuất",
                                modifier = Modifier.weight(1f)
                            )
                        }
                    }

                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            KpiCard(
                                title = "Tổng sản phẩm",
                                value = metrics.totalProduce.toString(),
                                caption = "Số lượng đã ghi nhận trong các lô",
                                modifier = Modifier.weight(1f)
                            )
                            KpiCard(
                                title = "Tỉ lệ lỗi",
                                value = "${metrics.defectRatePercent}%",
                                caption = "Tính trên dữ liệu phân loại hiện có",
                                modifier = Modifier.weight(1f)
                            )
                        }
                    }

                    item {
                        QualityBreakdownCard(
                            title = "Chất lượng theo cấp",
                            subtitle = "Tổng hợp từ dữ liệu lô đã đồng bộ.",
                            grade1Count = totalGrade1,
                            grade2Count = totalGrade2,
                            defectCount = totalDefect
                        )
                    }

                    item {
                        DashboardSectionCard(
                            title = if (editingLotId == null) "Tạo lô mới" else "Cập nhật lô #$editingLotId",
                            subtitle = "Ảnh lô được gửi lên ngay từ app để đồng bộ dữ liệu và QR."
                        ) {
                            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                StepBadge(step = 1, title = "Thông tin lô", active = currentStep == 0)
                                StepBadge(step = 2, title = "Kết quả", active = currentStep == 1)
                                StepBadge(step = 3, title = "Ảnh và QR", active = currentStep == 2)
                            }

                            if (editingLotId != null) {
                                StatusBadge(
                                    text = "Đang sửa lô",
                                    tone = StatusTone.WARNING
                                )
                            }

                            when (currentStep) {
                                0 -> {
                                    AppTextField(value = lotCode, onValueChange = { lotCode = it }, label = "Mã lô")
                                    AppTextField(value = produceType, onValueChange = { produceType = it }, label = "Loại nông sản")
                                    AppTextField(value = originRegion, onValueChange = { originRegion = it }, label = "Vùng trồng")
                                    AppTextField(
                                        value = harvestDate,
                                        onValueChange = { harvestDate = it },
                                        label = "Ngày thu hoạch (YYYY-MM-DD)"
                                    )
                                }

                                1 -> {
                                    AppTextField(
                                        value = grade1Count,
                                        onValueChange = { grade1Count = it.filter(Char::isDigit) },
                                        label = "Số lượng loại 1"
                                    )
                                    AppTextField(
                                        value = grade2Count,
                                        onValueChange = { grade2Count = it.filter(Char::isDigit) },
                                        label = "Số lượng loại 2"
                                    )
                                    AppTextField(
                                        value = defectCount,
                                        onValueChange = { defectCount = it.filter(Char::isDigit) },
                                        label = "Số lượng lỗi"
                                    )
                                }

                                else -> {
                                    AppTextField(
                                        value = notes,
                                        onValueChange = { notes = it },
                                        label = "Ghi chú vận hành",
                                        singleLine = false
                                    )
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                                    ) {
                                        OutlinedButton(
                                            onClick = { imagePicker.launch("image/*") },
                                            modifier = Modifier.weight(1f)
                                        ) {
                                            Text("Chọn ảnh")
                                        }
                                        if (editingLotId != null) {
                                            OutlinedButton(
                                                onClick = { imageUri = null },
                                                modifier = Modifier.weight(1f)
                                            ) {
                                                Text("Giữ ảnh cũ")
                                            }
                                        }
                                    }
                                    Text(
                                        text = when {
                                            imageUri != null -> "Ảnh đã chọn: ${imageUri?.lastPathSegment}"
                                            editingLotId != null -> "Không chọn ảnh mới sẽ giữ ảnh hiện tại của lô."
                                            else -> "Chưa chọn ảnh sản phẩm."
                                        },
                                        style = MaterialTheme.typography.bodySmall,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                    StatusBadge(
                                        text = when {
                                            editingLotId == null && imageUri == null -> "Cần ảnh để tạo lô"
                                            lotCode.isBlank() || produceType.isBlank() || originRegion.isBlank() || harvestDate.isBlank() -> "Thiếu thông tin cơ bản"
                                            else -> "Sẵn sàng gửi lên hệ thống"
                                        },
                                        tone = when {
                                            editingLotId == null && imageUri == null -> StatusTone.WARNING
                                            lotCode.isBlank() || produceType.isBlank() || originRegion.isBlank() || harvestDate.isBlank() -> StatusTone.WARNING
                                            else -> StatusTone.POSITIVE
                                        }
                                    )
                                }
                            }

                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                OutlinedButton(
                                    onClick = {
                                        if (editingLotId != null && currentStep == 0) {
                                            resetLotForm()
                                        } else if (currentStep > 0) {
                                            currentStep -= 1
                                        }
                                    },
                                    modifier = Modifier.weight(1f)
                                ) {
                                    Text(
                                        if (editingLotId != null && currentStep == 0) {
                                            "Hủy sửa"
                                        } else {
                                            "Quay lại"
                                        }
                                    )
                                }
                                if (currentStep < 2) {
                                    Button(
                                        onClick = { currentStep += 1 },
                                        modifier = Modifier.weight(1f)
                                    ) {
                                        Text("Tiếp tục")
                                    }
                                } else {
                                    val canSubmit = lotCode.isNotBlank() &&
                                        produceType.isNotBlank() &&
                                        originRegion.isNotBlank() &&
                                        harvestDate.isNotBlank() &&
                                        (editingLotId != null || imageUri != null)
                                    Button(
                                        onClick = {
                                            if (editingLotId == null) {
                                                onCreateLot(
                                                    CreateLotRequest(
                                                        lotCode = lotCode.trim(),
                                                        produceType = produceType.trim(),
                                                        originRegion = originRegion.trim(),
                                                        harvestDate = harvestDate.trim(),
                                                        grade1Count = grade1Count.toIntOrNull() ?: 0,
                                                        grade2Count = grade2Count.toIntOrNull() ?: 0,
                                                        defectCount = defectCount.toIntOrNull() ?: 0,
                                                        notes = notes.ifBlank { null },
                                                        imageUri = imageUri
                                                    )
                                                )
                                            } else {
                                                onUpdateLot(
                                                    editingLotId ?: return@Button,
                                                    UpdateLotRequest(
                                                        lotCode = lotCode.trim(),
                                                        produceType = produceType.trim(),
                                                        originRegion = originRegion.trim(),
                                                        harvestDate = harvestDate.trim(),
                                                        grade1Count = grade1Count.toIntOrNull() ?: 0,
                                                        grade2Count = grade2Count.toIntOrNull() ?: 0,
                                                        defectCount = defectCount.toIntOrNull() ?: 0,
                                                        notes = notes.ifBlank { null },
                                                        imageUri = imageUri
                                                    )
                                                )
                                            }
                                        },
                                        modifier = Modifier.weight(1f),
                                        enabled = canSubmit
                                    ) {
                                        Text(if (editingLotId == null) "Lưu lô" else "Cập nhật lô")
                                    }
                                }
                            }
                        }
                    }

                    item {
                        DashboardSectionCard(
                            title = "Lô gần đây",
                            subtitle = "Chọn một lô để sửa, tạo lại QR hoặc xóa khi cần."
                        ) {
                            if (lots.isEmpty() && !uiState.isLoading) {
                                Text(
                                    text = "Chưa có lô nào được đồng bộ.",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            } else {
                                lots.take(8).forEach { lot ->
                                    LotManagementCard(
                                        lot = lot,
                                        onEdit = {
                                            editingLotId = lot.id
                                            currentStep = 0
                                            lotCode = lot.lotCode
                                            produceType = lot.produceType
                                            originRegion = lot.originRegion.orEmpty()
                                            harvestDate = lot.harvestDate.orEmpty()
                                            grade1Count = (lot.grade1Count ?: 0).toString()
                                            grade2Count = (lot.grade2Count ?: 0).toString()
                                            defectCount = (lot.defectCount ?: 0).toString()
                                            notes = lot.notes.orEmpty()
                                            imageUri = null
                                        },
                                        onRegenerateQr = { onRegenerateLotQr(lot.id) },
                                        onDelete = { onDeleteLot(lot.id) }
                                    )
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

private fun shouldShowPartnerLoading(uiState: PartnerDashboardUiState): Boolean {
    return uiState.dataSourceState == DataSourceState.ERROR &&
        uiState.lots.isEmpty() &&
        uiState.packages.isEmpty()
}

@Composable
private fun StepBadge(
    step: Int,
    title: String,
    active: Boolean
) {
    StatusBadge(
        text = "$step. $title",
        tone = if (active) StatusTone.POSITIVE else StatusTone.NEUTRAL
    )
}

@Composable
private fun LotManagementCard(
    lot: PartnerLot,
    onEdit: () -> Unit,
    onRegenerateQr: () -> Unit,
    onDelete: () -> Unit
) {
    val workflowStatus = lot.workflowStatus()
    DashboardSectionCard(
        title = "${lot.lotCode} • ${lot.produceType}",
        subtitle = listOfNotNull(
            lot.originRegion?.takeIf { it.isNotBlank() },
            lot.harvestDate?.takeIf { it.isNotBlank() }
        ).joinToString(" | ").ifBlank { "Đang chờ bổ sung thông tin vùng trồng và ngày thu hoạch" }
    ) {
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            StatusBadge(text = formatWorkflowStatus(workflowStatus), tone = StatusTone.NEUTRAL)
            StatusBadge(
                text = formatPublishStatus(lot.publishStatus),
                tone = if (lot.publishStatus.equals("published", ignoreCase = true)) {
                    StatusTone.POSITIVE
                } else {
                    StatusTone.WARNING
                }
            )
        }
        Text(
            text = buildString {
                if (!lot.qrToken.isNullOrBlank()) {
                    append("Token: ${lot.qrToken}")
                }
                if (lot.totalQualityCount() > 0) {
                    if (isNotEmpty()) append(" • ")
                    append("Tổng ${lot.totalQualityCount()} sản phẩm")
                }
                lot.imageUrl?.takeIf { it.isNotBlank() }?.let {
                    if (isNotEmpty()) append(" • ")
                    append("Có ảnh")
                }
            },
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            OutlinedButton(
                onClick = onEdit,
                modifier = Modifier.weight(1f)
            ) {
                Text("Sửa")
            }
            OutlinedButton(
                onClick = onRegenerateQr,
                modifier = Modifier.weight(1f)
            ) {
                Text("Tạo lại QR")
            }
            OutlinedButton(
                onClick = onDelete,
                modifier = Modifier.weight(1f)
            ) {
                Text("Xóa")
            }
        }
    }
}
