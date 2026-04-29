package com.example.agrisort_ai.features.auth.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.agrisort_ai.R
import com.example.agrisort_ai.ui.components.AppCard
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.AppPasswordField
import com.example.agrisort_ai.ui.components.AppTextField
import com.example.agrisort_ai.ui.components.AuthGradientBackground
import com.example.agrisort_ai.ui.components.MessageType
import com.example.agrisort_ai.ui.components.ScreenHeader

@Composable
fun RegisterScreen(
    onRegisterSuccess: () -> Unit,
    onNavigateToLogin: () -> Unit,
    viewModel: AuthViewModel = hiltViewModel()
) {
    var fullName by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var organizationName by remember { mutableStateOf("") }
    var representativeName by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }
    var address by remember { mutableStateOf("") }
    var region by remember { mutableStateOf("") }
    var taxCode by remember { mutableStateOf("") }
    var submitted by remember { mutableStateOf(false) }

    val uiState by viewModel.uiState.collectAsState()

    LaunchedEffect(Unit) {
        viewModel.clearError()
    }

    LaunchedEffect(uiState, submitted) {
        if (submitted && uiState is AuthUiState.Success) {
            onRegisterSuccess()
        }
    }

    AuthGradientBackground(modifier = Modifier.fillMaxSize()) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 16.dp)
                .verticalScroll(rememberScrollState()),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(modifier = Modifier.height(24.dp))
            AppCard(modifier = Modifier.fillMaxWidth()) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    ScreenHeader(
                        title = stringResource(R.string.register_title),
                        subtitle = stringResource(R.string.register_subtitle_default_buyer),
                        showBrandMark = true
                    )

                    AppTextField(
                        value = fullName,
                        onValueChange = {
                            fullName = it
                            if (uiState is AuthUiState.Error) viewModel.clearError()
                        },
                        label = stringResource(R.string.register_label_full_name)
                    )
                    AppTextField(
                        value = email,
                        onValueChange = {
                            email = it
                            if (uiState is AuthUiState.Error) viewModel.clearError()
                        },
                        label = stringResource(R.string.register_label_email)
                    )
                    AppPasswordField(
                        value = password,
                        onValueChange = {
                            password = it
                            if (uiState is AuthUiState.Error) viewModel.clearError()
                        },
                        label = stringResource(R.string.register_label_password),
                        supportingText = stringResource(R.string.register_password_hint)
                    )

                    AppTextField(
                        value = organizationName,
                        onValueChange = { organizationName = it },
                        label = stringResource(R.string.register_label_org_name),
                        supportingText = stringResource(R.string.register_org_hint_buyer)
                    )
                    AppTextField(
                        value = representativeName,
                        onValueChange = { representativeName = it },
                        label = stringResource(R.string.register_label_representative)
                    )
                    AppTextField(
                        value = phone,
                        onValueChange = { phone = it },
                        label = stringResource(R.string.register_label_phone)
                    )
                    AppTextField(
                        value = address,
                        onValueChange = { address = it },
                        label = stringResource(R.string.register_label_address)
                    )
                    AppTextField(
                        value = region,
                        onValueChange = { region = it },
                        label = stringResource(R.string.register_label_region)
                    )
                    AppTextField(
                        value = taxCode,
                        onValueChange = { taxCode = it },
                        label = stringResource(R.string.register_label_tax_code)
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
                            viewModel.register(
                                fullName = fullName,
                                email = email,
                                password = password,
                                organizationName = organizationName,
                                representativeName = representativeName,
                                phone = phone,
                                address = address,
                                region = region,
                                taxCode = taxCode
                            )
                        },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = uiState !is AuthUiState.Loading
                    ) {
                        if (uiState is AuthUiState.Loading) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(20.dp),
                                strokeWidth = 2.dp
                            )
                        } else {
                            Text(stringResource(R.string.register_button))
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(12.dp))
            TextButton(onClick = onNavigateToLogin) {
                Text(stringResource(R.string.register_login_cta))
            }
            Spacer(modifier = Modifier.height(16.dp))
        }
    }
}
