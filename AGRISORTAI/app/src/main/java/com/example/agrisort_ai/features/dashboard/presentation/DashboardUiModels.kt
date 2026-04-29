package com.example.agrisort_ai.features.dashboard.presentation

import com.example.agrisort_ai.features.dashboard.domain.model.PartnerLot
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerPackage

enum class DataSourceState {
    LIVE,
    STALE,
    ERROR
}

enum class LotWorkflowStatus {
    DRAFT,
    READY_FOR_QR,
    PUBLISHED
}

data class PartnerDashboardMetrics(
    val activeLots: Int,
    val readyQrLots: Int,
    val totalProduce: Int,
    val defectRatePercent: Int
)

fun PartnerLot.workflowStatus(): LotWorkflowStatus {
    return when {
        publishStatus.equals("published", ignoreCase = true) -> LotWorkflowStatus.PUBLISHED
        !qrToken.isNullOrBlank() || totalQualityCount() > 0 -> LotWorkflowStatus.READY_FOR_QR
        else -> LotWorkflowStatus.DRAFT
    }
}

fun PartnerLot.totalQualityCount(): Int {
    return listOf(grade1Count, grade2Count, defectCount).sumOf { it ?: 0 }
}

fun derivePartnerDashboardMetrics(
    lots: List<PartnerLot>,
    packages: List<PartnerPackage>
): PartnerDashboardMetrics {
    val readyQrLots = lots.count { it.workflowStatus() != LotWorkflowStatus.DRAFT }
    val totalFromLots = lots.sumOf { it.totalQualityCount() }
    val totalFromPackages = packages.sumOf { it.quantity }
    val totalProduce = if (totalFromLots > 0) totalFromLots else totalFromPackages
    val totalDefects = lots.sumOf { it.defectCount ?: 0 }
    val defectRatePercent = if (totalProduce > 0) {
        ((totalDefects.toFloat() / totalProduce.toFloat()) * 100f).toInt()
    } else {
        0
    }

    return PartnerDashboardMetrics(
        activeLots = lots.size,
        readyQrLots = readyQrLots,
        totalProduce = totalProduce,
        defectRatePercent = defectRatePercent
    )
}

fun formatWorkflowStatus(status: LotWorkflowStatus): String {
    return when (status) {
        LotWorkflowStatus.DRAFT -> "Nháp"
        LotWorkflowStatus.READY_FOR_QR -> "Sẵn sàng QR"
        LotWorkflowStatus.PUBLISHED -> "Đã công khai"
    }
}

fun formatPublishStatus(status: String?): String {
    return when (status?.trim()?.lowercase()) {
        "published" -> "Đã công khai"
        "draft" -> "Nháp"
        "pending" -> "Chờ duyệt"
        "active" -> "Đang hoạt động"
        "inactive", "suspended" -> "Tạm khóa"
        else -> "Chưa rõ"
    }
}

fun formatEntityType(entityType: String): String {
    return when (entityType.trim().lowercase()) {
        "lot" -> "Lô hàng"
        "package" -> "Kiện hàng"
        else -> "Đối tượng"
    }
}

fun formatStageLabel(stageCode: String): String {
    return when (stageCode.trim().lowercase()) {
        "harvest-checkin" -> "Thu hoạch"
        "ai-sorting" -> "Phân loại"
        "warehouse-checkin" -> "Nhập kho"
        "transport-checkin" -> "Vận chuyển"
        "retail-display" -> "Trưng bày"
        else -> stageCode.ifBlank { "Chưa gán stage" }
    }
}

fun formatRoleLabel(role: String): String {
    return when (role.trim().lowercase()) {
        "admin" -> "Quản trị"
        "partner", "farmer" -> "Đối tác / HTX"
        "transporter", "warehouse", "seller", "supply" -> "Vận hành chuỗi"
        "buyer", "customer", "user" -> "Người tra cứu"
        else -> role.ifBlank { "Chưa gán vai trò" }
    }
}
