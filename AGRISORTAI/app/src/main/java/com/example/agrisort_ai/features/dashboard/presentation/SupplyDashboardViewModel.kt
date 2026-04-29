package com.example.agrisort_ai.features.dashboard.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.agrisort_ai.core.network.Resource
import com.example.agrisort_ai.features.dashboard.data.remote.CreateSupplyEventRequest
import com.example.agrisort_ai.features.dashboard.data.remote.UpdateSupplyEventRequest
import com.example.agrisort_ai.features.dashboard.domain.model.SupplyAssignment
import com.example.agrisort_ai.features.dashboard.domain.model.SupplyEvent
import com.example.agrisort_ai.features.dashboard.domain.usecase.DashboardUseCases
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach

data class SupplyDashboardUiState(
    val isLoading: Boolean = false,
    val assignments: List<SupplyAssignment> = emptyList(),
    val events: List<SupplyEvent> = emptyList(),
    val dataSourceState: DataSourceState = DataSourceState.ERROR,
    val message: String? = null,
    val error: String? = null
)

@HiltViewModel
class SupplyDashboardViewModel @Inject constructor(
    private val dashboardUseCases: DashboardUseCases
) : ViewModel() {

    private val _uiState = MutableStateFlow(SupplyDashboardUiState(isLoading = true))
    val uiState: StateFlow<SupplyDashboardUiState> = _uiState.asStateFlow()

    init {
        refreshAll()
    }

    fun refreshAll() {
        loadAssignments()
        loadEvents()
    }

    fun loadAssignments() {
        dashboardUseCases.supplyAssignments().onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    assignments = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null
                )

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun loadEvents() {
        dashboardUseCases.supplyEvents().onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    events = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null
                )

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun createEvent(request: CreateSupplyEventRequest) {
        dashboardUseCases.createSupplyEvent(request).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        dataSourceState = DataSourceState.LIVE,
                        message = result.data,
                        error = null
                    )
                    loadEvents()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun updateEvent(eventId: Int, request: UpdateSupplyEventRequest) {
        dashboardUseCases.updateSupplyEvent(eventId, request).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        dataSourceState = DataSourceState.LIVE,
                        message = result.data,
                        error = null
                    )
                    loadEvents()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun clearMessages() {
        _uiState.value = _uiState.value.copy(message = null, error = null)
    }

    private fun setLoading() {
        _uiState.value = _uiState.value.copy(isLoading = true, error = null)
    }

    private fun setError(message: String) {
        _uiState.value = _uiState.value.copy(
            isLoading = false,
            dataSourceState = if (hasAnyData()) DataSourceState.STALE else DataSourceState.ERROR,
            error = message
        )
    }

    private fun hasAnyData(): Boolean {
        val state = _uiState.value
        return state.assignments.isNotEmpty() || state.events.isNotEmpty()
    }
}
