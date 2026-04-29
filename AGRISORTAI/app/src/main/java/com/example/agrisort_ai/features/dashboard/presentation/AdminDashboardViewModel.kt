package com.example.agrisort_ai.features.dashboard.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.agrisort_ai.core.network.Resource
import com.example.agrisort_ai.features.dashboard.data.remote.AdminSettingsRequest
import com.example.agrisort_ai.features.dashboard.data.remote.SaveBlogRequest
import com.example.agrisort_ai.features.dashboard.data.remote.SaveCmsRequest
import com.example.agrisort_ai.features.dashboard.data.remote.TraceAssignmentRequest
import com.example.agrisort_ai.features.dashboard.domain.model.AdminCmsSection
import com.example.agrisort_ai.features.dashboard.domain.model.AdminDashboardSummary
import com.example.agrisort_ai.features.dashboard.domain.model.AdminSettings
import com.example.agrisort_ai.features.dashboard.domain.model.AdminUser
import com.example.agrisort_ai.features.dashboard.domain.model.ApiKeyItem
import com.example.agrisort_ai.features.dashboard.domain.model.BlogArticle
import com.example.agrisort_ai.features.dashboard.domain.model.TraceAssignment
import com.example.agrisort_ai.features.dashboard.domain.model.TraceEvent
import com.example.agrisort_ai.features.dashboard.domain.usecase.DashboardUseCases
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach

data class AdminDashboardUiState(
    val isLoading: Boolean = false,
    val summary: AdminDashboardSummary = AdminDashboardSummary(),
    val users: List<AdminUser> = emptyList(),
    val assignments: List<TraceAssignment> = emptyList(),
    val events: List<TraceEvent> = emptyList(),
    val settings: AdminSettings = AdminSettings(),
    val cmsSection: AdminCmsSection? = null,
    val blogPosts: List<BlogArticle> = emptyList(),
    val apiKeys: List<ApiKeyItem> = emptyList(),
    val latestCreatedApiKey: ApiKeyItem? = null,
    val dataSourceState: DataSourceState = DataSourceState.ERROR,
    val query: String = "",
    val roleFilter: String = "",
    val statusFilter: String = "",
    val cmsSectionKey: String = "hero",
    val message: String? = null,
    val error: String? = null
)

