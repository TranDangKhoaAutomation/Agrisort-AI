package com.example.agrisort_ai.features.dashboard.presentation

import androidx.compose.ui.test.assertIsDisplayed
import androidx.compose.ui.test.junit4.createComposeRule
import androidx.compose.ui.test.onNodeWithText
import androidx.test.ext.junit.runners.AndroidJUnit4
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerLot
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerPackage
import com.example.agrisort_ai.ui.theme.AgriSortAITheme
import org.junit.Rule
import org.junit.Test
import org.junit.runner.RunWith

@RunWith(AndroidJUnit4::class)
class PartnerDashboardScreenTest {

    @get:Rule
    val composeRule = createComposeRule()

    @Test
    fun partnerDashboard_showsKpisAndThreeStepForm() {
        composeRule.setContent {
            AgriSortAITheme {
                PartnerDashboardContent(
                    uiState = PartnerDashboardUiState(
                        lots = listOf(
                            PartnerLot(
                                id = 1,
                                lotCode = "LOT-BG-001",
                                produceType = "Vải thiều",
                                originRegion = "Bắc Giang",
                                harvestDate = "2026-03-06",
                                grade1Count = 120,
                                grade2Count = 24,
                                defectCount = 6,
                                publishStatus = "draft",
                                qrToken = "TRACE-LOT-001"
                            )
                        ),
                        packages = listOf(
                            PartnerPackage(
                                id = 10,
                                lotId = 1,
                                packageCode = "PKG-001",
                                quantity = 12,
                                publishStatus = "published",
                                qrToken = "TRACE-PKG-001"
                            )
                        ),
                        dataSourceState = DataSourceState.LIVE
                    ),
                    onRefresh = {},
                    onCreateLot = {}
                )
            }
        }

        composeRule.onNodeWithText("Phiên phân loại hôm nay").assertIsDisplayed()
        composeRule.onNodeWithText("Lô đang xử lý").assertIsDisplayed()
        composeRule.onNodeWithText("Lô sẵn sàng QR").assertIsDisplayed()
        composeRule.onNodeWithText("Tổng sản phẩm").assertIsDisplayed()
        composeRule.onNodeWithText("1. Thông tin lô").assertIsDisplayed()
        composeRule.onNodeWithText("2. Kết quả").assertIsDisplayed()
        composeRule.onNodeWithText("3. Xuất QR").assertIsDisplayed()
        composeRule.onNodeWithText("Lô gần đây").assertIsDisplayed()
    }
}
