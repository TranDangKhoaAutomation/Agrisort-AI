package com.example.agrisort_ai.features.dashboard.data.repository

import com.example.agrisort_ai.core.local.ScanHistoryDao
import com.example.agrisort_ai.core.local.ScanHistoryEntity
import com.example.agrisort_ai.core.network.Resource
import com.example.agrisort_ai.core.network.UploadPartFactory
import com.example.agrisort_ai.features.dashboard.data.mapper.toAdminCmsSection
import com.example.agrisort_ai.features.dashboard.data.mapper.toAdminDashboardSummary
import com.example.agrisort_ai.features.dashboard.data.mapper.toAdminSettings
import com.example.agrisort_ai.features.dashboard.data.mapper.toAdminUsers
import com.example.agrisort_ai.features.dashboard.data.mapper.toApiKey
import com.example.agrisort_ai.features.dashboard.data.mapper.toApiKeys
import com.example.agrisort_ai.features.dashboard.data.mapper.toBlogArticle
import com.example.agrisort_ai.features.dashboard.data.mapper.toBlogArticles
import com.example.agrisort_ai.features.dashboard.data.mapper.toDomain
import com.example.agrisort_ai.features.dashboard.data.mapper.toPartnerLot
import com.example.agrisort_ai.features.dashboard.data.mapper.toPartnerLots
import com.example.agrisort_ai.features.dashboard.data.mapper.toPartnerPackage
import com.example.agrisort_ai.features.dashboard.data.mapper.toPartnerPackages
import com.example.agrisort_ai.features.dashboard.data.mapper.toSupplyAssignments
import com.example.agrisort_ai.features.dashboard.data.mapper.toSupplyEvents
import com.example.agrisort_ai.features.dashboard.data.mapper.toTraceAssignments
import com.example.agrisort_ai.features.dashboard.data.mapper.toTraceEventsList
import com.example.agrisort_ai.features.dashboard.data.mapper.toTraceLookupResult
import com.example.agrisort_ai.features.dashboard.data.remote.AdminSettingsRequest
import com.example.agrisort_ai.features.dashboard.data.remote.AdminUserStatusRequest
import com.example.agrisort_ai.features.dashboard.data.remote.CreateApiKeyRequest
import com.example.agrisort_ai.features.dashboard.data.remote.CreateLotRequest
import com.example.agrisort_ai.features.dashboard.data.remote.CreatePackageRequest
import com.example.agrisort_ai.features.dashboard.data.remote.CreateSupplyEventRequest
import com.example.agrisort_ai.features.dashboard.data.remote.DashboardApi
import com.example.agrisort_ai.features.dashboard.data.remote.LotPublishRequest
import com.example.agrisort_ai.features.dashboard.data.remote.SaveBlogRequest
import com.example.agrisort_ai.features.dashboard.data.remote.SaveCmsRequest
import com.example.agrisort_ai.features.dashboard.data.remote.TraceAssignmentRequest
import com.example.agrisort_ai.features.dashboard.data.remote.UpdateLotRequest
import com.example.agrisort_ai.features.dashboard.data.remote.UpdatePackageRequest
import com.example.agrisort_ai.features.dashboard.data.remote.UpdateSupplyEventRequest
import com.example.agrisort_ai.features.dashboard.domain.model.AdminCmsSection
import com.example.agrisort_ai.features.dashboard.domain.model.AdminDashboardSummary
import com.example.agrisort_ai.features.dashboard.domain.model.AdminSettings
import com.example.agrisort_ai.features.dashboard.domain.model.AdminUser
import com.example.agrisort_ai.features.dashboard.domain.model.ApiKeyItem
import com.example.agrisort_ai.features.dashboard.domain.model.BlogArticle
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerLot
import com.example.agrisort_ai.features.dashboard.domain.model.PartnerPackage
import com.example.agrisort_ai.features.dashboard.domain.model.ScanHistory
import com.example.agrisort_ai.features.dashboard.domain.model.SupplyAssignment
import com.example.agrisort_ai.features.dashboard.domain.model.SupplyEvent
import com.example.agrisort_ai.features.dashboard.domain.model.TraceAssignment
import com.example.agrisort_ai.features.dashboard.domain.model.TraceEvent
import com.example.agrisort_ai.features.dashboard.domain.model.TraceLookupResult
import com.example.agrisort_ai.features.dashboard.domain.repository.DashboardRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.map
import kotlinx.serialization.SerializationException
import kotlinx.serialization.json.JsonElement
import okhttp3.RequestBody
import org.json.JSONObject
import retrofit2.HttpException
import java.io.IOException
import javax.inject.Inject