@HiltViewModel
class AdminDashboardViewModel @Inject constructor(
    private val dashboardUseCases: DashboardUseCases
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminDashboardUiState(isLoading = true))
    val uiState: StateFlow<AdminDashboardUiState> = _uiState.asStateFlow()

    init {
        refreshAll()
    }

    fun onQueryChange(query: String) {
        _uiState.value = _uiState.value.copy(query = query)
    }

    fun onRoleFilterChange(role: String) {
        _uiState.value = _uiState.value.copy(roleFilter = role)
    }

    fun onStatusFilterChange(status: String) {
        _uiState.value = _uiState.value.copy(statusFilter = status)
    }

    fun onCmsSectionKeyChange(sectionKey: String) {
        _uiState.value = _uiState.value.copy(cmsSectionKey = sectionKey)
    }

    fun refreshAll() {
        loadSummary()
        loadUsers()
        loadAssignments()
        loadEvents()
        loadSettings()
        loadBlogPosts()
        loadApiKeys()
        loadCmsSection(_uiState.value.cmsSectionKey)
    }

    fun loadSummary() {
        dashboardUseCases.adminDashboard().onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    summary = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null
                )

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun loadUsers() {
        val state = _uiState.value
        dashboardUseCases.adminUsers(
            query = state.query.ifBlank { null },
            role = state.roleFilter.ifBlank { null },
            status = state.statusFilter.ifBlank { null },
            limit = 50
        ).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    users = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null
                )

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun updateUserStatus(userId: Int, status: String) {
        dashboardUseCases.updateAdminUserStatus(userId, status).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        dataSourceState = DataSourceState.LIVE,
                        message = result.data,
                        error = null
                    )
                    loadUsers()
                    loadSummary()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun approvePartner(userId: Int) {
        dashboardUseCases.approvePartner(userId).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        dataSourceState = DataSourceState.LIVE,
                        message = result.data,
                        error = null
                    )
                    loadUsers()
                    loadSummary()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun loadSettings() {
        dashboardUseCases.adminSettings().onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    settings = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null
                )

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun updateSettings(request: AdminSettingsRequest) {
        dashboardUseCases.updateAdminSettings(request).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    settings = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    message = "Đã cập nhật cấu hình hệ thống.",
                    error = null
                )

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun loadCmsSection(sectionKey: String = _uiState.value.cmsSectionKey) {
        val normalized = sectionKey.trim().ifBlank { "hero" }
        _uiState.value = _uiState.value.copy(cmsSectionKey = normalized)
        dashboardUseCases.adminCms(normalized).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    cmsSection = result.data,
                    cmsSectionKey = normalized,
                    dataSourceState = DataSourceState.LIVE,
                    error = null
                )

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun saveCms(sectionKey: String, request: SaveCmsRequest) {
        dashboardUseCases.saveAdminCms(sectionKey.trim().ifBlank { "hero" }, request).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    cmsSection = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    message = "Đã cập nhật nội dung CMS.",
                    error = null
                )

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun loadBlogPosts() {
        dashboardUseCases.adminBlog().onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    blogPosts = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null
                )

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun saveBlog(request: SaveBlogRequest) {
        dashboardUseCases.saveAdminBlog(request).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        dataSourceState = DataSourceState.LIVE,
                        message = result.data,
                        error = null
                    )
                    loadBlogPosts()
                    loadSummary()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun loadApiKeys() {
        dashboardUseCases.adminApiKeys().onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    apiKeys = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null
                )

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun createApiKey(name: String) {
        dashboardUseCases.createAdminApiKey(name).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        latestCreatedApiKey = result.data,
                        dataSourceState = DataSourceState.LIVE,
                        message = "Đã tạo API key mới. Hãy lưu lại ngay.",
                        error = null
                    )
                    loadApiKeys()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun setLotPublish(lotId: Int, publishStatus: String) {
        dashboardUseCases.setAdminLotPublish(lotId, publishStatus).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        message = result.data,
                        error = null,
                        dataSourceState = DataSourceState.LIVE
                    )
                    loadSummary()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun loadAssignments() {
        dashboardUseCases.adminTraceAssignments().onEach { result ->
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

    fun saveAssignment(entityType: String, entityId: Int, stageCode: String, actorUserId: Int) {
        dashboardUseCases.saveTraceAssignment(
            TraceAssignmentRequest(
                entityType = entityType,
                entityId = entityId,
                stageCode = stageCode,
                actorUserId = actorUserId
            )
        ).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        dataSourceState = DataSourceState.LIVE,
                        message = result.data,
                        error = null
                    )
                    loadAssignments()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun deleteAssignment(assignmentId: Int) {
        dashboardUseCases.deleteTraceAssignment(assignmentId).onEach { result ->
            when (result) {
                is Resource.Loading -> setLoading()
                is Resource.Success -> {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        message = result.data,
                        error = null,
                        dataSourceState = DataSourceState.LIVE
                    )
                    loadAssignments()
                }

                is Resource.Error -> setError(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun loadEvents() {
        dashboardUseCases.adminTraceEvents(200).onEach { result ->
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

    fun clearMessages() {
        _uiState.value = _uiState.value.copy(
            message = null,
            error = null,
            latestCreatedApiKey = null
        )
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
        return state.summary.totalUsers > 0 ||
            state.summary.activeUsers > 0 ||
            state.summary.totalLots > 0 ||
            state.summary.publishedLots > 0 ||
            state.summary.pendingPartners > 0 ||
            state.users.isNotEmpty() ||
            state.assignments.isNotEmpty() ||
            state.events.isNotEmpty() ||
            state.blogPosts.isNotEmpty() ||
            state.apiKeys.isNotEmpty() ||
            state.cmsSection != null ||
            state.settings.smtpHost.isNotBlank() ||
            state.settings.qrBaseUrl.isNotBlank()
    }
}
