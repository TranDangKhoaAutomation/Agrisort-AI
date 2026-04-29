package com.example.agrisort_ai.features.dashboard.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.agrisort_ai.core.network.Resource
import com.example.agrisort_ai.features.dashboard.domain.model.BlogArticle
import com.example.agrisort_ai.features.dashboard.domain.usecase.DashboardUseCases
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach

data class BlogListUiState(
    val isLoading: Boolean = false,
    val posts: List<BlogArticle> = emptyList(),
    val dataSourceState: DataSourceState = DataSourceState.ERROR,
    val error: String? = null
)

data class BlogDetailUiState(
    val isLoading: Boolean = false,
    val article: BlogArticle? = null,
    val dataSourceState: DataSourceState = DataSourceState.ERROR,
    val error: String? = null
)

@HiltViewModel
class BlogViewModel @Inject constructor(
    private val dashboardUseCases: DashboardUseCases
) : ViewModel() {

    private val _listUiState = MutableStateFlow(BlogListUiState(isLoading = true))
    val listUiState: StateFlow<BlogListUiState> = _listUiState.asStateFlow()

    private val _detailUiState = MutableStateFlow(BlogDetailUiState(isLoading = true))
    val detailUiState: StateFlow<BlogDetailUiState> = _detailUiState.asStateFlow()

    init {
        loadPosts()
    }

    fun loadPosts(limit: Int = 20) {
        dashboardUseCases.blogIndex(limit).onEach { result ->
            when (result) {
                is Resource.Loading -> _listUiState.value = _listUiState.value.copy(isLoading = true, error = null)
                is Resource.Success -> _listUiState.value = _listUiState.value.copy(
                    isLoading = false,
                    posts = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null
                )

                is Resource.Error -> _listUiState.value = _listUiState.value.copy(
                    isLoading = false,
                    dataSourceState = if (_listUiState.value.posts.isNotEmpty()) DataSourceState.STALE else DataSourceState.ERROR,
                    error = result.message
                )
            }
        }.launchIn(viewModelScope)
    }

    fun loadPost(slug: String) {
        dashboardUseCases.blogShow(slug).onEach { result ->
            when (result) {
                is Resource.Loading -> _detailUiState.value = _detailUiState.value.copy(isLoading = true, error = null)
                is Resource.Success -> _detailUiState.value = _detailUiState.value.copy(
                    isLoading = false,
                    article = result.data,
                    dataSourceState = DataSourceState.LIVE,
                    error = null
                )

                is Resource.Error -> _detailUiState.value = _detailUiState.value.copy(
                    isLoading = false,
                    dataSourceState = if (_detailUiState.value.article != null) DataSourceState.STALE else DataSourceState.ERROR,
                    error = result.message
                )
            }
        }.launchIn(viewModelScope)
    }
}
