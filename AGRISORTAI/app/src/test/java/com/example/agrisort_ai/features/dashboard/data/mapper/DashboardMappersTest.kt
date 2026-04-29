package com.example.agrisort_ai.features.dashboard.data.mapper

import kotlinx.serialization.json.Json
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Test

class DashboardMappersTest {

    private val json = Json { ignoreUnknownKeys = true }

    @Test
    fun adminDashboardSummaryDerivesPendingPartnersFromArray() {
        val payload = json.parseToJsonElement(
            """
            {
              "data": {
                "counts": {
                  "active_users": 6,
                  "total_lots": 4,
                  "published_lots": 3
                },
                "pending_partners": [
                  { "id": 1 },
                  { "id": 2 }
                ]
              }
            }
            """.trimIndent()
        )

        val result = payload.toAdminDashboardSummary()

        assertEquals(2, result.pendingPartners)
        assertEquals(6, result.activeUsers)
        assertEquals(4, result.totalLots)
        assertEquals(3, result.publishedLots)
    }

    @Test
    fun partnerLotsParsesOptionalQualityFields() {
        val payload = json.parseToJsonElement(
            """
            {
              "data": [
                {
                  "id": 1,
                  "lot_code": "LO-BG-001",
                  "produce_type": "Vải thiều",
                  "origin_region": "Bắc Giang",
                  "harvest_date": "2026-03-01",
                  "grade1_count": 120,
                  "grade2_count": 36,
                  "defect_count": 8,
                  "notes": "Lô đầu vụ",
                  "publish_status": "published",
                  "qr_token": "trace-bg-001"
                }
              ]
            }
            """.trimIndent()
        )

        val result = payload.toPartnerLots().single()

        assertEquals("Bắc Giang", result.originRegion)
        assertEquals(120, result.grade1Count)
        assertEquals(36, result.grade2Count)
        assertEquals(8, result.defectCount)
        assertEquals("Lô đầu vụ", result.notes)
        assertEquals("trace-bg-001", result.qrToken)
    }

    @Test
    fun traceLookupResultParsesNestedOptionalFields() {
        val payload = json.parseToJsonElement(
            """
            {
              "data": {
                "token": "trace-bg-001",
                "entity_type": "lot",
                "publish_status": "published",
                "lot": {
                  "lot_code": "LO-BG-001",
                  "produce_type": "Vải thiều",
                  "origin_region": "Bắc Giang",
                  "harvest_date": "2026-03-01",
                  "grade1_count": 120,
                  "grade2_count": 36,
                  "defect_count": 8,
                  "notes": "Lô đầu vụ"
                },
                "events": [
                  {
                    "id": 11,
                    "stage_code": "warehouse-checkin",
                    "event_time": "2026-03-01 09:30:00",
                    "location_name": "Kho Bắc Giang"
                  }
                ]
              }
            }
            """.trimIndent()
        )

        val result = payload.toTraceLookupResult("trace-bg-001")

        assertEquals("Bắc Giang", result.originRegion)
        assertEquals("2026-03-01", result.harvestDate)
        assertEquals(120, result.grade1Count)
        assertEquals(36, result.grade2Count)
        assertEquals(8, result.defectCount)
        assertEquals("Lô đầu vụ", result.notes)
        assertNotNull(result.events.single())
    }
}
