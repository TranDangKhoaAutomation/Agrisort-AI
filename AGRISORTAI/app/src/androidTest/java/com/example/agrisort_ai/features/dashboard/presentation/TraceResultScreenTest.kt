package com.example.agrisort_ai.features.dashboard.presentation

import androidx.compose.ui.test.assertIsDisplayed
import androidx.compose.ui.test.junit4.createComposeRule
import androidx.compose.ui.test.onNodeWithText
import androidx.test.ext.junit.runners.AndroidJUnit4
import com.example.agrisort_ai.features.dashboard.domain.model.TraceAttachment
import com.example.agrisort_ai.features.dashboard.domain.model.TraceEvent
import com.example.agrisort_ai.features.dashboard.domain.model.TraceLookupResult
import com.example.agrisort_ai.ui.theme.AgriSortAITheme
import org.junit.Rule
import org.junit.Test
import org.junit.runner.RunWith

@RunWith(AndroidJUnit4::class)
class TraceResultScreenTest {

    @get:Rule
    val composeRule = createComposeRule()

    @Test
    fun traceResult_showsSuccessBlocks() {
        composeRule.setContent {
            AgriSortAITheme {
                TraceResultStateContent(
                    token = "TRACE-001",
                    uiState = TraceUiState(
                        currentResult = TraceLookupResult(
                            token = "TRACE-001",
                            entityType = "package",
                            lotCode = "LOT-BG-001",
                            packageCode = "PKG-BG-001",
                            produceType = "Vải thiều",
                            originRegion = "Bắc Giang",
                            harvestDate = "2026-03-06",
                            grade1Count = 120,
                            grade2Count = 15,
                            defectCount = 3,
                            notes = "Lô đã qua sơ tuyển.",
                            publishStatus = "published",
                            events = listOf(
                                TraceEvent(
                                    id = 1,
                                    stageCode = "harvest",
                                    eventTime = "2026-03-06 07:30",
                                    locationName = "Lục Ngạn",
                                    actorName = "HTX Bắc Giang",
                                    note = "Thu hoạch buổi sáng"
                                )
                            ),
                            attachments = listOf(
                                TraceAttachment(
                                    id = 1,
                                    fileName = "phieu-thu-hoach.pdf",
                                    fileUrl = "https://example.com/phieu-thu-hoach.pdf"
                                )
                            )
                        ),
                        dataSourceState = DataSourceState.LIVE
                    ),
                    onRetry = {},
                    onNavigateBack = {},
                    onNavigateHistory = {}
                )
            }
        }

        composeRule.onNodeWithText("Thông tin truy xuất").assertIsDisplayed()
        composeRule.onNodeWithText("Vùng trồng").assertIsDisplayed()
        composeRule.onNodeWithText("Chất lượng theo cấp").assertIsDisplayed()
        composeRule.onNodeWithText("Hành trình").assertIsDisplayed()
        composeRule.onNodeWithText("Tệp đính kèm").assertIsDisplayed()
    }

    @Test
    fun traceResult_showsEmptyStateWhenNoData() {
        composeRule.setContent {
            AgriSortAITheme {
                TraceResultStateContent(
                    token = "TRACE-EMPTY",
                    uiState = TraceUiState(),
                    onRetry = {},
                    onNavigateBack = {},
                    onNavigateHistory = {}
                )
            }
        }

        composeRule.onNodeWithText("Không có dữ liệu truy xuất").assertIsDisplayed()
        composeRule.onNodeWithText("Thử lại").assertIsDisplayed()
    }

    @Test
    fun traceResult_showsErrorStateMessage() {
        composeRule.setContent {
            AgriSortAITheme {
                TraceResultStateContent(
                    token = "TRACE-ERROR",
                    uiState = TraceUiState(
                        error = "Không thể tải dữ liệu truy xuất.",
                        dataSourceState = DataSourceState.ERROR
                    ),
                    onRetry = {},
                    onNavigateBack = {},
                    onNavigateHistory = {}
                )
            }
        }

        composeRule.onNodeWithText("Không thể tải dữ liệu truy xuất.").assertIsDisplayed()
        composeRule.onNodeWithText("Thử lại").assertIsDisplayed()
    }
}
