package com.example.agrisort_ai.features.dashboard.presentation

import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.ExperimentalMaterialApi
import androidx.compose.material.pullrefresh.PullRefreshIndicator
import androidx.compose.material.pullrefresh.pullRefresh
import androidx.compose.material.pullrefresh.rememberPullRefreshState
import androidx.compose.material3.Button
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.agrisort_ai.features.dashboard.data.remote.AdminSettingsRequest
import com.example.agrisort_ai.features.dashboard.data.remote.SaveBlogRequest
import com.example.agrisort_ai.features.dashboard.data.remote.SaveCmsRequest
import com.example.agrisort_ai.features.dashboard.domain.model.BlogArticle
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.AppTextField
import com.example.agrisort_ai.ui.components.LoadingState
import com.example.agrisort_ai.ui.components.MessageType

@OptIn(ExperimentalMaterial3Api::class, ExperimentalMaterialApi::class)
@Composable
fun AdminControlScreen(
    onNavigateBack: () -> Unit,
    viewModel: AdminDashboardViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val showFullScreenLoading = uiState.isLoading &&
        uiState.blogPosts.isEmpty() &&
        uiState.apiKeys.isEmpty() &&
        uiState.cmsSection == null

    var autoPublishLot by remember { mutableStateOf(false) }
    var smtpHost by remember { mutableStateOf("") }
    var smtpPort by remember { mutableStateOf("") }
    var smtpUser by remember { mutableStateOf("") }
    var smtpPass by remember { mutableStateOf("") }
    var smtpFrom by remember { mutableStateOf("") }
    var qrBaseUrl by remember { mutableStateOf("") }
    var googleTranslateApiKey by remember { mutableStateOf("") }

    var cmsSectionKey by remember { mutableStateOf(uiState.cmsSectionKey) }
    var cmsTitleVi by remember { mutableStateOf("") }
    var cmsBodyVi by remember { mutableStateOf("") }
    var cmsImageUri by remember { mutableStateOf<Uri?>(null) }

    var editingBlogId by remember { mutableStateOf<Int?>(null) }
    var blogSlug by remember { mutableStateOf("") }
    var blogTitleVi by remember { mutableStateOf("") }
    var blogContentVi by remember { mutableStateOf("") }
    var blogExcerptVi by remember { mutableStateOf("") }
    var blogSeoTitle by remember { mutableStateOf("") }
    var blogSeoDescription by remember { mutableStateOf("") }
    var blogStatus by remember { mutableStateOf("draft") }
    var blogThumbnailUri by remember { mutableStateOf<Uri?>(null) }

    var apiKeyName by remember { mutableStateOf("Tích hợp ứng dụng") }
    var publishLotId by remember { mutableIntStateOf(0) }
    var publishStatus by remember { mutableStateOf("published") }

    val cmsImagePicker = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri ->
        cmsImageUri = uri
    }

    val blogImagePicker = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri ->
        blogThumbnailUri = uri
    }

    LaunchedEffect(uiState.settings) {
        autoPublishLot = uiState.settings.autoPublishLot
        smtpHost = uiState.settings.smtpHost
        smtpPort = uiState.settings.smtpPort
        smtpUser = uiState.settings.smtpUser
        smtpPass = uiState.settings.smtpPass
        smtpFrom = uiState.settings.smtpFrom
        qrBaseUrl = uiState.settings.qrBaseUrl
        googleTranslateApiKey = uiState.settings.googleTranslateApiKey
    }

    LaunchedEffect(uiState.cmsSection) {
        val cms = uiState.cmsSection ?: return@LaunchedEffect
        cmsSectionKey = cms.sectionKey
        cmsTitleVi = cms.vi.title
        cmsBodyVi = cms.vi.body
    }

    val pullRefreshState = rememberPullRefreshState(
        refreshing = uiState.isLoading,
        onRefresh = viewModel::refreshAll
    )

    Scaffold(topBar = { TopAppBar(title = { Text("Điều hành nội dung (cho website và hệ thống)") }) }) { paddingValues ->
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

                if (!uiState.message.isNullOrBlank()) {
                    item {
                        AppMessage(
                            message = uiState.message.orEmpty(),
                            type = MessageType.SUCCESS
                        )
                    }
                }

                item {
                    DashboardSectionCard(
                        title = "Điều hướng nhanh (cho website và hệ thống)",
                        subtitle = "Mở nhanh các tác vụ cấu hình, nội dung công khai, blog và khóa kết nối."
                    ) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            OutlinedButton(
                                onClick = onNavigateBack,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text("Quay lại")
                            }
                            OutlinedButton(
                                onClick = viewModel::refreshAll,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text("Tải lại")
                            }
                        }
                    }
                }

                item {
                    DashboardSectionCard(
                        title = "Cấu hình hệ thống (cho toàn hệ thống)",
                        subtitle = "Quản lý publish mặc định, email hệ thống, URL QR và khóa xử lý bản dịch tự động."
                    ) {
                        AppTextField(
                            value = if (autoPublishLot) "1" else "0",
                            onValueChange = { autoPublishLot = it == "1" || it.equals("true", ignoreCase = true) },
                            label = "Tự động công khai lô (1/0)"
                        )
                        AppTextField(value = smtpHost, onValueChange = { smtpHost = it }, label = "Máy chủ SMTP")
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            AppTextField(
                                value = smtpPort,
                                onValueChange = { smtpPort = it },
                                label = "Cổng SMTP",
                                modifier = Modifier.weight(1f)
                            )
                            AppTextField(
                                value = smtpFrom,
                                onValueChange = { smtpFrom = it },
                                label = "Email gửi đi",
                                modifier = Modifier.weight(1f)
                            )
                        }
                        AppTextField(value = smtpUser, onValueChange = { smtpUser = it }, label = "Tài khoản SMTP")
                        AppTextField(value = smtpPass, onValueChange = { smtpPass = it }, label = "Mật khẩu SMTP")
                        AppTextField(value = qrBaseUrl, onValueChange = { qrBaseUrl = it }, label = "URL gốc cho QR")
                        AppTextField(
                            value = googleTranslateApiKey,
                            onValueChange = { googleTranslateApiKey = it },
                            label = "Khóa xử lý bản dịch"
                        )
                        Button(
                            onClick = {
                                viewModel.updateSettings(
                                    AdminSettingsRequest(
                                        autoPublishLot = if (autoPublishLot) "1" else "0",
                                        smtpHost = smtpHost,
                                        smtpPort = smtpPort,
                                        smtpUser = smtpUser,
                                        smtpPass = smtpPass,
                                        smtpFrom = smtpFrom,
                                        qrBaseUrl = qrBaseUrl,
                                        googleTranslateApiKey = googleTranslateApiKey
                                    )
                                )
                            },
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Lưu cấu hình")
                        }
                    }
                }

                item {
                    DashboardSectionCard(
                        title = "Nội dung trang công khai (cho website công khai)",
                        subtitle = "Chỉ nhập nội dung tiếng Việt. Nội dung tiếng Anh sẽ được xử lý tự động khi lưu."
                    ) {
                        AppTextField(
                            value = cmsSectionKey,
                            onValueChange = {
                                cmsSectionKey = it
                                viewModel.onCmsSectionKeyChange(it)
                            },
                            label = "Mã section"
                        )
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            OutlinedButton(
                                onClick = { viewModel.loadCmsSection(cmsSectionKey) },
                                modifier = Modifier.weight(1f)
                            ) {
                                Text("Nạp section")
                            }
                            OutlinedButton(
                                onClick = { cmsImagePicker.launch("image/*") },
                                modifier = Modifier.weight(1f)
                            ) {
                                Text("Chọn ảnh")
                            }
                        }
                        Text("Ảnh mới: ${cmsImageUri?.lastPathSegment ?: "Chưa chọn"}")
                        AppTextField(value = cmsTitleVi, onValueChange = { cmsTitleVi = it }, label = "Tiêu đề")
                        AppTextField(
                            value = cmsBodyVi,
                            onValueChange = { cmsBodyVi = it },
                            label = "Nội dung",
                            singleLine = false
                        )
                        Button(
                            onClick = {
                                viewModel.saveCms(
                                    sectionKey = cmsSectionKey,
                                    request = SaveCmsRequest(
                                        titleVi = cmsTitleVi,
                                        bodyVi = cmsBodyVi,
                                        titleEn = "",
                                        bodyEn = "",
                                        autoTranslateEn = true,
                                        imageUri = cmsImageUri
                                    )
                                )
                            },
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Lưu nội dung")
                        }
                    }
                }

                item {
                    DashboardSectionCard(
                        title = "Bài viết blog (cho website công khai)",
                        subtitle = "Chỉ nhập tiếng Việt. Phần tiếng Anh sẽ được tạo tự động từ nội dung đã lưu."
                    ) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            OutlinedButton(
                                onClick = { blogImagePicker.launch("image/*") },
                                modifier = Modifier.weight(1f)
                            ) {
                                Text("Chọn thumbnail")
                            }
                            OutlinedButton(
                                onClick = {
                                    editingBlogId = null
                                    blogSlug = ""
                                    blogTitleVi = ""
                                    blogContentVi = ""
                                    blogExcerptVi = ""
                                    blogSeoTitle = ""
                                    blogSeoDescription = ""
                                    blogStatus = "draft"
                                    blogThumbnailUri = null
                                },
                                modifier = Modifier.weight(1f)
                            ) {
                                Text("Form mới")
                            }
                        }
                        Text("Thumbnail mới: ${blogThumbnailUri?.lastPathSegment ?: "Chưa chọn"}")
                        AppTextField(
                            value = editingBlogId?.toString().orEmpty(),
                            onValueChange = {},
                            label = "ID bài viết"
                        )
                        AppTextField(value = blogSlug, onValueChange = { blogSlug = it }, label = "Slug")
                        AppTextField(value = blogTitleVi, onValueChange = { blogTitleVi = it }, label = "Tiêu đề")
                        AppTextField(
                            value = blogContentVi,
                            onValueChange = { blogContentVi = it },
                            label = "Nội dung",
                            singleLine = false
                        )
                        AppTextField(value = blogExcerptVi, onValueChange = { blogExcerptVi = it }, label = "Tóm tắt")
                        AppTextField(value = blogSeoTitle, onValueChange = { blogSeoTitle = it }, label = "Tiêu đề SEO")
                        AppTextField(
                            value = blogSeoDescription,
                            onValueChange = { blogSeoDescription = it },
                            label = "Mô tả SEO",
                            singleLine = false
                        )
                        AppTextField(value = blogStatus, onValueChange = { blogStatus = it }, label = "Trạng thái")
                        Button(
                            onClick = {
                                viewModel.saveBlog(
                                    SaveBlogRequest(
                                        id = editingBlogId,
                                        slug = blogSlug,
                                        titleVi = blogTitleVi,
                                        contentVi = blogContentVi,
                                        titleEn = "",
                                        contentEn = "",
                                        excerptVi = blogExcerptVi,
                                        excerptEn = "",
                                        seoTitle = blogSeoTitle,
                                        seoDescription = blogSeoDescription,
                                        status = blogStatus,
                                        autoTranslateEn = true,
                                        thumbnailUri = blogThumbnailUri
                                    )
                                )
                            },
                            enabled = blogTitleVi.isNotBlank() && blogContentVi.isNotBlank(),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text(if (editingBlogId == null) "Tạo bài viết" else "Cập nhật bài viết")
                        }
                    }
                }

                if (uiState.blogPosts.isNotEmpty()) {
                    items(uiState.blogPosts.take(8), key = { it.id }) { article ->
                        AdminBlogCard(
                            article = article,
                            onLoadIntoForm = {
                                fillBlogForm(article = article).also { values ->
                                    editingBlogId = values.id
                                    blogSlug = values.slug
                                    blogTitleVi = values.titleVi
                                    blogContentVi = values.contentVi
                                    blogExcerptVi = values.excerptVi
                                    blogSeoTitle = values.seoTitle
                                    blogSeoDescription = values.seoDescription
                                    blogStatus = values.status
                                    blogThumbnailUri = null
                                }
                            }
                        )
                    }
                }

                item {
                    DashboardSectionCard(
                        title = "API key và công khai lô (cho app, máy phân loại và website)",
                        subtitle = "Tạo khóa kết nối cho ứng dụng hoặc máy phân loại, và đổi trạng thái công khai theo mã lô."
                    ) {
                        AppTextField(
                            value = apiKeyName,
                            onValueChange = { apiKeyName = it },
                            label = "Tên khóa kết nối"
                        )
                        Button(
                            onClick = { viewModel.createApiKey(apiKeyName) },
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Tạo khóa kết nối")
                        }

                        uiState.latestCreatedApiKey?.let { key ->
                            AppMessage(
                                message = "Khóa mới: ${key.apiKey.orEmpty()}",
                                type = MessageType.INFO
                            )
                        }

                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            AppTextField(
                                value = if (publishLotId == 0) "" else publishLotId.toString(),
                                onValueChange = { publishLotId = it.toIntOrNull() ?: 0 },
                                label = "ID lô",
                                modifier = Modifier.weight(1f)
                            )
                            AppTextField(
                                value = publishStatus,
                                onValueChange = { publishStatus = it },
                                label = "Trạng thái công khai",
                                modifier = Modifier.weight(1f)
                            )
                        }
                        Button(
                            onClick = { viewModel.setLotPublish(publishLotId, publishStatus) },
                            enabled = publishLotId > 0,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Cập nhật công khai")
                        }
                    }
                }

                if (uiState.apiKeys.isNotEmpty()) {
                    items(uiState.apiKeys.take(8), key = { it.id }) { apiKey ->
                        DashboardSectionCard(
                            title = apiKey.name,
                            subtitle = listOfNotNull(
                                apiKey.status.takeIf { it.isNotBlank() },
                                apiKey.createdAt.takeIf { it.isNotBlank() }
                            ).joinToString(" | ")
                        ) {
                            Text("Lần dùng gần nhất: ${apiKey.lastUsedAt.ifBlank { "Chưa có" }}")
                        }
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

private data class BlogFormValues(
    val id: Int?,
    val slug: String,
    val titleVi: String,
    val contentVi: String,
    val excerptVi: String,
    val seoTitle: String,
    val seoDescription: String,
    val status: String
)

private fun fillBlogForm(article: BlogArticle): BlogFormValues {
    return BlogFormValues(
        id = article.id,
        slug = article.slug,
        titleVi = article.titleVi,
        contentVi = article.contentVi,
        excerptVi = article.excerptVi,
        seoTitle = article.seoTitle.orEmpty(),
        seoDescription = article.seoDescription.orEmpty(),
        status = article.status
    )
}

@Composable
private fun AdminBlogCard(
    article: BlogArticle,
    onLoadIntoForm: () -> Unit
) {
    DashboardSectionCard(
        title = article.titleVi.ifBlank { article.slug },
        subtitle = listOfNotNull(
            article.status.takeIf { it.isNotBlank() },
            article.publishedAt.takeIf { it.isNotBlank() }
        ).joinToString(" | ")
    ) {
        Text(article.excerptVi.ifBlank { "Chưa có tóm tắt." })
        OutlinedButton(
            onClick = onLoadIntoForm,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text("Nạp lên form")
        }
    }
}
