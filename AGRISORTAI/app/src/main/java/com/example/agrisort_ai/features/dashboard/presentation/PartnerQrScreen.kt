package com.example.agrisort_ai.features.dashboard.presentation

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
import com.example.agrisort_ai.features.dashboard.data.remote.CreatePackageRequest
import com.example.agrisort_ai.features.dashboard.data.remote.UpdatePackageRequest
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerPackage
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.AppTextField
import com.example.agrisort_ai.ui.components.LoadingState
import com.example.agrisort_ai.ui.components.MessageType

@OptIn(ExperimentalMaterial3Api::class, ExperimentalMaterialApi::class)
@Composable
fun PartnerQrScreen(
    onNavigateBack: () -> Unit,
    onNavigateScan: () -> Unit,
    viewModel: PartnerDashboardViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val showFullScreenLoading = uiState.isLoading && uiState.packages.isEmpty()
    val publishedPackages = uiState.packages.count { it.publishStatus.equals("published", ignoreCase = true) }

    var editingPackageId by remember { mutableStateOf<Int?>(null) }
    var lotId by remember { mutableStateOf("101") }
    var packageCode by remember { mutableStateOf("") }
    var packageLabel by remember { mutableStateOf("") }
    var quantity by remember { mutableStateOf("1") }
    var netWeight by remember { mutableStateOf("8.0") }
    var publishStatus by remember { mutableStateOf("draft") }

    fun resetPackageForm() {
        editingPackageId = null
        lotId = "101"
        packageCode = ""
        packageLabel = ""
        quantity = "1"
        netWeight = "8.0"
        publishStatus = "draft"
    }

    val pullRefreshState = rememberPullRefreshState(
        refreshing = uiState.isLoading,
        onRefresh = viewModel::loadPackages
    )

    Scaffold(topBar = { TopAppBar(title = { Text("Đóng gói và QR") }) }) { paddingValues ->
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
                        title = "Không gian đóng gói",
                        subtitle = "Tạo, cập nhật và tái cấp QR cho kiện hàng để truy xuất theo từng đóng gói."
                    ) {
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            StatusBadge(text = "Theo lô", tone = StatusTone.NEUTRAL)
                            StatusBadge(text = "Token riêng", tone = StatusTone.POSITIVE)
                            StatusBadge(text = "Đủ thao tác", tone = StatusTone.POSITIVE)
                        }
                    }
                }

                item {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        KpiCard(
                            title = "Tổng kiện",
                            value = uiState.packages.size.toString(),
                            caption = "Số kiện đã đồng bộ từ backend",
                            modifier = Modifier.weight(1f)
                        )
                        KpiCard(
                            title = "Đã công khai",
                            value = publishedPackages.toString(),
                            caption = "Sẵn sàng quét QR công khai",
                            modifier = Modifier.weight(1f)
                        )
                    }
                }

                item {
                    DashboardSectionCard(
                        title = if (editingPackageId == null) "Tạo kiện mới" else "Cập nhật kiện #$editingPackageId",
                        subtitle = "Mỗi kiện có thể đổi trạng thái công khai và tạo lại QR riêng."
                    ) {
                        AppTextField(
                            value = lotId,
                            onValueChange = { lotId = it.filter(Char::isDigit) },
                            label = "ID lô"
                        )
                        AppTextField(
                            value = packageCode,
                            onValueChange = { packageCode = it },
                            label = "Mã kiện"
                        )
                        AppTextField(
                            value = packageLabel,
                            onValueChange = { packageLabel = it },
                            label = "Nhãn hiển thị"
                        )
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            AppTextField(
                                value = quantity,
                                onValueChange = { quantity = it.filter(Char::isDigit) },
                                label = "Số lượng",
                                modifier = Modifier.weight(1f)
                            )
                            AppTextField(
                                value = netWeight,
                                onValueChange = { netWeight = it.filter { ch -> ch.isDigit() || ch == '.' } },
                                label = "Khối lượng (kg)",
                                modifier = Modifier.weight(1f)
                            )
                        }
                        AppTextField(
                            value = publishStatus,
                            onValueChange = { publishStatus = it },
                            label = "Trạng thái công khai"
                        )
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            OutlinedButton(
                                onClick = if (editingPackageId == null) onNavigateBack else ::resetPackageForm,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text(if (editingPackageId == null) "Về lô hàng" else "Hủy sửa")
                            }
                            Button(
                                onClick = {
                                    if (editingPackageId == null) {
                                        viewModel.createPackage(
                                            CreatePackageRequest(
                                                lotId = lotId.toIntOrNull() ?: 0,
                                                packageCode = packageCode.trim(),
                                                packageLabel = packageLabel.trim(),
                                                quantity = quantity.toIntOrNull() ?: 0,
                                                netWeightKg = netWeight.trim()
                                            )
                                        )
                                    } else {
                                        viewModel.updatePackage(
                                            packageId = editingPackageId ?: return@Button,
                                            request = UpdatePackageRequest(
                                                packageCode = packageCode.trim(),
                                                packageLabel = packageLabel.trim(),
                                                quantity = quantity.toIntOrNull() ?: 0,
                                                netWeightKg = netWeight.trim(),
                                                publishStatus = publishStatus.trim()
                                            )
                                        )
                                    }
                                },
                                enabled = lotId.isNotBlank() && packageCode.isNotBlank(),
                                modifier = Modifier.weight(1f)
                            ) {
                                Text(if (editingPackageId == null) "Lưu kiện" else "Cập nhật kiện")
                            }
                        }
                    }
                }

                item {
                    DashboardSectionCard(
                        title = "Kiện gần đây",
                        subtitle = "Tại đây có thể nạp kiện vào form, tạo lại QR hoặc xóa."
                    ) {
                        if (uiState.packages.isEmpty() && !uiState.isLoading) {
                            Text(
                                text = "Chưa có kiện hàng nào được đồng bộ.",
                                style = androidx.compose.material3.MaterialTheme.typography.bodyMedium,
                                color = androidx.compose.material3.MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        } else {
                            uiState.packages.take(8).forEach { pkg ->
                                PackageManagementCard(
                                    pkg = pkg,
                                    onEdit = {
                                        editingPackageId = pkg.id
                                        lotId = pkg.lotId.toString()
                                        packageCode = pkg.packageCode
                                        packageLabel = pkg.packageLabel.orEmpty()
                                        quantity = pkg.quantity.toString()
                                        netWeight = pkg.netWeightKg.orEmpty()
                                        publishStatus = pkg.publishStatus.orEmpty().ifBlank { "draft" }
                                    },
                                    onRegenerateQr = { viewModel.regeneratePackageQr(pkg.id) },
                                    onDelete = { viewModel.deletePackage(pkg.id) }
                                )
                            }
                        }
                    }
                }

                item {
                    OutlinedButton(
                        onClick = onNavigateScan,
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text("Mở màn quét QR")
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
private fun PackageManagementCard(
    pkg: PartnerPackage,
    onEdit: () -> Unit,
    onRegenerateQr: () -> Unit,
    onDelete: () -> Unit
) {
    DashboardSectionCard(
        title = pkg.packageCode,
        subtitle = pkg.packageLabel ?: "Kiện hàng truy xuất"
    ) {
        QrPreviewCard(
            packageCode = pkg.packageCode,
            packageLabel = pkg.packageLabel,
            qrToken = pkg.qrToken,
            publishStatus = pkg.publishStatus
        )
        Text(
            text = listOfNotNull(
                pkg.lotCode?.takeIf { it.isNotBlank() }?.let { "Lô: $it" },
                pkg.netWeightKg?.takeIf { it.isNotBlank() }?.let { "Kg: $it" }
            ).joinToString(" • "),
            style = androidx.compose.material3.MaterialTheme.typography.bodySmall,
            color = androidx.compose.material3.MaterialTheme.colorScheme.onSurfaceVariant
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

