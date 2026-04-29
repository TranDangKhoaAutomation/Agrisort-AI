package com.example.agrisort_ai.features.dashboard.data.mapper

import com.example.agrisort_ai.BuildConfig
import com.example.agrisort_ai.core.network.asJsonObjectOrNull
import com.example.agrisort_ai.core.network.bool
import com.example.agrisort_ai.core.network.extractArray
import com.example.agrisort_ai.core.network.int
import com.example.agrisort_ai.core.network.string
import com.example.agrisort_ai.features.dashboard.domain.model.AdminCmsSection
import com.example.agrisort_ai.features.dashboard.domain.model.AdminDashboardSummary
import com.example.agrisort_ai.features.dashboard.domain.model.AdminSettings
import com.example.agrisort_ai.features.dashboard.domain.model.AdminUser
import com.example.agrisort_ai.features.dashboard.domain.model.ApiKeyItem
import com.example.agrisort_ai.features.dashboard.domain.model.BlogArticle
import com.example.agrisort_ai.features.dashboard.domain.model.CmsLocaleContent
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerLot
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerPackage
import com.example.agrisort_ai.features.dashboard.domain.model.ScanHistory
import com.example.agrisort_ai.features.dashboard.domain.model.SupplyAssignment
import com.example.agrisort_ai.features.dashboard.domain.model.SupplyEvent
import com.example.agrisort_ai.features.dashboard.domain.model.TraceAssignment
import com.example.agrisort_ai.features.dashboard.domain.model.TraceAttachment
import com.example.agrisort_ai.features.dashboard.domain.model.TraceEvent
import com.example.agrisort_ai.features.dashboard.domain.model.TraceLookupResult
import kotlinx.serialization.json.JsonArray
import kotlinx.serialization.json.JsonElement
import kotlinx.serialization.json.JsonObject

private fun JsonElement?.toDataObject(): JsonObject {
    val root = this.asJsonObjectOrNull() ?: return JsonObject(emptyMap())
    return when {
        root["data"] is JsonObject -> root["data"].asJsonObjectOrNull() ?: root
        else -> root
    }
}

private fun JsonElement?.toDataArray(keys: List<String>): JsonArray {
    val root = this.asJsonObjectOrNull()
    if (root != null) {
        if (root["data"] != null) {
            return root["data"].extractArray(keys)
        }
        return root.extractArray(keys)
    }
    return this.extractArray(keys)
}

private fun absoluteFileUrl(raw: String?): String? {
    val value = raw?.trim().orEmpty()
    if (value.isBlank()) {
        return null
    }
    if (value.startsWith("http://") || value.startsWith("https://")) {
        return value
    }
    return "${BuildConfig.API_BASE_URL.trimEnd('/')}/${value.trimStart('/')}"
}

private fun JsonObject.toTraceAttachments(): List<TraceAttachment> {
    return this["attachments"].extractArray(listOf("attachments")).mapNotNull { item ->
        val obj = item.asJsonObjectOrNull() ?: return@mapNotNull null
        TraceAttachment(
            id = obj.int("id") ?: 0,
            fileName = obj.string("file_name") ?: obj.string("original_name") ?: obj.string("name"),
            fileUrl = obj.string("file_url") ?: absoluteFileUrl(obj.string("file_path")) ?: obj.string("url")
        )
    }
}

private fun JsonElement?.toTraceEvents(): List<TraceEvent> {
    return this.extractArray(listOf("events", "items", "data")).mapNotNull { element ->
        val obj = element.asJsonObjectOrNull() ?: return@mapNotNull null
        TraceEvent(
            id = obj.int("id") ?: 0,
            stageCode = obj.string("stage_code").orEmpty(),
            eventTime = obj.string("event_time").orEmpty(),
            locationName = obj.string("location_name"),
            note = obj.string("note"),
            actorName = obj.string("actor_name") ?: obj.string("actor"),
            attachments = obj.toTraceAttachments()
        )
    }
}

private fun JsonObject.toCmsLocaleContent(): CmsLocaleContent {
    return CmsLocaleContent(
        title = string("title").orEmpty(),
        body = string("body").orEmpty(),
        image = string("image"),
        imageUrl = absoluteFileUrl(string("image"))
    )
}

