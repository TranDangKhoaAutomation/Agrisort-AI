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
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.agrisort_ai.ui.components.AppCard
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.AppTextField
import com.example.agrisort_ai.ui.components.MessageType
import com.example.agrisort_ai.ui.components.ScreenHeader

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ForgotPasswordScreen(
    onNavigateBack: () -> Unit,
    onNavigateToResetPassword: () -> Unit,
    viewModel: AuthViewModel = hiltViewModel()
) {
    var email by remember { mutableStateOf("") }
    val uiState by viewModel.uiState.collectAsState()

    LaunchedEffect(Unit) {
        viewModel.clearError()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Quên mật khẩu") },
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
                        title = "Khôi phục mật khẩu",
                        subtitle = "Nhập email đã đăng ký để nhận hướng dẫn và token reset.",
                        showBrandMark = true
                    )

                    AppTextField(
                        value = email,
                        onValueChange = { email = it },
                        label = "Email đăng ký"
                    )

                    if (uiState is AuthUiState.Error) {
                        AppMessage(
                            message = (uiState as AuthUiState.Error).message,
                            type = MessageType.ERROR
                        )
                    }
                    if (uiState is AuthUiState.Success) {
                        val msg = (uiState as AuthUiState.Success).message
                        if (!msg.isNullOrBlank()) {
                            AppMessage(message = msg, type = MessageType.SUCCESS)
                        }
                    }

                    Button(
                        onClick = { viewModel.forgotPassword(email) },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = uiState !is AuthUiState.Loading
                    ) {
                        if (uiState is AuthUiState.Loading) {
                            CircularProgressIndicator(strokeWidth = 2.dp)
                        } else {
                            Text("Gửi yêu cầu")
                        }
                    }

                    TextButton(
                        onClick = onNavigateToResetPassword,
                        modifier = Modifier.align(Alignment.End)
                    ) {
                        Text("Tôi đã có token reset")
                    }
                }
            }
            Spacer(modifier = Modifier.height(12.dp))
        }
    }
}
