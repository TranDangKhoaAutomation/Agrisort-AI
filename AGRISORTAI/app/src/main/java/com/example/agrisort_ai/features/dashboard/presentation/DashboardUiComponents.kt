package com.example.agrisort_ai.features.dashboard.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.AssistChip
import androidx.compose.material3.AssistChipDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.example.agrisort_ai.ui.components.AppCard
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.MessageType

enum class StatusTone {
    POSITIVE,
    WARNING,
    NEUTRAL,
    DANGER
}

@Composable
fun DashboardSectionCard(
    title: String,
    subtitle: String? = null,
    modifier: Modifier = Modifier,
    content: @Composable ColumnScope.() -> Unit
) {
    AppCard(modifier = modifier.fillMaxWidth()) {
        Column(
            modifier = Modifier.padding(18.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(
                text = title,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold
            )
            subtitle?.takeIf { it.isNotBlank() }?.let {
                Text(
                    text = it,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            content()
        }
    }
}

@Composable
fun KpiCard(
    title: String,
    value: String,
    caption: String,
    modifier: Modifier = Modifier
) {
    AppCard(modifier = modifier) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Text(
                text = value,
                style = MaterialTheme.typography.headlineSmall,
                fontWeight = FontWeight.Bold
            )
            Text(
                text = title,
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.Medium
            )
            Text(
                text = caption,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
fun StatusBadge(
    text: String,
    tone: StatusTone,
    modifier: Modifier = Modifier
) {
    val containerColor: Color
    val labelColor: Color
    when (tone) {
        StatusTone.POSITIVE -> {
            containerColor = Color(0xFFE1F4E8)
            labelColor = Color(0xFF157347)
        }

        StatusTone.WARNING -> {
            containerColor = Color(0xFFFFF1DA)
            labelColor = Color(0xFFB9670F)
        }

        StatusTone.DANGER -> {
            containerColor = Color(0xFFFDE7E4)
            labelColor = Color(0xFFB42318)
        }

        StatusTone.NEUTRAL -> {
            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.7f)
            labelColor = MaterialTheme.colorScheme.onSurfaceVariant
        }
    }

    AssistChip(
        onClick = {},
        label = { Text(text = text) },
        modifier = modifier,
        colors = AssistChipDefaults.assistChipColors(
            containerColor = containerColor,
            labelColor = labelColor
        )
    )
}

@Composable
fun DataSourceBanner(
    state: DataSourceState,
    errorMessage: String?,
    modifier: Modifier = Modifier
) {
    when (state) {
        DataSourceState.LIVE -> Unit
        DataSourceState.STALE -> AppMessage(
            message = errorMessage
                ?: "Không thể đồng bộ dữ liệu mới nhất. Đang hiển thị dữ liệu gần nhất.",
            type = MessageType.INFO,
            modifier = modifier
        )

        DataSourceState.ERROR -> errorMessage?.takeIf { it.isNotBlank() }?.let {
            AppMessage(
                message = it,
                type = MessageType.ERROR,
                modifier = modifier
            )
        }
    }
}

@Composable
fun QualityBreakdownCard(
    title: String,
    subtitle: String,
    grade1Count: Int,
    grade2Count: Int,
    defectCount: Int,
    modifier: Modifier = Modifier
) {
    val total = (grade1Count + grade2Count + defectCount).coerceAtLeast(1)
    val grade1Weight = grade1Count.toFloat() / total.toFloat()
    val grade2Weight = grade2Count.toFloat() / total.toFloat()
    val defectWeight = defectCount.toFloat() / total.toFloat()

    DashboardSectionCard(
        title = title,
        subtitle = subtitle,
        modifier = modifier
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .height(14.dp)
                .background(
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.45f),
                    shape = RoundedCornerShape(999.dp)
                )
        ) {
            if (grade1Count > 0) {
                Box(
                    modifier = Modifier
                        .weight(grade1Weight)
                        .height(14.dp)
                        .background(Color(0xFF2E9E65), RoundedCornerShape(999.dp))
                )
            }
            if (grade2Count > 0) {
                Box(
                    modifier = Modifier
                        .weight(grade2Weight)
                        .height(14.dp)
                        .background(Color(0xFFE7A52B))
                )
            }
            if (defectCount > 0) {
                Box(
                    modifier = Modifier
                        .weight(defectWeight)
                        .height(14.dp)
                        .background(Color(0xFFD04A3A), RoundedCornerShape(999.dp))
                )
            }
        }

        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            QualityLegend("Loại 1", grade1Count, Color(0xFF2E9E65), Modifier.weight(1f))
            QualityLegend("Loại 2", grade2Count, Color(0xFFE7A52B), Modifier.weight(1f))
            QualityLegend("Lỗi", defectCount, Color(0xFFD04A3A), Modifier.weight(1f))
        }
    }
}

@Composable
private fun QualityLegend(
    label: String,
    value: Int,
    color: Color,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier,
        horizontalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Box(
            modifier = Modifier
                .width(10.dp)
                .height(10.dp)
                .background(color = color, shape = RoundedCornerShape(99.dp))
        )
        Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
            Text(text = label, style = MaterialTheme.typography.labelMedium)
            Text(
                text = value.toString(),
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
fun QrPreviewCard(
    packageCode: String,
    packageLabel: String?,
    qrToken: String?,
    publishStatus: String?,
    modifier: Modifier = Modifier
) {
    DashboardSectionCard(
        title = packageCode,
        subtitle = packageLabel ?: "Kiện hàng truy xuất",
        modifier = modifier
    ) {
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            StatusBadge(
                text = formatPublishStatus(publishStatus),
                tone = when (publishStatus?.lowercase()) {
                    "published" -> StatusTone.POSITIVE
                    "draft" -> StatusTone.WARNING
                    else -> StatusTone.NEUTRAL
                }
            )
            StatusBadge(
                text = if (qrToken.isNullOrBlank()) "Chưa có QR" else "Đã cấp QR",
                tone = if (qrToken.isNullOrBlank()) StatusTone.NEUTRAL else StatusTone.POSITIVE
            )
        }
        Text(
            text = "Token: ${qrToken ?: "—"}",
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
    }
}

@Composable
fun TimelineEntryCard(
    title: String,
    subtitle: String,
    description: String,
    modifier: Modifier = Modifier
) {
    AppCard(modifier = modifier.fillMaxWidth()) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            Text(
                text = title,
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.SemiBold
            )
            Text(
                text = subtitle,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            if (description.isNotBlank()) {
                Spacer(modifier = Modifier.height(2.dp))
                Text(
                    text = description,
                    style = MaterialTheme.typography.bodyMedium
                )
            }
        }
    }
}
