package com.example.agrisort_ai.features.home.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
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
import com.example.agrisort_ai.features.auth.presentation.AuthUiState
import com.example.agrisort_ai.features.auth.presentation.AuthViewModel
import com.example.agrisort_ai.features.dashboard.presentation.AgriSortShowcaseContent
import com.example.agrisort_ai.features.dashboard.presentation.ShowcaseMetric
import com.example.agrisort_ai.features.dashboard.presentation.ShowcasePoint
import com.example.agrisort_ai.ui.components.AppCard
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.MessageType

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    onNavigateProfile: () -> Unit,
    onNavigateForgotPassword: () -> Unit,
    onLogout: () -> Unit,
    viewModel: AuthViewModel = hiltViewModel()
) {
    val currentUser by viewModel.currentUser.collectAsState()
    val uiState by viewModel.uiState.collectAsState()
    val userName = currentUser?.fullName ?: "Bạn"
    val roleLabel = currentUser?.roleLabel?.ifBlank { currentUser?.role } ?: "Người dùng truy xuất"

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(AgriSortShowcaseContent.projectName) }
            )
        }
    ) { paddingValues ->
        if (uiState is AuthUiState.Loading && currentUser == null) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.Center
            ) {
                CircularProgressIndicator()
                Spacer(modifier = Modifier.height(12.dp))
                Text("Đang tải không gian làm việc của bạn...")
            }
            return@Scaffold
        }

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
                            .padding(18.dp),
                        verticalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        Text(
                            text = "Xin chào, $userName",
                            style = MaterialTheme.typography.headlineSmall,
                            fontWeight = FontWeight.Bold
                        )
                        Text(
                            text = AgriSortShowcaseContent.projectTagline,
                            style = MaterialTheme.typography.titleMedium,
                            color = MaterialTheme.colorScheme.primary
                        )
                        Text(
                            text = AgriSortShowcaseContent.projectSummary,
                            style = MaterialTheme.typography.bodyMedium
                        )
                        Text(
                            text = "Vai trò hiện tại: $roleLabel",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            OutlinedButton(
                                onClick = onNavigateProfile,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text("Mở mục Chung")
                            }
                            OutlinedButton(
                                onClick = onNavigateForgotPassword,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text("Bảo mật")
                            }
                            OutlinedButton(
                                onClick = onLogout,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text("Đăng xuất")
                            }
                        }
                    }
                }
            }

            item {
                GridMetrics(metrics = AgriSortShowcaseContent.homeMetrics)
            }

            if (uiState is AuthUiState.Error) {
                item {
                    AppMessage(
                        message = (uiState as AuthUiState.Error).message,
                        type = MessageType.ERROR
                    )
                }
            }

            item {
                PointsSection(
                    title = "Bài toán thị trường",
                    subtitle = "Các điểm nghẽn thực tế mà AGRISORT-AI đang xử lý trong chuỗi nông sản.",
                    points = AgriSortShowcaseContent.painPoints
                )
            }

            item {
                PointsSection(
                    title = "Giải pháp cốt lõi",
                    subtitle = "Kết hợp máy phân loại, dữ liệu mã lô và QR truy xuất trong cùng một quy trình vận hành.",
                    points = AgriSortShowcaseContent.solutionBlocks
                )
            }

            item {
                PointsSection(
                    title = "Công nghệ triển khai",
                    subtitle = "Chu trình công nghệ bám đúng dây chuyền: Camera -> Xử lý ảnh -> AI -> Quyết định phân loại -> Cơ cấu gạt.",
                    points = AgriSortShowcaseContent.technologyStack
                )
            }

            item {
                PointsSection(
                    title = "Nhóm thụ hưởng",
                    subtitle = "Tập trung vào HTX, cơ sở sơ chế, trạm thu mua và doanh nghiệp nông sản quy mô nhỏ-vừa.",
                    points = AgriSortShowcaseContent.targetSegments
                )
            }

            item {
                PointsSection(
                    title = "Lộ trình phát triển",
                    subtitle = "Ưu tiên pilot theo mùa vụ, hiệu chỉnh dữ liệu thực tế và mở rộng theo cụm nguyên liệu.",
                    points = AgriSortShowcaseContent.roadmap
                )
            }
        }
    }
}

@Composable
private fun GridMetrics(metrics: List<ShowcaseMetric>) {
    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
        metrics.chunked(2).forEach { rowItems ->
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                rowItems.forEach { metric ->
                    AppCard(modifier = Modifier.weight(1f)) {
                        Column(
                            modifier = Modifier.padding(14.dp),
                            verticalArrangement = Arrangement.spacedBy(6.dp)
                        ) {
                            Text(
                                text = metric.value,
                                style = MaterialTheme.typography.headlineSmall,
                                fontWeight = FontWeight.Bold
                            )
                            Text(
                                text = metric.title,
                                style = MaterialTheme.typography.titleSmall
                            )
                            Text(
                                text = metric.caption,
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }
                if (rowItems.size == 1) {
                    Spacer(modifier = Modifier.weight(1f))
                }
            }
        }
    }
}

@Composable
private fun PointsSection(
    title: String,
    subtitle: String,
    points: List<ShowcasePoint>
) {
    AppCard(modifier = Modifier.fillMaxWidth()) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Text(
                text = title,
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.SemiBold
            )
            Text(
                text = subtitle,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            points.forEach { point ->
                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    Text(
                        text = point.title,
                        style = MaterialTheme.typography.titleSmall
                    )
                    Text(
                        text = point.description,
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
        }
    }
}
