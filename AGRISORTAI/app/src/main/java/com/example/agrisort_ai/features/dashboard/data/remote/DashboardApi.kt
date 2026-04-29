package com.example.agrisort_ai.features.dashboard.data.remote

import android.net.Uri
import com.example.agrisort_ai.core.network.ApiEnvelope
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonElement
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Part
import retrofit2.http.PartMap
import retrofit2.http.Path
import retrofit2.http.Query

@Serializable
data class AdminUserStatusRequest(
    val status: String
)

@Serializable
data class TraceAssignmentRequest(
    @SerialName("entity_type") val entityType: String,
    @SerialName("entity_id") val entityId: Int,
    @SerialName("stage_code") val stageCode: String,
    @SerialName("actor_user_id") val actorUserId: Int
)

data class CreateLotRequest(
    @SerialName("lot_code") val lotCode: String,
    @SerialName("produce_type") val produceType: String,
    @SerialName("origin_region") val originRegion: String,
    @SerialName("harvest_date") val harvestDate: String,
    @SerialName("grade1_count") val grade1Count: Int,
    @SerialName("grade2_count") val grade2Count: Int,
    @SerialName("defect_count") val defectCount: Int,
    val notes: String? = null,
    val imageUri: Uri? = null
)

data class UpdateLotRequest(
    @SerialName("lot_code") val lotCode: String,
    @SerialName("produce_type") val produceType: String,
    @SerialName("origin_region") val originRegion: String,
    @SerialName("harvest_date") val harvestDate: String,
    @SerialName("grade1_count") val grade1Count: Int,
    @SerialName("grade2_count") val grade2Count: Int,
    @SerialName("defect_count") val defectCount: Int,
    val notes: String? = null,
    val imageUri: Uri? = null
)

@Serializable
data class CreatePackageRequest(
    @SerialName("lot_id") val lotId: Int,
    @SerialName("package_code") val packageCode: String,
    @SerialName("package_label") val packageLabel: String,
    val quantity: Int,
    @SerialName("net_weight_kg") val netWeightKg: String
)

@Serializable
data class UpdatePackageRequest(
    @SerialName("package_code") val packageCode: String,
    @SerialName("package_label") val packageLabel: String,
    val quantity: Int,
    @SerialName("net_weight_kg") val netWeightKg: String,
    @SerialName("publish_status") val publishStatus: String
)

data class CreateSupplyEventRequest(
    @SerialName("entity_type") val entityType: String,
    @SerialName("entity_id") val entityId: Int,
    @SerialName("stage_code") val stageCode: String,
    @SerialName("event_time") val eventTime: String,
    @SerialName("location_name") val locationName: String,
    val note: String? = null,
    val attachmentUris: List<Uri> = emptyList()
)

data class UpdateSupplyEventRequest(
    @SerialName("event_time") val eventTime: String,
    @SerialName("location_name") val locationName: String,
    val note: String? = null,
    val attachmentUris: List<Uri> = emptyList()
)

@Serializable
data class AdminSettingsRequest(
    @SerialName("auto_publish_lot") val autoPublishLot: String,
    @SerialName("smtp_host") val smtpHost: String,
    @SerialName("smtp_port") val smtpPort: String,
    @SerialName("smtp_user") val smtpUser: String,
    @SerialName("smtp_pass") val smtpPass: String,
    @SerialName("smtp_from") val smtpFrom: String,
    @SerialName("qr_base_url") val qrBaseUrl: String,
    @SerialName("google_translate_api_key") val googleTranslateApiKey: String
)

data class SaveCmsRequest(
    @SerialName("title_vi") val titleVi: String = "",
    @SerialName("body_vi") val bodyVi: String = "",
    @SerialName("title_en") val titleEn: String = "",
    @SerialName("body_en") val bodyEn: String = "",
    @SerialName("auto_translate_en") val autoTranslateEn: Boolean = false,
    val imageUri: Uri? = null
)

data class SaveBlogRequest(
    val id: Int? = null,
    val slug: String = "",
    @SerialName("title_vi") val titleVi: String,
    @SerialName("content_vi") val contentVi: String,
    @SerialName("title_en") val titleEn: String = "",
    @SerialName("content_en") val contentEn: String = "",
    @SerialName("excerpt_vi") val excerptVi: String = "",
    @SerialName("excerpt_en") val excerptEn: String = "",
    @SerialName("seo_title") val seoTitle: String = "",
    @SerialName("seo_description") val seoDescription: String = "",
    val status: String = "draft",
    @SerialName("auto_translate_en") val autoTranslateEn: Boolean = false,
    val thumbnailUri: Uri? = null
)

@Serializable
data class CreateApiKeyRequest(
    val name: String
)

@Serializable
data class LotPublishRequest(
    @SerialName("publish_status") val publishStatus: String
)

interface DashboardApi {
    @GET("blog")
    suspend fun blogIndex(
        @Query("limit") limit: Int = 20
    ): ApiEnvelope<JsonElement>

    @GET("blog/{slug}")
    suspend fun blogShow(@Path("slug") slug: String): ApiEnvelope<JsonElement>

    @GET("trace/lookup")
    suspend fun traceLookup(@Query("token") token: String): ApiEnvelope<JsonElement>

    @GET("trace/{qrToken}")
    suspend fun traceShow(@Path("qrToken") qrToken: String): ApiEnvelope<JsonElement>

    @GET("admin/dashboard")
    suspend fun adminDashboard(): ApiEnvelope<JsonElement>

    @GET("admin/users")
    suspend fun adminUsers(
        @Query("q") query: String? = null,
        @Query("role") role: String? = null,
        @Query("status") status: String? = null,
        @Query("limit") limit: Int = 20
    ): ApiEnvelope<JsonElement>

