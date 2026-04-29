package com.example.agrisort_ai.features.dashboard.domain.repository

import com.example.agrisort_ai.core.network.Resource
import com.example.agrisort_ai.features.dashboard.data.remote.AdminSettingsRequest
import com.example.agrisort_ai.features.dashboard.data.remote.CreateLotRequest
import com.example.agrisort_ai.features.dashboard.data.remote.CreatePackageRequest
import com.example.agrisort_ai.features.dashboard.data.remote.CreateSupplyEventRequest
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
import kotlinx.coroutines.flow.Flow

interface DashboardRepository {
    fun blogIndex(limit: Int = 20): Flow<Resource<List<BlogArticle>>>
    fun blogShow(slug: String): Flow<Resource<BlogArticle>>

    fun traceLookup(token: String, source: String = "camera"): Flow<Resource<TraceLookupResult>>
    fun traceShow(token: String, source: String = "manual"): Flow<Resource<TraceLookupResult>>

    fun adminDashboard(): Flow<Resource<AdminDashboardSummary>>
    fun adminUsers(
        query: String? = null,
        role: String? = null,
        status: String? = null,
        limit: Int = 20
    ): Flow<Resource<List<AdminUser>>>

    fun updateAdminUserStatus(userId: Int, status: String): Flow<Resource<String>>
    fun approvePartner(userId: Int): Flow<Resource<String>>
    fun adminSettings(): Flow<Resource<AdminSettings>>
    fun updateAdminSettings(request: AdminSettingsRequest): Flow<Resource<AdminSettings>>
    fun adminCms(sectionKey: String): Flow<Resource<AdminCmsSection>>
    fun saveAdminCms(sectionKey: String, request: SaveCmsRequest): Flow<Resource<AdminCmsSection>>
    fun adminBlog(): Flow<Resource<List<BlogArticle>>>
    fun saveAdminBlog(request: SaveBlogRequest): Flow<Resource<String>>
    fun adminApiKeys(): Flow<Resource<List<ApiKeyItem>>>
    fun createAdminApiKey(name: String): Flow<Resource<ApiKeyItem>>
    fun setAdminLotPublish(lotId: Int, publishStatus: String): Flow<Resource<String>>
    fun adminTraceAssignments(): Flow<Resource<List<TraceAssignment>>>
    fun saveTraceAssignment(request: TraceAssignmentRequest): Flow<Resource<String>>
    fun deleteTraceAssignment(assignmentId: Int): Flow<Resource<String>>
    fun adminTraceEvents(limit: Int = 200): Flow<Resource<List<TraceEvent>>>

    fun partnerLots(): Flow<Resource<List<PartnerLot>>>
    fun partnerLotDetail(lotId: Int): Flow<Resource<PartnerLot>>
    fun createPartnerLot(request: CreateLotRequest): Flow<Resource<String>>
    fun updatePartnerLot(lotId: Int, request: UpdateLotRequest): Flow<Resource<String>>
    fun deletePartnerLot(lotId: Int): Flow<Resource<String>>
    fun regeneratePartnerLotQr(lotId: Int): Flow<Resource<String>>
    fun partnerPackages(): Flow<Resource<List<PartnerPackage>>>
    fun createPartnerPackage(request: CreatePackageRequest): Flow<Resource<String>>
    fun updatePartnerPackage(packageId: Int, request: UpdatePackageRequest): Flow<Resource<String>>
    fun deletePartnerPackage(packageId: Int): Flow<Resource<String>>
    fun regeneratePartnerPackageQr(packageId: Int): Flow<Resource<String>>

    fun supplyAssignments(): Flow<Resource<List<SupplyAssignment>>>
    fun supplyEvents(): Flow<Resource<List<SupplyEvent>>>
    fun createSupplyEvent(request: CreateSupplyEventRequest): Flow<Resource<String>>
    fun updateSupplyEvent(eventId: Int, request: UpdateSupplyEventRequest): Flow<Resource<String>>

    fun observeScanHistory(limit: Int = 100): Flow<List<ScanHistory>>
    suspend fun saveScanHistory(
        token: String,
        source: String,
        status: String,
        message: String?,
        deduplicateByToken: Boolean = true
    )

    suspend fun clearScanHistory()
}
