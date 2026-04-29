package com.example.agrisort_ai.features.auth.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Logout
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
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

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProfileScreen(
    onNavigateBack: () -> Unit,
    viewModel: AuthViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val currentUser by viewModel.currentUser.collectAsState()

    var initializedUserId by remember { mutableStateOf<Int?>(null) }
    var fullName by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var organizationName by remember { mutableStateOf("") }
    var representativeName by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }
    var address by remember { mutableStateOf("") }
    var region by remember { mutableStateOf("") }
    var taxCode by remember { mutableStateOf("") }
    var currentPassword by remember { mutableStateOf("") }
    var newPassword by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }

    LaunchedEffect(Unit) {
        viewModel.getProfile()
    }

    LaunchedEffect(currentUser?.id) {
        val user = currentUser ?: return@LaunchedEffect
        if (initializedUserId == user.id) return@LaunchedEffect
        initializedUserId = user.id
        fullName = user.fullName
        email = user.email
        organizationName = user.partnerProfile?.organizationName.orEmpty()
        representativeName = user.partnerProfile?.representativeName.orEmpty()
        phone = user.partnerProfile?.phone.orEmpty()
        address = user.partnerProfile?.address.orEmpty()
        region = user.partnerProfile?.region.orEmpty()
        taxCode = user.partnerProfile?.taxCode.orEmpty()
    }

    val role = currentUser?.role.orEmpty()
    val roleLabel = currentUser?.roleLabel?.ifBlank { role }.orEmpty()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Hồ sơ tài khoản") },
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
                            .padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Text(
                            text = "Thông tin tài khoản",
                            style = MaterialTheme.typography.titleMedium
                        )
                        Text(
                            text = "Vai trò: ${if (roleLabel.isBlank()) "Người dùng" else roleLabel}",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )

                        AppTextField(
                            value = fullName,
                            onValueChange = { fullName = it },
                            label = "Họ và tên"
                        )
                        AppTextField(
                            value = email,
                            onValueChange = { email = it },
                            label = "Email"
                        )
                        AppTextField(
                            value = organizationName,
                            onValueChange = { organizationName = it },
                            label = "Tên tổ chức / HTX"
                        )
                        AppTextField(
                            value = representativeName,
                            onValueChange = { representativeName = it },
                            label = "Người đại diện"
                        )
                        AppTextField(
                            value = phone,
                            onValueChange = { phone = it },
                            label = "Số điện thoại"
                        )
                        AppTextField(
                            value = address,
                            onValueChange = { address = it },
                            label = "Địa chỉ"
                        )
                        AppTextField(
                            value = region,
                            onValueChange = { region = it },
                            label = "Khu vực"
                        )
                        AppTextField(
                            value = taxCode,
                            onValueChange = { taxCode = it },
                            label = "Mã số thuế"
                        )

                        Button(
                            onClick = {
                                viewModel.updateProfile(
                                    fullName = fullName,
                                    email = email,
                                    role = role,
                                    organizationName = organizationName,
                                    representativeName = representativeName,
                                    phone = phone,
                                    address = address,
                                    region = region,
                                    taxCode = taxCode
                                )
                            },
                            modifier = Modifier.fillMaxWidth(),
                            enabled = uiState !is AuthUiState.Loading && currentUser != null
                        ) {
                            if (uiState is AuthUiState.Loading) {
                                CircularProgressIndicator(strokeWidth = 2.dp)
                            } else {
                                Text("Lưu hồ sơ")
                            }
                        }
                    }
                }
            }

            item {
                AppCard(modifier = Modifier.fillMaxWidth()) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Text(
                            text = "Đổi mật khẩu",
                            style = MaterialTheme.typography.titleMedium
                        )
                        AppPasswordField(
                            value = currentPassword,
                            onValueChange = { currentPassword = it },
                            label = "Mật khẩu hiện tại"
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
                            label = "Xác nhận mật khẩu mới"
                        )
                        OutlinedButton(
                            onClick = {
                                viewModel.updatePassword(
                                    currentPassword = currentPassword,
                                    newPassword = newPassword,
                                    confirmPassword = confirmPassword
                                )
                            },
                            modifier = Modifier.fillMaxWidth(),
                            enabled = uiState !is AuthUiState.Loading
                        ) {
                            Text("Cập nhật mật khẩu")
                        }
                    }
                }
            }

            if (uiState is AuthUiState.Error) {
                item {
                    AppMessage(
                        message = (uiState as AuthUiState.Error).message,
                        type = MessageType.ERROR
                    )
                }
            }

            if (uiState is AuthUiState.Success) {
                val message = (uiState as AuthUiState.Success).message
                if (!message.isNullOrBlank()) {
                    item {
                        AppMessage(
                            message = message,
                            type = MessageType.SUCCESS
                        )
                    }
                }
            }

            item {
                OutlinedButton(
                    onClick = { viewModel.logout() },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Icon(
                        imageVector = Icons.Default.Logout,
                        contentDescription = null
                    )
                    Text(" Đăng xuất")
                }
            }

            item {
                Spacer(modifier = Modifier.height(10.dp))
            }
        }
    }
}