private fun JsonObject.toPartnerLotOrNull(): PartnerLot? {
    val id = int("id") ?: return null
    return PartnerLot(
        id = id,
        partnerId = int("partner_id") ?: 0,
        lotCode = string("lot_code").orEmpty(),
        produceType = string("produce_type").orEmpty(),
        originRegion = string("origin_region"),
        harvestDate = string("harvest_date"),
        grade1Count = int("grade1_count"),
        grade2Count = int("grade2_count"),
        defectCount = int("defect_count"),
        notes = string("notes"),
        imagePath = string("image_path"),
        imageUrl = string("image_url") ?: absoluteFileUrl(string("image_path")),
        publishStatus = string("publish_status"),
        qrToken = string("qr_token"),
        traceUrl = string("trace_url"),
        qrPngUrl = string("qr_png_url"),
        qrSvgUrl = string("qr_svg_url"),
        createdAt = string("created_at").orEmpty(),
        updatedAt = string("updated_at").orEmpty()
    )
}

private fun JsonObject.toPartnerPackageOrNull(): PartnerPackage? {
    val id = int("id") ?: return null
    return PartnerPackage(
        id = id,
        lotId = int("lot_id") ?: 0,
        lotCode = string("lot_code"),
        produceType = string("produce_type"),
        packageCode = string("package_code").orEmpty(),
        packageLabel = string("package_label"),
        quantity = int("quantity") ?: 0,
        netWeightKg = string("net_weight_kg"),
        publishStatus = string("publish_status"),
        qrToken = string("qr_token"),
        traceUrl = string("trace_url"),
        qrPngUrl = string("qr_png_url"),
        qrSvgUrl = string("qr_svg_url"),
        createdAt = string("created_at").orEmpty(),
        updatedAt = string("updated_at").orEmpty()
    )
}

private fun JsonObject.toBlogArticleOrNull(): BlogArticle? {
    val id = int("id") ?: return null
    return BlogArticle(
        id = id,
        slug = string("slug").orEmpty(),
        titleVi = string("title_vi").orEmpty(),
        titleEn = string("title_en").orEmpty(),
        excerptVi = string("excerpt_vi").orEmpty(),
        excerptEn = string("excerpt_en").orEmpty(),
        contentVi = string("content_vi").orEmpty(),
        contentEn = string("content_en").orEmpty(),
        thumbnail = string("thumbnail"),
        thumbnailUrl = string("thumbnail_url") ?: absoluteFileUrl(string("thumbnail")),
        status = string("status").orEmpty(),
        publishedAt = string("published_at").orEmpty(),
        createdAt = string("created_at").orEmpty(),
        updatedAt = string("updated_at").orEmpty(),
        seoTitle = string("seo_title"),
        seoDescription = string("seo_description")
    )
}

fun JsonElement?.toTraceLookupResult(token: String): TraceLookupResult {
    val data = this.toDataObject()
    val lot = data["lot"].asJsonObjectOrNull()
    val packageObj = data["package"].asJsonObjectOrNull()
    val events = data["events"].toTraceEvents()
    val rootAttachments = data["attachments"].extractArray(listOf("attachments")).mapNotNull { item ->
        val obj = item.asJsonObjectOrNull() ?: return@mapNotNull null
        TraceAttachment(
            id = obj.int("id") ?: 0,
            fileName = obj.string("file_name"),
            fileUrl = obj.string("file_url") ?: absoluteFileUrl(obj.string("file_path")) ?: obj.string("url")
        )
    }

    return TraceLookupResult(
        token = data.string("token") ?: data.string("qr_token") ?: token,
        entityType = data.string("entity_type") ?: if (packageObj != null) "package" else "lot",
        lotCode = lot?.string("lot_code") ?: data.string("lot_code"),
        packageCode = packageObj?.string("package_code") ?: data.string("package_code"),
        produceType = lot?.string("produce_type") ?: data.string("produce_type"),
        originRegion = lot?.string("origin_region") ?: data.string("origin_region"),
        harvestDate = lot?.string("harvest_date") ?: data.string("harvest_date"),
        grade1Count = lot?.int("grade1_count") ?: data.int("grade1_count"),
        grade2Count = lot?.int("grade2_count") ?: data.int("grade2_count"),
        defectCount = lot?.int("defect_count") ?: data.int("defect_count"),
        notes = lot?.string("notes") ?: data.string("notes"),
        publishStatus = data.string("publish_status"),
        events = events,
        attachments = if (rootAttachments.isNotEmpty()) rootAttachments else events.flatMap { it.attachments }
    )
}