class DashboardRepositoryImpl @Inject constructor(
    private val api: DashboardApi,
    private val scanHistoryDao: ScanHistoryDao,
    private val multipartPartFactory: UploadPartFactory
) : DashboardRepository {

    override fun blogIndex(limit: Int): Flow<Resource<List<BlogArticle>>> = objectRequest(
        mapper = { it.toBlogArticles() },
        block = { api.blogIndex(limit) }
    )

    override fun blogShow(slug: String): Flow<Resource<BlogArticle>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.blogShow(slug)
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }

            val article = response.data.toBlogArticle()
            if (article == null) {
                emit(Resource.Error("Không đọc được dữ liệu bài viết từ máy chủ."))
                return@flow
            }

            emit(Resource.Success(article))
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun traceLookup(token: String, source: String): Flow<Resource<TraceLookupResult>> = objectRequest(
        mapper = { it.toTraceLookupResult(token) },
        block = { api.traceLookup(token) }
    )

    override fun traceShow(token: String, source: String): Flow<Resource<TraceLookupResult>> = objectRequest(
        mapper = { it.toTraceLookupResult(token) },
        block = { api.traceShow(token) }
    )

    override fun adminDashboard(): Flow<Resource<AdminDashboardSummary>> = objectRequest(
        mapper = { it.toAdminDashboardSummary() },
        block = { api.adminDashboard() }
    )

    override fun adminUsers(
        query: String?,
        role: String?,
        status: String?,
        limit: Int
    ): Flow<Resource<List<AdminUser>>> = objectRequest(
        mapper = { it.toAdminUsers() },
        block = { api.adminUsers(query, role, status, limit) }
    )

    override fun updateAdminUserStatus(userId: Int, status: String): Flow<Resource<String>> = messageRequest {
        api.updateAdminUserStatus(userId, AdminUserStatusRequest(status))
    }

    override fun approvePartner(userId: Int): Flow<Resource<String>> = messageRequest {
        api.approvePartner(userId)
    }

    override fun adminSettings(): Flow<Resource<AdminSettings>> = objectRequest(
        mapper = { it.toAdminSettings() },
        block = { api.adminSettings() }
    )

    override fun updateAdminSettings(request: AdminSettingsRequest): Flow<Resource<AdminSettings>> = objectRequest(
        mapper = { it.toAdminSettings() },
        block = { api.updateAdminSettings(request) }
    )

    override fun adminCms(sectionKey: String): Flow<Resource<AdminCmsSection>> = objectRequest(
        mapper = { it.toAdminCmsSection(sectionKey) },
        block = { api.adminCms(sectionKey) }
    )

    override fun saveAdminCms(sectionKey: String, request: SaveCmsRequest): Flow<Resource<AdminCmsSection>> = flow {
        emit(Resource.Loading)
        try {
            val parts = buildCmsParts(request)
            val imagePart = request.imageUri?.let { multipartPartFactory.filePart("image", it) }
            val response = api.saveAdminCms(sectionKey, parts, imagePart)
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }
            emit(Resource.Success(response.data.toAdminCmsSection(sectionKey)))
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun adminBlog(): Flow<Resource<List<BlogArticle>>> = objectRequest(
        mapper = { it.toBlogArticles() },
        block = { api.adminBlog() }
    )

    override fun saveAdminBlog(request: SaveBlogRequest): Flow<Resource<String>> = flow {
        emit(Resource.Loading)
        try {
            val parts = buildBlogParts(request)
            val thumbnailPart = request.thumbnailUri?.let { multipartPartFactory.filePart("thumbnail", it) }
            val response = api.saveAdminBlog(parts, thumbnailPart)
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }
            emit(Resource.Success(response.message))
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun adminApiKeys(): Flow<Resource<List<ApiKeyItem>>> = objectRequest(
        mapper = { it.toApiKeys() },
        block = { api.adminApiKeys() }
    )

    override fun createAdminApiKey(name: String): Flow<Resource<ApiKeyItem>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.createAdminApiKey(CreateApiKeyRequest(name.ifBlank { "Tích hợp ứng dụng" }))
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }
            val apiKey = response.data.toApiKey()
            if (apiKey == null) {
                emit(Resource.Error("Máy chủ đã tạo API key nhưng không trả về dữ liệu đầy đủ."))
                return@flow
            }
            emit(Resource.Success(apiKey))
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun setAdminLotPublish(lotId: Int, publishStatus: String): Flow<Resource<String>> = messageRequest {
        api.setAdminLotPublish(lotId, LotPublishRequest(publishStatus))
    }

    override fun adminTraceAssignments(): Flow<Resource<List<TraceAssignment>>> = objectRequest(
        mapper = { it.toTraceAssignments() },
        block = { api.adminTraceAssignments() }
    )

    override fun saveTraceAssignment(request: TraceAssignmentRequest): Flow<Resource<String>> = messageRequest {
        api.saveTraceAssignment(request)
    }

    override fun deleteTraceAssignment(assignmentId: Int): Flow<Resource<String>> = messageRequest {
        api.deleteTraceAssignment(assignmentId)
    }

    override fun adminTraceEvents(limit: Int): Flow<Resource<List<TraceEvent>>> = objectRequest(
        mapper = { it.toTraceEventsList() },
        block = { api.adminTraceEvents(limit) }
    )

    override fun partnerLots(): Flow<Resource<List<PartnerLot>>> = objectRequest(
        mapper = { it.toPartnerLots() },
        block = { api.partnerLots() }
    )

    override fun partnerLotDetail(lotId: Int): Flow<Resource<PartnerLot>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.partnerLotDetail(lotId)
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }

            val lot = response.data.toPartnerLot()
            if (lot == null) {
                emit(Resource.Error("Không đọc được dữ liệu lô hàng từ máy chủ."))
                return@flow
            }

            emit(Resource.Success(lot))
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun createPartnerLot(request: CreateLotRequest): Flow<Resource<String>> = flow {
        emit(Resource.Loading)
        val imageUri = request.imageUri
        if (imageUri == null) {
            emit(Resource.Error("Vui lòng chọn ảnh sản phẩm cho lô hàng trước khi lưu."))
            return@flow
        }

        try {
            val response = api.createPartnerLot(
                parts = buildLotParts(
                    lotCode = request.lotCode,
                    produceType = request.produceType,
                    originRegion = request.originRegion,
                    harvestDate = request.harvestDate,
                    grade1Count = request.grade1Count,
                    grade2Count = request.grade2Count,
                    defectCount = request.defectCount,
                    notes = request.notes
                ),
                image = multipartPartFactory.filePart("image", imageUri)
            )
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }
            emit(Resource.Success(response.message))
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun updatePartnerLot(lotId: Int, request: UpdateLotRequest): Flow<Resource<String>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.updatePartnerLot(
                lotId = lotId,
                parts = buildLotParts(
                    lotCode = request.lotCode,
                    produceType = request.produceType,
                    originRegion = request.originRegion,
                    harvestDate = request.harvestDate,
                    grade1Count = request.grade1Count,
                    grade2Count = request.grade2Count,
                    defectCount = request.defectCount,
                    notes = request.notes,
                    methodOverride = "PUT"
                ),
                image = request.imageUri?.let { multipartPartFactory.filePart("image", it) }
            )
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }
            emit(Resource.Success(response.message))
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun deletePartnerLot(lotId: Int): Flow<Resource<String>> = messageRequest {
        api.deletePartnerLot(lotId)
    }

    override fun regeneratePartnerLotQr(lotId: Int): Flow<Resource<String>> = messageRequest {
        api.regeneratePartnerLotQr(lotId)
    }

    override fun partnerPackages(): Flow<Resource<List<PartnerPackage>>> = objectRequest(
        mapper = { it.toPartnerPackages() },
        block = { api.partnerPackages() }
    )

    override fun createPartnerPackage(request: CreatePackageRequest): Flow<Resource<String>> = messageRequest {
        api.createPartnerPackage(request)
    }

    override fun updatePartnerPackage(packageId: Int, request: UpdatePackageRequest): Flow<Resource<String>> = messageRequest {
        api.updatePartnerPackage(packageId, request)
    }

    override fun deletePartnerPackage(packageId: Int): Flow<Resource<String>> = messageRequest {
        api.deletePartnerPackage(packageId)
    }

    override fun regeneratePartnerPackageQr(packageId: Int): Flow<Resource<String>> = messageRequest {
        api.regeneratePartnerPackageQr(packageId)
    }

    override fun supplyAssignments(): Flow<Resource<List<SupplyAssignment>>> = objectRequest(
        mapper = { it.toSupplyAssignments() },
        block = { api.supplyAssignments() }
    )

    override fun supplyEvents(): Flow<Resource<List<SupplyEvent>>> = objectRequest(
        mapper = { it.toSupplyEvents() },
        block = { api.supplyEvents() }
    )

    override fun createSupplyEvent(request: CreateSupplyEventRequest): Flow<Resource<String>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.createSupplyEvent(
                parts = buildSupplyEventParts(
                    entityType = request.entityType,
                    entityId = request.entityId,
                    stageCode = request.stageCode,
                    eventTime = request.eventTime,
                    locationName = request.locationName,
                    note = request.note
                ),
                attachments = multipartPartFactory.fileParts("attachments[]", request.attachmentUris)
            )
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }
            emit(Resource.Success(response.message))
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun updateSupplyEvent(eventId: Int, request: UpdateSupplyEventRequest): Flow<Resource<String>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.updateSupplyEvent(
                eventId = eventId,
                parts = buildSupplyEventParts(
                    eventTime = request.eventTime,
                    locationName = request.locationName,
                    note = request.note,
                    methodOverride = "PUT"
                ),
                attachments = multipartPartFactory.fileParts("attachments[]", request.attachmentUris)
            )
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }
            emit(Resource.Success(response.message))
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun observeScanHistory(limit: Int): Flow<List<ScanHistory>> {
        return scanHistoryDao.observeRecent(limit).map { entities ->
            entities.map { it.toDomain() }
        }
    }

    override suspend fun saveScanHistory(
        token: String,
        source: String,
        status: String,
        message: String?,
        deduplicateByToken: Boolean
    ) {
        if (deduplicateByToken) {
            scanHistoryDao.deleteByToken(token)
        }
        scanHistoryDao.insert(
            ScanHistoryEntity(
                token = token,
                source = source,
                status = status,
                message = message,
                scannedAt = System.currentTimeMillis()
            )
        )
    }

    override suspend fun clearScanHistory() {
        scanHistoryDao.clearAll()
    }

    private fun <T> objectRequest(
        mapper: (JsonElement?) -> T,
        block: suspend () -> com.example.agrisort_ai.core.network.ApiEnvelope<JsonElement>
    ): Flow<Resource<T>> = flow {
        emit(Resource.Loading)
        try {
            val response = block()
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }
            emit(Resource.Success(mapper(response.data)))
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    private fun messageRequest(
        block: suspend () -> com.example.agrisort_ai.core.network.ApiEnvelope<JsonElement>
    ): Flow<Resource<String>> = flow {
        emit(Resource.Loading)
        try {
            val response = block()
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }
            emit(Resource.Success(response.message))
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    private fun buildLotParts(
        lotCode: String,
        produceType: String,
        originRegion: String,
        harvestDate: String,
        grade1Count: Int,
        grade2Count: Int,
        defectCount: Int,
        notes: String?,
        methodOverride: String? = null
    ): Map<String, RequestBody> {
        return buildMap {
            methodOverride?.let { put("_method", multipartPartFactory.textPart(it)) }
            put("lot_code", multipartPartFactory.textPart(lotCode.trim()))
            put("produce_type", multipartPartFactory.textPart(produceType.trim()))
            put("origin_region", multipartPartFactory.textPart(originRegion.trim()))
            put("harvest_date", multipartPartFactory.textPart(harvestDate.trim()))
            put("grade1_count", multipartPartFactory.textPart(grade1Count.toString()))
            put("grade2_count", multipartPartFactory.textPart(grade2Count.toString()))
            put("defect_count", multipartPartFactory.textPart(defectCount.toString()))
            put("notes", multipartPartFactory.textPart(notes?.trim().orEmpty()))
        }
    }

    private fun buildSupplyEventParts(
        entityType: String? = null,
        entityId: Int? = null,
        stageCode: String? = null,
        eventTime: String,
        locationName: String,
        note: String?,
        methodOverride: String? = null
    ): Map<String, RequestBody> {
        return buildMap {
            methodOverride?.let { put("_method", multipartPartFactory.textPart(it)) }
            entityType?.let { put("entity_type", multipartPartFactory.textPart(it.trim())) }
            entityId?.let { put("entity_id", multipartPartFactory.textPart(it.toString())) }
            stageCode?.let { put("stage_code", multipartPartFactory.textPart(it.trim())) }
            put("event_time", multipartPartFactory.textPart(eventTime.trim()))
            put("location_name", multipartPartFactory.textPart(locationName.trim()))
            put("note", multipartPartFactory.textPart(note?.trim().orEmpty()))
        }
    }

    private fun buildCmsParts(request: SaveCmsRequest): Map<String, RequestBody> {
        return buildMap {
            put("title_vi", multipartPartFactory.textPart(request.titleVi.trim()))
            put("body_vi", multipartPartFactory.textPart(request.bodyVi.trim()))
            put("title_en", multipartPartFactory.textPart(request.titleEn.trim()))
            put("body_en", multipartPartFactory.textPart(request.bodyEn.trim()))
            put("auto_translate_en", multipartPartFactory.textPart(if (request.autoTranslateEn) "1" else "0"))
        }
    }

    private fun buildBlogParts(request: SaveBlogRequest): Map<String, RequestBody> {
        return buildMap {
            request.id?.let { put("id", multipartPartFactory.textPart(it.toString())) }
            put("slug", multipartPartFactory.textPart(request.slug.trim()))
            put("title_vi", multipartPartFactory.textPart(request.titleVi.trim()))
            put("content_vi", multipartPartFactory.textPart(request.contentVi.trim()))
            put("title_en", multipartPartFactory.textPart(request.titleEn.trim()))
            put("content_en", multipartPartFactory.textPart(request.contentEn.trim()))
            put("excerpt_vi", multipartPartFactory.textPart(request.excerptVi.trim()))
            put("excerpt_en", multipartPartFactory.textPart(request.excerptEn.trim()))
            put("seo_title", multipartPartFactory.textPart(request.seoTitle.trim()))
            put("seo_description", multipartPartFactory.textPart(request.seoDescription.trim()))
            put("status", multipartPartFactory.textPart(request.status.trim()))
            put("auto_translate_en", multipartPartFactory.textPart(if (request.autoTranslateEn) "1" else "0"))
        }
    }

    private fun handleError(e: Exception): Resource.Error {
        return when (e) {
            is HttpException -> {
                val serverMessage = extractServerMessage(e)
                val message = when (e.code()) {
                    401 -> serverMessage ?: "Phiên đăng nhập đã hết hạn."
                    403 -> serverMessage ?: "Bạn không có quyền thực hiện thao tác này."
                    404 -> serverMessage ?: "Không tìm thấy dữ liệu yêu cầu."
                    419 -> serverMessage ?: "Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại."
                    422 -> serverMessage ?: "Dữ liệu gửi lên chưa hợp lệ."
                    429 -> serverMessage ?: "Yêu cầu đang được gửi quá nhanh. Vui lòng thử lại sau."
                    500 -> serverMessage ?: "Máy chủ đang gặp sự cố tạm thời."
                    else -> serverMessage ?: "Không thể đồng bộ dữ liệu từ máy chủ (${e.code()})."
                }
                Resource.Error(message, e.code())
            }

            is IOException -> Resource.Error("Không thể kết nối mạng. Vui lòng kiểm tra kết nối của bạn.")
            is SecurityException -> Resource.Error("Không thể đọc tệp đã chọn trên thiết bị.")
            is SerializationException,
            is IllegalArgumentException -> Resource.Error("Dữ liệu trả về từ máy chủ chưa đúng định dạng mong đợi.")
            else -> {
                val technicalMessage = e.message.orEmpty()
                if ("json" in technicalMessage.lowercase() || "serialization" in technicalMessage.lowercase()) {
                    Resource.Error("Dữ liệu trả về từ máy chủ chưa đúng định dạng mong đợi.")
                } else {
                    Resource.Error("Không thể hoàn tất yêu cầu lúc này. Vui lòng thử lại.")
                }
            }
        }
    }

    private fun extractServerMessage(e: HttpException): String? {
        val rawBody = try {
            e.response()?.errorBody()?.string()
        } catch (_: Exception) {
            null
        }

        if (rawBody.isNullOrBlank()) return null

        return try {
            val json = JSONObject(rawBody)
            val message = json.optString("message")
            if (message.isNotBlank()) {
                message
            } else {
                val error = json.optString("error")
                if (error.isNotBlank()) error else null
            }
        } catch (_: Exception) {
            null
        }
    }
}
