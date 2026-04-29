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
import androidx.compose.material3.MaterialTheme
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
fun LoginScreen(
    onNavigateToRegister: () -> Unit,
    onNavigateToForgotPassword: () -> Unit,
    onNavigateToBlog: () -> Unit,
    viewModel: AuthViewModel = hiltViewModel()
) {
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    val uiState by viewModel.uiState.collectAsState()

    LaunchedEffect(Unit) {
        viewModel.clearError()
    }

    AuthGradientBackground(modifier = Modifier.fillMaxSize()) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 16.dp)
                .verticalScroll(rememberScrollState()),
            horizontalAlignment = Alignment.CenterHorizontally,
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
                        title = stringResource(R.string.login_title),
                        subtitle = stringResource(R.string.login_subtitle),
                        showBrandMark = true
                    )

                    LoginTrustPoint(stringResource(R.string.login_trust_1))
                    LoginTrustPoint(stringResource(R.string.login_trust_2))
                    LoginTrustPoint(stringResource(R.string.login_trust_3))

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
                        label = stringResource(R.string.register_label_password)
                    )

                    TextButton(
                        onClick = onNavigateToForgotPassword,
                        modifier = Modifier.align(Alignment.End)
                    ) {
                        Text(stringResource(R.string.login_forgot_password))
                    }

                    when (uiState) {
                        is AuthUiState.Error -> AppMessage(
                            message = (uiState as AuthUiState.Error).message,
                            type = MessageType.ERROR
                        )

                        is AuthUiState.Success -> AppMessage(
                            message = stringResource(R.string.login_success),
                            type = MessageType.SUCCESS
                        )

                        else -> Unit
                    }

                    Button(
                        onClick = { viewModel.login(email, password) },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = uiState !is AuthUiState.Loading
                    ) {
                        if (uiState is AuthUiState.Loading) {
                            CircularProgressIndicator(
                                strokeWidth = 2.dp,
                                modifier = Modifier.size(20.dp)
                            )
                        } else {
                            Text(stringResource(R.string.login_button))
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(16.dp))
            TextButton(onClick = onNavigateToBlog) {
                Text(
                    text = stringResource(R.string.general_blog_title),
                    style = MaterialTheme.typography.bodyLarge
                )
            }
            TextButton(onClick = onNavigateToRegister) {
                Text(
                    text = stringResource(R.string.login_register_cta),
                    style = MaterialTheme.typography.bodyLarge
                )
            }
        }
    }
}

@Composable
private fun LoginTrustPoint(text: String) {
    Text(
        text = "• $text",
        style = MaterialTheme.typography.bodyMedium,
        color = MaterialTheme.colorScheme.onSurfaceVariant
    )
}