fun JsonElement?.toAdminDashboardSummary(): AdminDashboardSummary {
    val data = this.toDataObject()
    val counts = data["counts"].asJsonObjectOrNull() ?: data

    val pendingPartners = counts.int("pending_partners")
        ?: data["pending_partners"].extractArray(listOf("pending_partners", "items", "data")).size
    val activePartners = counts.int("active_partners") ?: 0
    val pendingSupplyUsers = counts.int("pending_supply_users")
        ?: data["pending_actors"].extractArray(listOf("pending_actors", "items", "data")).size
    val activeSupplyUsers = counts.int("active_supply_users") ?: 0

    val derivedTotalUsers = activePartners + pendingPartners + activeSupplyUsers + pendingSupplyUsers
    val derivedActiveUsers = activePartners + activeSupplyUsers

    return AdminDashboardSummary(
        totalUsers = counts.int("total_users") ?: data.int("total_users") ?: derivedTotalUsers,
        activeUsers = counts.int("active_users") ?: data.int("active_users") ?: derivedActiveUsers,
        totalLots = counts.int("total_lots") ?: data.int("total_lots") ?: 0,
        publishedLots = counts.int("published_lots") ?: data.int("published_lots") ?: 0,
        pendingPartners = pendingPartners,
        publishedBlogPosts = counts.int("published_blog_posts") ?: data.int("published_blog_posts") ?: 0,
        totalPackages = counts.int("total_packages") ?: data.int("total_packages") ?: 0,
        publishedPackages = counts.int("published_packages") ?: data.int("published_packages") ?: 0
    )
}

fun JsonElement?.toAdminUsers(): List<AdminUser> {
    return this.toDataArray(listOf("users", "items", "data")).mapNotNull { item ->
        val obj = item.asJsonObjectOrNull() ?: return@mapNotNull null
        AdminUser(
            id = obj.int("id") ?: return@mapNotNull null,
            fullName = obj.string("full_name") ?: obj.string("name").orEmpty(),
            email = obj.string("email").orEmpty(),
            role = obj.string("role").orEmpty(),
            status = obj.string("status").orEmpty(),
            organizationName = obj.string("organization_name"),
            representativeName = obj.string("representative_name"),
            phone = obj.string("phone"),
            address = obj.string("address"),
            region = obj.string("region"),
            taxCode = obj.string("tax_code")
        )
    }
}

fun JsonElement?.toAdminSettings(): AdminSettings {
    val data = this.toDataObject()
    val autoPublishRaw = data.string("auto_publish_lot")
    val autoPublish = autoPublishRaw == "1" || autoPublishRaw == "true" || data.bool("auto_publish_lot") == true
    return AdminSettings(
        autoPublishLot = autoPublish,
        smtpHost = data.string("smtp_host").orEmpty(),
        smtpPort = data.string("smtp_port").orEmpty(),
        smtpUser = data.string("smtp_user").orEmpty(),
        smtpPass = data.string("smtp_pass").orEmpty(),
        smtpFrom = data.string("smtp_from").orEmpty(),
        qrBaseUrl = data.string("qr_base_url").orEmpty(),
        googleTranslateApiKey = data.string("google_translate_api_key").orEmpty()
    )
}

fun JsonElement?.toAdminCmsSection(fallbackSectionKey: String): AdminCmsSection {
    val data = this.toDataObject()
    val sectionKey = data.string("section_key") ?: fallbackSectionKey
    val vi = data["vi"].asJsonObjectOrNull()?.toCmsLocaleContent() ?: CmsLocaleContent()
    val en = data["en"].asJsonObjectOrNull()?.toCmsLocaleContent() ?: CmsLocaleContent()
    return AdminCmsSection(
        sectionKey = sectionKey,
        vi = vi,
        en = en
    )
}

fun JsonElement?.toBlogArticles(): List<BlogArticle> {
    return this.toDataArray(listOf("posts", "items", "data")).mapNotNull { item ->
        item.asJsonObjectOrNull()?.toBlogArticleOrNull()
    }
}

fun JsonElement?.toBlogArticle(): BlogArticle? {
    val data = this.toDataObject()
    val candidate = data["post"].asJsonObjectOrNull() ?: data
    return candidate.toBlogArticleOrNull()
}

