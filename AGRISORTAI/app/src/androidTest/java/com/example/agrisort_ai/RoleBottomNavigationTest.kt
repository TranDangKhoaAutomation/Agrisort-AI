package com.example.agrisort_ai

import androidx.compose.ui.test.assertIsDisplayed
import androidx.compose.ui.test.junit4.createComposeRule
import androidx.compose.ui.test.onNodeWithText
import androidx.test.ext.junit.runners.AndroidJUnit4
import com.example.agrisort_ai.features.dashboard.domain.model.AppRoleDestination
import com.example.agrisort_ai.navigation.AppRoutes
import com.example.agrisort_ai.ui.theme.AgriSortAITheme
import org.junit.Rule
import org.junit.Test
import org.junit.runner.RunWith

@RunWith(AndroidJUnit4::class)
class RoleBottomNavigationTest {

    @get:Rule
    val composeRule = createComposeRule()

    @Test
    fun adminBottomNav_showsOperationsTabs() {
        composeRule.setContent {
            AgriSortAITheme {
                RoleBottomNavigationContent(
                    roleDestination = AppRoleDestination.ADMIN,
                    currentRoute = AppRoutes.ADMIN_DASHBOARD,
                    onNavigate = {}
                )
            }
        }

        composeRule.onNodeWithText("Điều hành").assertIsDisplayed()
        composeRule.onNodeWithText("Người dùng").assertIsDisplayed()
        composeRule.onNodeWithText("Truy xuất").assertIsDisplayed()
        composeRule.onNodeWithText("Tài khoản").assertIsDisplayed()
    }

    @Test
    fun partnerBottomNav_showsLotAndPackagingTabs() {
        composeRule.setContent {
            AgriSortAITheme {
                RoleBottomNavigationContent(
                    roleDestination = AppRoleDestination.PARTNER,
                    currentRoute = AppRoutes.PARTNER_DASHBOARD,
                    onNavigate = {}
                )
            }
        }

        composeRule.onNodeWithText("Lô hàng").assertIsDisplayed()
        composeRule.onNodeWithText("Đóng gói").assertIsDisplayed()
        composeRule.onNodeWithText("Quét").assertIsDisplayed()
        composeRule.onNodeWithText("Tài khoản").assertIsDisplayed()
    }

    @Test
    fun supplyBottomNav_showsHandoverTabs() {
        composeRule.setContent {
            AgriSortAITheme {
                RoleBottomNavigationContent(
                    roleDestination = AppRoleDestination.SUPPLY,
                    currentRoute = AppRoutes.SUPPLY_DASHBOARD,
                    onNavigate = {}
                )
            }
        }

        composeRule.onNodeWithText("Bàn giao").assertIsDisplayed()
        composeRule.onNodeWithText("Quét").assertIsDisplayed()
        composeRule.onNodeWithText("Tài khoản").assertIsDisplayed()
    }

    @Test
    fun buyerBottomNav_showsScanHistoryTabs() {
        composeRule.setContent {
            AgriSortAITheme {
                RoleBottomNavigationContent(
                    roleDestination = AppRoleDestination.BUYER,
                    currentRoute = AppRoutes.TRACE_SCAN,
                    onNavigate = {}
                )
            }
        }

        composeRule.onNodeWithText("Quét").assertIsDisplayed()
        composeRule.onNodeWithText("Lịch sử").assertIsDisplayed()
        composeRule.onNodeWithText("Tài khoản").assertIsDisplayed()
    }
}
