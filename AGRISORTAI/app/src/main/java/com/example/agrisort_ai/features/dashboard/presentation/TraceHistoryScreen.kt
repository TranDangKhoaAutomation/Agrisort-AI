package com.example.agrisort_ai.features.dashboard.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Button
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.agrisort_ai.R
import com.example.agrisort_ai.ui.components.AppCard
import com.example.agrisort_ai.ui.components.EmptyState
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TraceHistoryScreen(
    onNavigateBack: () -> Unit,
    onSelectToken: (String) -> Unit,
    viewModel: TraceViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = { TopAppBar(title = { Text(stringResource(R.string.trace_history_title)) }) }
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            if (uiState.history.isEmpty()) {
                item {
                    EmptyState(
                        title = stringResource(R.string.trace_history_empty_title),
                        message = stringResource(R.string.trace_history_empty_message),
                        actionLabel = stringResource(R.string.trace_history_back),
                        onAction = onNavigateBack
                    )
                }
            } else {
                items(uiState.history, key = { it.id }) { item ->
                    AppCard(modifier = Modifier.fillMaxWidth()) {
                        Column(
                            modifier = Modifier.padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(4.dp)
                        ) {
                            Text(item.token, style = MaterialTheme.typography.titleMedium)
                            Text(
                                text = stringResource(
                                    R.string.trace_history_source_status,
                                    formatHistorySource(item.source),
                                    formatHistoryStatus(item.status)
                                ),
                                style = MaterialTheme.typography.bodySmall
                            )
                            Text(
                                text = stringResource(
                                    R.string.trace_history_time,
                                    SimpleDateFormat(
                                        "yyyy-MM-dd HH:mm:ss",
                                        Locale.getDefault()
                                    ).format(Date(item.scannedAt))
                                ),
                                style = MaterialTheme.typography.bodySmall
                            )
                            item.message?.takeIf { it.isNotBlank() }?.let { message ->
                                Text(
                                    text = message,
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                            Button(
                                onClick = { onSelectToken(item.token) },
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Text(stringResource(R.string.trace_history_open_result))
                            }
                        }
                    }
                }

                item {
                    OutlinedButton(
                        onClick = viewModel::clearHistory,
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text(stringResource(R.string.trace_history_clear_all))
                    }
                }
            }

            item {
                OutlinedButton(onClick = onNavigateBack, modifier = Modifier.fillMaxWidth()) {
                    Text(stringResource(R.string.trace_history_back))
                }
            }
        }
    }
}

@Composable
private fun formatHistorySource(source: String): String {
    return when (source.lowercase()) {
        "camera" -> stringResource(R.string.trace_source_camera)
        "gallery" -> stringResource(R.string.trace_source_gallery)
        "manual" -> stringResource(R.string.trace_source_manual)
        "deeplink" -> stringResource(R.string.trace_source_deeplink)
        else -> stringResource(R.string.trace_source_unknown)
    }
}

@Composable
private fun formatHistoryStatus(status: String): String {
    return when (status.lowercase()) {
        "success" -> stringResource(R.string.trace_status_success)
        "error" -> stringResource(R.string.trace_status_error)
        else -> status
    }
}