fun JsonElement?.toApiKeys(): List<ApiKeyItem> {
    return this.toDataArray(listOf("items", "data")).mapNotNull { item ->
        val obj = item.asJsonObjectOrNull() ?: return@mapNotNull null
        ApiKeyItem(
            id = obj.int("id") ?: 0,
            name = obj.string("name").orEmpty(),
            status = obj.string("status").orEmpty(),
            lastUsedAt = obj.string("last_used_at").orEmpty(),
            createdAt = obj.string("created_at").orEmpty()
        )
    }
}

fun JsonElement?.toApiKey(): ApiKeyItem? {
    val data = this.toDataObject()
    return ApiKeyItem(
        id = data.int("id") ?: 0,
        name = data.string("name").orEmpty(),
        status = (data.string("status") ?: "active").ifBlank { "active" },
        apiKey = data.string("api_key"),
        note = data.string("note"),
        lastUsedAt = data.string("last_used_at").orEmpty(),
        createdAt = data.string("created_at").orEmpty()
    )
}

fun JsonElement?.toTraceAssignments(): List<TraceAssignment> {
    return this.toDataArray(listOf("assignments", "items", "data")).mapNotNull { item ->
        val obj = item.asJsonObjectOrNull() ?: return@mapNotNull null
        TraceAssignment(
            id = obj.int("id") ?: 0,
            entityType = obj.string("entity_type").orEmpty(),
            entityId = obj.int("entity_id") ?: 0,
            stageCode = obj.string("stage_code").orEmpty(),
            actorUserId = obj.int("actor_user_id"),
            actorName = obj.string("actor_name")
        )
    }
}

fun JsonElement?.toTraceEventsList(): List<TraceEvent> {
    return this.toDataArray(listOf("events", "items", "data")).mapNotNull { item ->
        val obj = item.asJsonObjectOrNull() ?: return@mapNotNull null
        TraceEvent(
            id = obj.int("id") ?: 0,
            stageCode = obj.string("stage_code").orEmpty(),
            eventTime = obj.string("event_time").orEmpty(),
            locationName = obj.string("location_name"),
            note = obj.string("note"),
            actorName = obj.string("actor_name"),
            attachments = obj.toTraceAttachments()
        )
    }
}

fun JsonElement?.toPartnerLots(): List<PartnerLot> {
    return this.toDataArray(listOf("lots", "items", "data")).mapNotNull { item ->
        item.asJsonObjectOrNull()?.toPartnerLotOrNull()
    }
}

fun JsonElement?.toPartnerLot(): PartnerLot? {
    val data = this.toDataObject()
    val candidate = data["lot"].asJsonObjectOrNull() ?: data
    return candidate.toPartnerLotOrNull()
}

fun JsonElement?.toPartnerPackages(): List<PartnerPackage> {
    return this.toDataArray(listOf("packages", "items", "data")).mapNotNull { item ->
        item.asJsonObjectOrNull()?.toPartnerPackageOrNull()
    }
}

fun JsonElement?.toPartnerPackage(): PartnerPackage? {
    val data = this.toDataObject()
    val candidate = data["package"].asJsonObjectOrNull() ?: data
    return candidate.toPartnerPackageOrNull()
}

fun JsonElement?.toSupplyAssignments(): List<SupplyAssignment> {
    return this.toDataArray(listOf("assignments", "items", "data")).mapNotNull { item ->
        val obj = item.asJsonObjectOrNull() ?: return@mapNotNull null
        SupplyAssignment(
            id = obj.int("id") ?: 0,
            entityType = obj.string("entity_type").orEmpty(),
            entityId = obj.int("entity_id") ?: 0,
            stageCode = obj.string("stage_code").orEmpty(),
            stageLabel = obj.string("stage_label")
        )
    }
}

fun JsonElement?.toSupplyEvents(): List<SupplyEvent> {
    return this.toDataArray(listOf("events", "items", "data")).mapNotNull { item ->
        val obj = item.asJsonObjectOrNull() ?: return@mapNotNull null
        SupplyEvent(
            id = obj.int("id") ?: 0,
            entityType = obj.string("entity_type").orEmpty(),
            entityId = obj.int("entity_id") ?: 0,
            stageCode = obj.string("stage_code").orEmpty(),
            eventTime = obj.string("event_time").orEmpty(),
            locationName = obj.string("location_name"),
            note = obj.string("note"),
            attachments = obj.toTraceAttachments()
        )
    }
}

fun com.example.agrisort_ai.core.local.ScanHistoryEntity.toDomain(): ScanHistory {
    return ScanHistory(
        id = id,
        token = token,
        source = source,
        status = status,
        message = message,
        scannedAt = scannedAt
    )
}