    @POST("admin/users/{id}/status")
    suspend fun updateAdminUserStatus(
        @Path("id") userId: Int,
        @Body request: AdminUserStatusRequest
    ): ApiEnvelope<JsonElement>

    @POST("admin/partners/{id}/approve")
    suspend fun approvePartner(
        @Path("id") userId: Int
    ): ApiEnvelope<JsonElement>

    @GET("admin/settings")
    suspend fun adminSettings(): ApiEnvelope<JsonElement>

    @POST("admin/settings")
    suspend fun updateAdminSettings(
        @Body request: AdminSettingsRequest
    ): ApiEnvelope<JsonElement>

    @GET("admin/cms/{sectionKey}")
    suspend fun adminCms(@Path("sectionKey") sectionKey: String): ApiEnvelope<JsonElement>

    @Multipart
    @POST("admin/cms/{sectionKey}")
    suspend fun saveAdminCms(
        @Path("sectionKey") sectionKey: String,
        @PartMap parts: Map<String, @JvmSuppressWildcards RequestBody>,
        @Part image: MultipartBody.Part? = null
    ): ApiEnvelope<JsonElement>

    @GET("admin/blog")
    suspend fun adminBlog(): ApiEnvelope<JsonElement>

    @Multipart
    @POST("admin/blog")
    suspend fun saveAdminBlog(
        @PartMap parts: Map<String, @JvmSuppressWildcards RequestBody>,
        @Part thumbnail: MultipartBody.Part? = null
    ): ApiEnvelope<JsonElement>

    @GET("admin/api-keys")
    suspend fun adminApiKeys(): ApiEnvelope<JsonElement>

    @POST("admin/api-keys")
    suspend fun createAdminApiKey(
        @Body request: CreateApiKeyRequest
    ): ApiEnvelope<JsonElement>

    @POST("admin/lots/{id}/publish")
    suspend fun setAdminLotPublish(
        @Path("id") lotId: Int,
        @Body request: LotPublishRequest
    ): ApiEnvelope<JsonElement>

    @GET("admin/trace/assignments")
    suspend fun adminTraceAssignments(): ApiEnvelope<JsonElement>

    @POST("admin/trace/assignments")
    suspend fun saveTraceAssignment(
        @Body request: TraceAssignmentRequest
    ): ApiEnvelope<JsonElement>

    @DELETE("admin/trace/assignments/{id}")
    suspend fun deleteTraceAssignment(
        @Path("id") assignmentId: Int
    ): ApiEnvelope<JsonElement>

    @GET("admin/trace/events")
    suspend fun adminTraceEvents(
        @Query("limit") limit: Int = 200
    ): ApiEnvelope<JsonElement>

    @GET("partner/lots")
    suspend fun partnerLots(): ApiEnvelope<JsonElement>

    @GET("partner/lots/{id}")
    suspend fun partnerLotDetail(
        @Path("id") lotId: Int
    ): ApiEnvelope<JsonElement>

    @Multipart
    @POST("partner/lots")
    suspend fun createPartnerLot(
        @PartMap parts: Map<String, @JvmSuppressWildcards RequestBody>,
        @Part image: MultipartBody.Part
    ): ApiEnvelope<JsonElement>

    @Multipart
    @POST("partner/lots/{id}")
    suspend fun updatePartnerLot(
        @Path("id") lotId: Int,
        @PartMap parts: Map<String, @JvmSuppressWildcards RequestBody>,
        @Part image: MultipartBody.Part? = null
    ): ApiEnvelope<JsonElement>

    @DELETE("partner/lots/{id}")
    suspend fun deletePartnerLot(
        @Path("id") lotId: Int
    ): ApiEnvelope<JsonElement>

    @POST("partner/lots/{id}/regenerate-qr")
    suspend fun regeneratePartnerLotQr(
        @Path("id") lotId: Int
    ): ApiEnvelope<JsonElement>

    @GET("partner/packages")
    suspend fun partnerPackages(): ApiEnvelope<JsonElement>

    @POST("partner/packages")
    suspend fun createPartnerPackage(
        @Body request: CreatePackageRequest
    ): ApiEnvelope<JsonElement>

    @PUT("partner/packages/{id}")
    suspend fun updatePartnerPackage(
        @Path("id") packageId: Int,
        @Body request: UpdatePackageRequest
    ): ApiEnvelope<JsonElement>

    @DELETE("partner/packages/{id}")
    suspend fun deletePartnerPackage(
        @Path("id") packageId: Int
    ): ApiEnvelope<JsonElement>

    @POST("partner/packages/{id}/regenerate-qr")
    suspend fun regeneratePartnerPackageQr(
        @Path("id") packageId: Int
    ): ApiEnvelope<JsonElement>

    @GET("supply/assignments")
    suspend fun supplyAssignments(): ApiEnvelope<JsonElement>

    @GET("supply/events")
    suspend fun supplyEvents(): ApiEnvelope<JsonElement>

    @Multipart
    @POST("supply/events")
    suspend fun createSupplyEvent(
        @PartMap parts: Map<String, @JvmSuppressWildcards RequestBody>,
        @Part attachments: List<MultipartBody.Part>
    ): ApiEnvelope<JsonElement>

    @Multipart
    @POST("supply/events/{id}")
    suspend fun updateSupplyEvent(
        @Path("id") eventId: Int,
        @PartMap parts: Map<String, @JvmSuppressWildcards RequestBody>,
        @Part attachments: List<MultipartBody.Part>
    ): ApiEnvelope<JsonElement>
}
