package com.example.agrisort_ai.features.dashboard.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.agrisort_ai.core.network.Resource
import com.example.agrisort_ai.features.dashboard.data.remote.CreateLotRequest
import com.example.agrisort_ai.features.dashboard.data.remote.CreatePackageRequest
import com.example.agrisort_ai.features.dashboard.data.remote.UpdateLotRequest
import com.example.agrisort_ai.features.dashboard.data.remote.UpdatePackageRequest
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerLot
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerPackage
import com.example.agrisort_ai.features.dashboard.domain.usecase.DashboardUseCases
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach

data class PartnerDashboardUiState(
    val isLoading: Boolean = false,
    val lots: List<PartnerLot> = emptyList(),
    val packages: List<PartnerPackage> = emptyList(),
    val dataSourceState: DataSourceState = DataSourceState.ERROR,
    val message: String? = null,
    val error: String? = null
)

@HiltViewModel
class PartnerDashboardViewModel @Inject constructor(
    private val dashboardUseCases: DashboardUseCases
) : ViewModel() {

    private val _uiState = MutableStateFlow(PartnerDashboardUiState(isLoading = true))
    val uiState: StateFlow<PartnerDashboardUiState> = _uiState.asStateFlow()

    init {
        refreshAll()
    }

    fun refreshAll() {
        loadLots()
        loadPackages()
    }

    fun loadLots() {
        dashboardUseCases.partnerLots().onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    lots = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null
                )

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun createLot(request: CreateLotRequest) {
        dashboardUseCases.createPartnerLot(request).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        dataSourceState = DataSourceState.LIVE,
                        message = result.data,
                        error = null
                    )
                    loadLots()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun updateLot(lotId: Int, request: UpdateLotRequest) {
        dashboardUseCases.updatePartnerLot(lotId, request).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        dataSourceState = DataSourceState.LIVE,
                        message = result.data,
                        error = null
                    )
                    loadLots()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun deleteLot(lotId: Int) {
        dashboardUseCases.deletePartnerLot(lotId).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        message = result.data,
                        error = null,
                        dataSourceState = DataSourceState.LIVE
                    )
                    loadLots()
                    loadPackages()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun regenerateLotQr(lotId: Int) {
        dashboardUseCases.regeneratePartnerLotQr(lotId).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        message = result.data,
                        error = null,
                        dataSourceState = DataSourceState.LIVE
                    )
                    loadLots()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun loadPackages() {
        dashboardUseCases.partnerPackages().onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    packages = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null
                )

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun createPackage(request: CreatePackageRequest) {
        dashboardUseCases.createPartnerPackage(request).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        dataSourceState = DataSourceState.LIVE,
                        message = result.data,
                        error = null
                    )
                    loadPackages()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun updatePackage(packageId: Int, request: UpdatePackageRequest) {
        dashboardUseCases.updatePartnerPackage(packageId, request).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        dataSourceState = DataSourceState.LIVE,
                        message = result.data,
                        error = null
                    )
                    loadPackages()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun deletePackage(packageId: Int) {
        dashboardUseCases.deletePartnerPackage(packageId).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        dataSourceState = DataSourceState.LIVE,
                        message = result.data,
                        error = null
                    )
                    loadPackages()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun regeneratePackageQr(packageId: Int) {
        dashboardUseCases.regeneratePartnerPackageQr(packageId).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        dataSourceState = DataSourceState.LIVE,
                        message = result.data,
                        error = null
                    )
                    loadPackages()
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
        return state.lots.isNotEmpty() || state.packages.isNotEmpty()
    }
}
