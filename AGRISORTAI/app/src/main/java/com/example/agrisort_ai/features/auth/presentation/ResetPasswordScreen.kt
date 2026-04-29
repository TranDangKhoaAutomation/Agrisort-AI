package com.example.agrisort_ai.features.auth.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.agrisort_ai.ui.components.AppCard
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.AppPasswordField
import com.example.agrisort_ai.ui.components.AppTextField
import com.example.agrisort_ai.ui.components.MessageType
import com.example.agrisort_ai.ui.components.ScreenHeader

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ResetPasswordScreen(
    onNavigateBack: () -> Unit,
    onResetPasswordSuccess: () -> Unit,
    viewModel: AuthViewModel = hiltViewModel()
) {
    var token by remember { mutableStateOf("") }
    var newPassword by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }
    var submitted by remember { mutableStateOf(false) }
    val uiState by viewModel.uiState.collectAsState()

    LaunchedEffect(Unit) {
        viewModel.clearError()
    }

    LaunchedEffect(uiState, submitted) {
        if (submitted && uiState is AuthUiState.Success) {
            onResetPasswordSuccess()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Đặt lại mật khẩu") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            imageVector = Icons.Default.ArrowBack,
                            contentDescription = "Quay lại"
                        )
                    }
                }
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(16.dp),
            verticalArrangement = Arrangement.Center
        ) {
            AppCard(modifier = Modifier.fillMaxWidth()) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    ScreenHeader(
                        title = "Nhập token xác thực",
                        subtitle = "Token được gửi qua email sau khi bạn gửi yêu cầu quên mật khẩu.",
                        showBrandMark = true
                    )

                    AppTextField(
                        value = token,
                        onValueChange = { token = it },
                        label = "Token reset"
                    )
                    AppPasswordField(
                        value = newPassword,
                        onValueChange = { newPassword = it },
                        label = "Mật khẩu mới",
                        supportingText = "Tối thiểu 8 ký tự"
                    )
                    AppPasswordField(
                        value = confirmPassword,
                        onValueChange = { confirmPassword = it },
                        label = "Xác nhận mật khẩu"
                    )

                    if (uiState is AuthUiState.Error) {
                        AppMessage(
                            message = (uiState as AuthUiState.Error).message,
                            type = MessageType.ERROR
                        )
                    }

                    Button(
                        onClick = {
                            submitted = true
                            viewModel.resetPassword(
                                token = token,
                                newPassword = newPassword,
                                confirmPassword = confirmPassword
                            )
                        },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = uiState !is AuthUiState.Loading
                    ) {
                        if (uiState is AuthUiState.Loading) {
                            CircularProgressIndicator(strokeWidth = 2.dp)
                        } else {
                            Text("Xác nhận đổi mật khẩu")
                        }
                    }
                }
            }
            Spacer(modifier = Modifier.height(12.dp))
        }
    }
}
