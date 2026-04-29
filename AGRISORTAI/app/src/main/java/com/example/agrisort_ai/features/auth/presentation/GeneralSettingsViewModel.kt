package com.example.agrisort_ai.features.auth.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.agrisort_ai.core.data.ScanPreferencesManager
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import kotlinx.coroutines.launch

data class GeneralSettingsUiState(
    val deduplicateScanHistory: Boolean = true
)

@HiltViewModel
class GeneralSettingsViewModel @Inject constructor(
    private val scanPreferencesManager: ScanPreferencesManager
) : ViewModel() {

    private val _uiState = MutableStateFlow(GeneralSettingsUiState())
    val uiState: StateFlow<GeneralSettingsUiState> = _uiState.asStateFlow()

    init {
        scanPreferencesManager.deduplicateScanHistory.onEach { enabled ->
            _uiState.value = _uiState.value.copy(deduplicateScanHistory = enabled)
        }.launchIn(viewModelScope)
    }

    fun setDeduplicateScanHistory(enabled: Boolean) {
        viewModelScope.launch {
            scanPreferencesManager.setDeduplicateScanHistory(enabled)
        }
    }
}
