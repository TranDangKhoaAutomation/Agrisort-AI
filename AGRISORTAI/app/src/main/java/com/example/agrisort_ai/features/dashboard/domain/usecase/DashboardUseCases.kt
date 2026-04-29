package com.example.agrisort_ai.features.dashboard.domain.usecase

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
import com.example.agrisort_ai.features.dashboard.domain.repository.DashboardRepository
import javax.inject.Inject

data class DashboardUseCases(
    val blogIndex: BlogIndexUseCase,
    val blogShow: BlogShowUseCase,
    val traceLookup: TraceLookupUseCase,
    val traceShow: TraceShowUseCase,
    val adminDashboard: AdminDashboardUseCase,
    val adminUsers: AdminUsersUseCase,
    val updateAdminUserStatus: UpdateAdminUserStatusUseCase,
    val approvePartner: ApprovePartnerUseCase,
    val adminSettings: AdminSettingsUseCase,
    val updateAdminSettings: UpdateAdminSettingsUseCase,
    val adminCms: AdminCmsUseCase,
    val saveAdminCms: SaveAdminCmsUseCase,
    val adminBlog: AdminBlogUseCase,
    val saveAdminBlog: SaveAdminBlogUseCase,
    val adminApiKeys: AdminApiKeysUseCase,
    val createAdminApiKey: CreateAdminApiKeyUseCase,
    val setAdminLotPublish: SetAdminLotPublishUseCase,
    val adminTraceAssignments: AdminTraceAssignmentsUseCase,
    val saveTraceAssignment: SaveTraceAssignmentUseCase,
    val deleteTraceAssignment: DeleteTraceAssignmentUseCase,
    val adminTraceEvents: AdminTraceEventsUseCase,
    val partnerLots: PartnerLotsUseCase,
    val partnerLotDetail: PartnerLotDetailUseCase,
    val createPartnerLot: CreatePartnerLotUseCase,
    val updatePartnerLot: UpdatePartnerLotUseCase,
    val deletePartnerLot: DeletePartnerLotUseCase,
    val regeneratePartnerLotQr: RegeneratePartnerLotQrUseCase,
    val partnerPackages: PartnerPackagesUseCase,
    val createPartnerPackage: CreatePartnerPackageUseCase,
    val updatePartnerPackage: UpdatePartnerPackageUseCase,
    val deletePartnerPackage: DeletePartnerPackageUseCase,
    val regeneratePartnerPackageQr: RegeneratePartnerPackageQrUseCase,
    val supplyAssignments: SupplyAssignmentsUseCase,
    val supplyEvents: SupplyEventsUseCase,
    val createSupplyEvent: CreateSupplyEventUseCase,
    val updateSupplyEvent: UpdateSupplyEventUseCase,
    val observeScanHistory: ObserveScanHistoryUseCase,
    val saveScanHistory: SaveScanHistoryUseCase,
    val clearScanHistory: ClearScanHistoryUseCase
)

class BlogIndexUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(limit: Int = 20) = repository.blogIndex(limit)
}

class BlogShowUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(slug: String) = repository.blogShow(slug)
}

class TraceLookupUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(token: String, source: String = "camera") = repository.traceLookup(token, source)
}

class TraceShowUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(token: String, source: String = "manual") = repository.traceShow(token, source)
}

class AdminDashboardUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke() = repository.adminDashboard()
}

class AdminUsersUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(query: String?, role: String?, status: String?, limit: Int) =
        repository.adminUsers(query, role, status, limit)
}

class UpdateAdminUserStatusUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(userId: Int, status: String) = repository.updateAdminUserStatus(userId, status)
}

class ApprovePartnerUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(userId: Int) = repository.approvePartner(userId)
}

class AdminSettingsUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke() = repository.adminSettings()
}

class UpdateAdminSettingsUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(request: AdminSettingsRequest) = repository.updateAdminSettings(request)
}

class AdminCmsUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(sectionKey: String) = repository.adminCms(sectionKey)
}

class SaveAdminCmsUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(sectionKey: String, request: SaveCmsRequest) = repository.saveAdminCms(sectionKey, request)
}

class AdminBlogUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke() = repository.adminBlog()
}

class SaveAdminBlogUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(request: SaveBlogRequest) = repository.saveAdminBlog(request)
}

class AdminApiKeysUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke() = repository.adminApiKeys()
}

class CreateAdminApiKeyUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(name: String) = repository.createAdminApiKey(name)
}

class SetAdminLotPublishUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(lotId: Int, publishStatus: String) = repository.setAdminLotPublish(lotId, publishStatus)
}

class AdminTraceAssignmentsUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke() = repository.adminTraceAssignments()
}

class SaveTraceAssignmentUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(request: TraceAssignmentRequest) = repository.saveTraceAssignment(request)
}

class DeleteTraceAssignmentUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(assignmentId: Int) = repository.deleteTraceAssignment(assignmentId)
}

class AdminTraceEventsUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(limit: Int = 200) = repository.adminTraceEvents(limit)
}

class PartnerLotsUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke() = repository.partnerLots()
}

class PartnerLotDetailUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(lotId: Int) = repository.partnerLotDetail(lotId)
}

class CreatePartnerLotUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(request: CreateLotRequest) = repository.createPartnerLot(request)
}

class UpdatePartnerLotUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(lotId: Int, request: UpdateLotRequest) = repository.updatePartnerLot(lotId, request)
}

class DeletePartnerLotUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(lotId: Int) = repository.deletePartnerLot(lotId)
}

class RegeneratePartnerLotQrUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(lotId: Int) = repository.regeneratePartnerLotQr(lotId)
}

class PartnerPackagesUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke() = repository.partnerPackages()
}

class CreatePartnerPackageUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(request: CreatePackageRequest) = repository.createPartnerPackage(request)
}

class UpdatePartnerPackageUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(packageId: Int, request: UpdatePackageRequest) =
        repository.updatePartnerPackage(packageId, request)
}

class DeletePartnerPackageUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(packageId: Int) = repository.deletePartnerPackage(packageId)
}

class RegeneratePartnerPackageQrUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(packageId: Int) = repository.regeneratePartnerPackageQr(packageId)
}

class SupplyAssignmentsUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke() = repository.supplyAssignments()
}

class SupplyEventsUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke() = repository.supplyEvents()
}

class CreateSupplyEventUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(request: CreateSupplyEventRequest) = repository.createSupplyEvent(request)
}

class UpdateSupplyEventUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(eventId: Int, request: UpdateSupplyEventRequest) = repository.updateSupplyEvent(eventId, request)
}

class ObserveScanHistoryUseCase @Inject constructor(private val repository: DashboardRepository) {
    operator fun invoke(limit: Int = 100) = repository.observeScanHistory(limit)
}

class SaveScanHistoryUseCase @Inject constructor(private val repository: DashboardRepository) {
    suspend operator fun invoke(
        token: String,
        source: String,
        status: String,
        message: String?,
        deduplicateByToken: Boolean
    ) {
        repository.saveScanHistory(token, source, status, message, deduplicateByToken)
    }
}

class ClearScanHistoryUseCase @Inject constructor(private val repository: DashboardRepository) {
    suspend operator fun invoke() {
        repository.clearScanHistory()
    }
}
