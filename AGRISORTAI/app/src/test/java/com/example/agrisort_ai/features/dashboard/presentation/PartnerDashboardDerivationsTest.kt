package com.example.agrisort_ai.features.dashboard.presentation

import com.example.agrisort_ai.features.dashboard.domain.model.PartnerLot
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerPackage
import org.junit.Assert.assertEquals
import org.junit.Test

class PartnerDashboardDerivationsTest {

    @Test
    fun deriveMetricsUsesLotQualityCountsWhenAvailable() {
        val lots = listOf(
            PartnerLot(
                id = 1,
                lotCode = "LO-01",
                produceType = "Vải",
                grade1Count = 100,
                grade2Count = 20,
                defectCount = 5,
                publishStatus = "published",
                qrToken = "trace-1"
            ),
            PartnerLot(
                id = 2,
                lotCode = "LO-02",
                produceType = "Nhãn",
                grade1Count = 80,
                grade2Count = 10,
                defectCount = 10,
                qrToken = "trace-2"
            )
        )

        val result = derivePartnerDashboardMetrics(lots, emptyList())

        assertEquals(2, result.activeLots)
        assertEquals(2, result.readyQrLots)
        assertEquals(225, result.totalProduce)
        assertEquals(6, result.defectRatePercent)
    }

    @Test
    fun deriveMetricsFallsBackToPackagesWhenLotCountsAreMissing() {
        val lots = listOf(
            PartnerLot(id = 1, lotCode = "LO-01", produceType = "Vải"),
            PartnerLot(id = 2, lotCode = "LO-02", produceType = "Nhãn")
        )
        val packages = listOf(
            PartnerPackage(id = 10, lotId = 1, packageCode = "PK-01", quantity = 12),
            PartnerPackage(id = 11, lotId = 2, packageCode = "PK-02", quantity = 8)
        )

        val result = derivePartnerDashboardMetrics(lots, packages)

        assertEquals(20, result.totalProduce)
        assertEquals(0, result.defectRatePercent)
    }

    @Test
    fun workflowStatusMapsPublishedReadyAndDraftCorrectly() {
        val published = PartnerLot(
            id = 1,
            lotCode = "LO-01",
            produceType = "Vải",
            publishStatus = "published"
        )
        val readyForQr = PartnerLot(
            id = 2,
            lotCode = "LO-02",
            produceType = "Nhãn",
            qrToken = "trace-2"
        )
        val draft = PartnerLot(
            id = 3,
            lotCode = "LO-03",
            produceType = "Xoài"
        )

        assertEquals(LotWorkflowStatus.PUBLISHED, published.workflowStatus())
        assertEquals(LotWorkflowStatus.READY_FOR_QR, readyForQr.workflowStatus())
        assertEquals(LotWorkflowStatus.DRAFT, draft.workflowStatus())
    }
}
