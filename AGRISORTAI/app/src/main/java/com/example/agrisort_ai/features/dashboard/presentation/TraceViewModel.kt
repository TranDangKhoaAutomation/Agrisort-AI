package com.example.agrisort_ai.features.dashboard.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.agrisort_ai.core.data.ScanPreferencesManager
import com.example.agrisort_ai.core.network.Resource
import com.example.agrisort_ai.features.dashboard.domain.model.ScanHistory
import com.example.agrisort_ai.features.dashboard.domain.model.TraceLookupResult
import com.example.agrisort_ai.features.dashboard.domain.usecase.DashboardUseCases
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import kotlinx.coroutines.launch

enum class ScanFeedback {
    IDLE,
    SUCCESS,
    ERROR
}

data class TraceUiState(
    val isLoading: Boolean = false,
    val manualToken: String = "",
    val currentResult: TraceLookupResult? = null,
    val history: List<ScanHistory> = emptyList(),
    val deduplicateHistory: Boolean = true,
    val torchEnabled: Boolean = false,
    val scanFeedback: ScanFeedback = ScanFeedback.IDLE,
    val feedbackId: Long = 0L,
    val dataSourceState: DataSourceState = DataSourceState.ERROR,
    val error: String? = null,
    val message: String? = null
)

@HiltViewModel
class TraceViewModel @Inject constructor(
    private val dashboardUseCases: DashboardUseCases,
    private val scanPreferencesManager: ScanPreferencesManager
) : ViewModel() {

    private val _uiState = MutableStateFlow(TraceUiState(isLoading = false))
    val uiState: StateFlow<TraceUiState> = _uiState.asStateFlow()

    private var lastScannedToken: String? = null
    private var lastScanAtMillis: Long = 0L
    private val minDebounceMillis = 1800L
    private var lastCameraFrameToken: String? = null
    private var feedbackSequence: Long = 0L

    init {
        observeHistory()
        observeSettings()
    }

    private fun observeHistory() {
        dashboardUseCases.observeScanHistory(100).onEach { history ->
            _uiState.value = _uiState.value.copy(history = history)
        }.launchIn(viewModelScope)
    }

    private fun observeSettings() {
        scanPreferencesManager.deduplicateScanHistory.onEach { enabled ->
            _uiState.value = _uiState.value.copy(deduplicateHistory = enabled)
        }.launchIn(viewModelScope)
    }

    fun onManualTokenChange(value: String) {
        _uiState.value = _uiState.value.copy(
            manualToken = value,
            error = null,
            dataSourceState = if (_uiState.value.currentResult != null) {
                _uiState.value.dataSourceState
            } else {
                DataSourceState.ERROR
            },
            scanFeedback = ScanFeedback.IDLE
        )
    }

    fun setTorchEnabled(enabled: Boolean) {
        _uiState.value = _uiState.value.copy(torchEnabled = enabled)
    }

    fun submitManualToken() {
        lookupToken(
            token = _uiState.value.manualToken.trim(),
            source = "manual",
            force = true,
            shouldSaveHistory = true
        )
    }

    fun onCameraFrameToken(token: String?) {
        val normalized = token?.trim().orEmpty()
        if (normalized.isBlank()) {
            lastCameraFrameToken = null
            return
        }
        if (normalized == lastCameraFrameToken) {
            return
        }
        lastCameraFrameToken = normalized
        lookupToken(
            token = normalized,
            source = "camera",
            force = true,
            shouldSaveHistory = true
        )
    }

    fun onTokenDetectedFromGallery(token: String) {
        lookupToken(
            token = token.trim(),
            source = "gallery",
            force = true,
            shouldSaveHistory = true
        )
    }

    fun openToken(token: String) {
        _uiState.value = _uiState.value.copy(
            manualToken = token,
            currentResult = null,
            dataSourceState = DataSourceState.ERROR,
            error = null,
            message = null,
            scanFeedback = ScanFeedback.IDLE
        )
        lookupToken(
            token = token.trim(),
            source = "deeplink",
            force = true,
            shouldSaveHistory = false
        )
    }

    fun clearMessages() {
        _uiState.value = _uiState.value.copy(
            message = null,
            error = null,
            scanFeedback = ScanFeedback.IDLE
        )
    }

    fun onScanError(message: String) {
        _uiState.value = _uiState.value.copy(
            feedbackId = nextFeedbackId(),
            error = message,
            message = null,
            scanFeedback = ScanFeedback.ERROR
        )
    }

    fun clearHistory() {
        viewModelScope.launch {
            dashboardUseCases.clearScanHistory()
        }
    }

    private fun lookupToken(
        token: String,
        source: String,
        force: Boolean,
        shouldSaveHistory: Boolean
    ) {
        val now = System.currentTimeMillis()
        if (!force && token == lastScannedToken && now - lastScanAtMillis < minDebounceMillis) {
            return
        }
        if (!isValidToken(token)) {
            _uiState.value = _uiState.value.copy(
                feedbackId = nextFeedbackId(),
                currentResult = null,
                dataSourceState = DataSourceState.ERROR,
                error = "Token không hợp lệ",
                message = null,
                scanFeedback = ScanFeedback.ERROR
            )
            return
        }

        lastScannedToken = token
        lastScanAtMillis = now

        dashboardUseCases.traceLookup(token, source).onEach { result ->
            when (result) {
                is Resource.Loading -> _uiState.value = _uiState.value.copy(
                    isLoading = true,
                    currentResult = if (_uiState.value.currentResult?.token == token) {
                        _uiState.value.currentResult
                    } else {
                        null
                    },
                    dataSourceState = if (_uiState.value.currentResult?.token == token) {
                        _uiState.value.dataSourceState
                    } else {
                        DataSourceState.ERROR
                    },
                    error = null,
                    message = null,
                    scanFeedback = ScanFeedback.IDLE
                )

                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    feedbackId = nextFeedbackId(),
                    currentResult = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null,
                    message = "Truy xuất thành công",
                    scanFeedback = ScanFeedback.SUCCESS
                ).also {
                    if (shouldSaveHistory) {
                        persistHistory(
                            token = token,
                            source = source,
                            status = "success",
                            message = "Truy xuất thành công"
                        )
                    }
                }

                is Resource.Error -> {
                    // Nếu lookup thất bại, thử fallback trace/{token}
                    fallbackTraceShow(
                        token = token,
                        source = source,
                        previousError = result.message,
                        shouldSaveHistory = shouldSaveHistory
                    )
                }
            }
        }.launchIn(viewModelScope)
    }

    private fun fallbackTraceShow(
        token: String,
        source: String,
        previousError: String,
        shouldSaveHistory: Boolean
    ) {
        dashboardUseCases.traceShow(token, source).onEach { result ->
            when (result) {
                is Resource.Loading -> _uiState.value = _uiState.value.copy(isLoading = true)

                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    feedbackId = nextFeedbackId(),
                    currentResult = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null,
                    message = "Truy xuất thành công",
                    scanFeedback = ScanFeedback.SUCCESS
                ).also {
                    if (shouldSaveHistory) {
                        persistHistory(
                            token = token,
                            source = source,
                            status = "success",
                            message = "Truy xuất thành công"
                        )
                    }
                }

                is Resource.Error -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    feedbackId = nextFeedbackId(),
                    currentResult = null,
                    dataSourceState = DataSourceState.ERROR,
                    error = result.message.ifBlank { previousError },
                    message = null,
                    scanFeedback = ScanFeedback.ERROR
                ).also {
                    if (shouldSaveHistory) {
                        persistHistory(
                            token = token,
                            source = source,
                            status = "error",
                            message = result.message.ifBlank { previousError }
                        )
                    }
                }
            }
        }.launchIn(viewModelScope)
    }

    private fun persistHistory(
        token: String,
        source: String,
        status: String,
        message: String?
    ) {
        viewModelScope.launch {
            val deduplicateHistory = _uiState.value.deduplicateHistory
            val alreadyExists = deduplicateHistory && _uiState.value.history.any { it.token == token }
            dashboardUseCases.saveScanHistory(
                token = token,
                source = source,
                status = status,
                message = message,
                deduplicateByToken = deduplicateHistory
            )
            if (alreadyExists) {
                _uiState.value = _uiState.value.copy(
                    feedbackId = nextFeedbackId(),
                    message = "Token đã có trong bảng, đã cập nhật lần quét mới nhất."
                )
            }
        }
    }

    private fun isValidToken(token: String): Boolean {
        if (token.length !in 4..128) return false
        return token.matches(Regex("^[A-Za-z0-9._-]+$"))
    }

    private fun nextFeedbackId(): Long {
        feedbackSequence += 1
        return feedbackSequence
    }
}
