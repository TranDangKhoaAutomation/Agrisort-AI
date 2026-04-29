package com.example.agrisort_ai.features.dashboard.domain.model

data class AdminDashboardSummary(
    val totalUsers: Int = 0,
    val activeUsers: Int = 0,
    val totalLots: Int = 0,
    val publishedLots: Int = 0,
    val pendingPartners: Int = 0,
    val publishedBlogPosts: Int = 0,
    val totalPackages: Int = 0,
    val publishedPackages: Int = 0
)

data class AdminUser(
    val id: Int,
    val fullName: String,
    val email: String,
    val role: String,
    val status: String,
    val organizationName: String? = null,
    val representativeName: String? = null,
    val phone: String? = null,
    val address: String? = null,
    val region: String? = null,
    val taxCode: String? = null
)

data class AdminSettings(
    val autoPublishLot: Boolean = false,
    val smtpHost: String = "",
    val smtpPort: String = "",
    val smtpUser: String = "",
    val smtpPass: String = "",
    val smtpFrom: String = "",
    val qrBaseUrl: String = "",
    val googleTranslateApiKey: String = ""
)

data class CmsLocaleContent(
    val title: String = "",
    val body: String = "",
    val image: String? = null,
    val imageUrl: String? = null
)

data class AdminCmsSection(
    val sectionKey: String,
    val vi: CmsLocaleContent = CmsLocaleContent(),
    val en: CmsLocaleContent = CmsLocaleContent()
)

data class BlogArticle(
    val id: Int,
    val slug: String,
    val titleVi: String,
    val titleEn: String,
    val excerptVi: String,
    val excerptEn: String,
    val contentVi: String,
    val contentEn: String,
    val thumbnail: String? = null,
    val thumbnailUrl: String? = null,
    val status: String = "",
    val publishedAt: String = "",
    val createdAt: String = "",
    val updatedAt: String = "",
    val seoTitle: String? = null,
    val seoDescription: String? = null
)

data class ApiKeyItem(
    val id: Int,
    val name: String,
    val status: String,
    val apiKey: String? = null,
    val note: String? = null,
    val lastUsedAt: String = "",
    val createdAt: String = ""
)

data class TraceAssignment(
    val id: Int,
    val entityType: String,
    val entityId: Int,
    val stageCode: String,
    val actorUserId: Int? = null,
    val actorName: String? = null
)

data class TraceEvent(
    val id: Int,
    val stageCode: String,
    val eventTime: String,
    val locationName: String? = null,
    val note: String? = null,
    val actorName: String? = null,
    val attachments: List<TraceAttachment> = emptyList()
)

data class TraceAttachment(
    val id: Int = 0,
    val fileName: String? = null,
    val fileUrl: String? = null
)

data class PartnerLot(
    val id: Int,
    val partnerId: Int = 0,
    val lotCode: String,
    val produceType: String,
    val originRegion: String? = null,
    val harvestDate: String? = null,
    val grade1Count: Int? = null,
    val grade2Count: Int? = null,
    val defectCount: Int? = null,
    val notes: String? = null,
    val imagePath: String? = null,
    val imageUrl: String? = null,
    val publishStatus: String? = null,
    val qrToken: String? = null,
    val traceUrl: String? = null,
    val qrPngUrl: String? = null,
    val qrSvgUrl: String? = null,
    val createdAt: String = "",
    val updatedAt: String = ""
)

data class PartnerPackage(
    val id: Int,
    val lotId: Int,
    val lotCode: String? = null,
    val produceType: String? = null,
    val packageCode: String,
    val packageLabel: String? = null,
    val quantity: Int = 0,
    val netWeightKg: String? = null,
    val publishStatus: String? = null,
    val qrToken: String? = null,
    val traceUrl: String? = null,
    val qrPngUrl: String? = null,
    val qrSvgUrl: String? = null,
    val createdAt: String = "",
    val updatedAt: String = ""
)

data class SupplyAssignment(
    val id: Int,
    val entityType: String,
    val entityId: Int,
    val stageCode: String,
    val stageLabel: String? = null
)

data class SupplyEvent(
    val id: Int,
    val entityType: String,
    val entityId: Int,
    val stageCode: String,
    val eventTime: String,
    val locationName: String? = null,
    val note: String? = null,
    val attachments: List<TraceAttachment> = emptyList()
)

data class TraceLookupResult(
    val token: String,
    val entityType: String,
    val lotCode: String? = null,
    val packageCode: String? = null,
    val produceType: String? = null,
    val originRegion: String? = null,
    val harvestDate: String? = null,
    val grade1Count: Int? = null,
    val grade2Count: Int? = null,
    val defectCount: Int? = null,
    val notes: String? = null,
    val publishStatus: String? = null,
    val events: List<TraceEvent> = emptyList(),
    val attachments: List<TraceAttachment> = emptyList()
)

data class ScanHistory(
    val id: Long,
    val token: String,
    val source: String,
    val status: String,
    val message: String?,
    val scannedAt: Long
)

enum class AppRoleDestination {
    ADMIN,
    PARTNER,
    SUPPLY,
    BUYER,
    UNKNOWN
}
