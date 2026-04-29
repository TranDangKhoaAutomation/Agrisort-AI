package com.example.agrisort_ai.features.dashboard.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.ExperimentalMaterialApi
import androidx.compose.material.pullrefresh.PullRefreshIndicator
import androidx.compose.material.pullrefresh.pullRefresh
import androidx.compose.material.pullrefresh.rememberPullRefreshState
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.agrisort_ai.features.dashboard.domain.model.BlogArticle
import com.example.agrisort_ai.ui.components.LoadingState

@OptIn(ExperimentalMaterial3Api::class, ExperimentalMaterialApi::class)
@Composable
fun BlogScreen(
    onNavigateBack: () -> Unit,
    onOpenArticle: (String) -> Unit,
    viewModel: BlogViewModel = hiltViewModel()
) {
    val uiState by viewModel.listUiState.collectAsState()
    val showFullScreenLoading = uiState.isLoading && uiState.posts.isEmpty()
    val pullRefreshState = rememberPullRefreshState(
        refreshing = uiState.isLoading,
        onRefresh = { viewModel.loadPosts() }
    )

    Scaffold(topBar = { TopAppBar(title = { Text("Tin tức nông sản") }) }) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .pullRefresh(pullRefreshState)
        ) {
            LazyColumn(
                modifier = Modifier.fillMaxSize(),
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                item {
                    DataSourceBanner(
                        state = uiState.dataSourceState,
                        errorMessage = uiState.error
                    )
                }

                item {
                    DashboardSectionCard(
                        title = "Bản tin AGRISORT",
                        subtitle = "Tổng hợp các bài viết công khai về sản phẩm, quy trình và lộ trình triển khai."
                    ) {
                        OutlinedButton(
                            onClick = onNavigateBack,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Quay lại")
                        }
                    }
                }

                if (uiState.posts.isEmpty() && !uiState.isLoading) {
                    item {
                        DashboardSectionCard(
                            title = "Chưa có bài viết",
                            subtitle = "Hệ thống hiện chưa trả về bài viết công khai nào."
                        ) {
                            Text("Thử tải lại sau khi có nội dung mới.")
                        }
                    }
                } else {
                    items(uiState.posts, key = { it.id }) { article ->
                        BlogArticleCard(
                            article = article,
                            onOpen = { onOpenArticle(article.slug) }
                        )
                    }
                }
            }

            if (!showFullScreenLoading) {
                PullRefreshIndicator(
                    refreshing = uiState.isLoading,
                    state = pullRefreshState,
                    modifier = Modifier.align(Alignment.TopCenter)
                )
            }

            if (showFullScreenLoading) {
                LoadingState(
                    modifier = Modifier
                        .fillMaxSize()
                        .align(Alignment.Center)
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun BlogDetailScreen(
    slug: String,
    onNavigateBack: () -> Unit,
    viewModel: BlogViewModel = hiltViewModel()
) {
    val uiState by viewModel.detailUiState.collectAsState()

    LaunchedEffect(slug) {
        viewModel.loadPost(slug)
    }

    Scaffold(topBar = { TopAppBar(title = { Text("Chi tiết bài viết") }) }) { paddingValues ->
        if (uiState.isLoading && uiState.article == null) {
            LoadingState(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
            )
            return@Scaffold
        }

        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            item {
                DataSourceBanner(
                    state = uiState.dataSourceState,
                    errorMessage = uiState.error
                )
            }

            item {
                OutlinedButton(
                    onClick = onNavigateBack,
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("Quay lại danh sách")
                }
            }

            val article = uiState.article
            if (article == null) {
                item {
                    DashboardSectionCard(
                        title = "Không tìm thấy bài viết",
                        subtitle = "Slug đang mở không còn tồn tại trên hệ thống."
                    ) {
                        Text("Hãy quay lại danh sách và chọn bài viết khác.")
                    }
                }
            } else {
                item {
                    DashboardSectionCard(
                        title = article.titleVi.ifBlank { article.titleEn.ifBlank { article.slug } },
                        subtitle = listOfNotNull(
                            article.status.takeIf { it.isNotBlank() },
                            article.publishedAt.takeIf { it.isNotBlank() }
                        ).joinToString(" | ")
                    ) {
                        if (article.excerptVi.isNotBlank()) {
                            Text(
                                text = article.excerptVi,
                                style = MaterialTheme.typography.bodyLarge,
                                fontWeight = FontWeight.Medium
                            )
                        }

                        if (!article.thumbnailUrl.isNullOrBlank()) {
                            Text(
                                text = "Ảnh đại diện: ${article.thumbnailUrl}",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }

                item {
                    DashboardSectionCard(
                        title = "Nội dung bài viết",
                        subtitle = null
                    ) {
                        Text(article.contentVi.ifBlank { "Bài viết chưa có nội dung tiếng Việt." })
                    }
                }
            }
        }
    }
}

@Composable
private fun BlogArticleCard(
    article: BlogArticle,
    onOpen: () -> Unit
) {
    DashboardSectionCard(
        title = article.titleVi.ifBlank { article.titleEn.ifBlank { article.slug } },
        subtitle = listOfNotNull(
            article.status.takeIf { it.isNotBlank() },
            article.publishedAt.takeIf { it.isNotBlank() }
        ).joinToString(" | ")
    ) {
        Text(
            text = article.excerptVi.ifBlank { "Mở bài viết để xem nội dung chi tiết." },
            style = MaterialTheme.typography.bodyMedium
        )
        OutlinedButton(
            onClick = onOpen,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text("Mở bài viết")
        }
    }
}
