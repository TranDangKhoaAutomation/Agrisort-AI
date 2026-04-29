package com.example.agrisort_ai.features.dashboard.di

import com.example.agrisort_ai.features.dashboard.data.remote.DashboardApi
import com.example.agrisort_ai.features.dashboard.data.repository.DashboardRepositoryImpl
import com.example.agrisort_ai.features.dashboard.domain.repository.DashboardRepository
import com.example.agrisort_ai.features.dashboard.domain.usecase.AdminApiKeysUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.AdminBlogUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.AdminCmsUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.AdminDashboardUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.AdminSettingsUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.AdminTraceAssignmentsUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.AdminTraceEventsUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.AdminUsersUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.ApprovePartnerUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.BlogIndexUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.BlogShowUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.ClearScanHistoryUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.CreateAdminApiKeyUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.CreatePartnerLotUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.CreatePartnerPackageUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.CreateSupplyEventUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.DashboardUseCases
import com.example.agrisort_ai.features.dashboard.domain.usecase.DeletePartnerLotUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.DeletePartnerPackageUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.DeleteTraceAssignmentUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.ObserveScanHistoryUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.PartnerLotDetailUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.PartnerLotsUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.PartnerPackagesUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.RegeneratePartnerLotQrUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.RegeneratePartnerPackageQrUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.SaveAdminBlogUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.SaveAdminCmsUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.SaveScanHistoryUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.SaveTraceAssignmentUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.SetAdminLotPublishUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.SupplyAssignmentsUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.SupplyEventsUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.TraceLookupUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.TraceShowUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.UpdateAdminSettingsUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.UpdateAdminUserStatusUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.UpdatePartnerLotUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.UpdatePartnerPackageUseCase
import com.example.agrisort_ai.features.dashboard.domain.usecase.UpdateSupplyEventUseCase
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import retrofit2.Retrofit
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object DashboardModule {

    @Provides
    @Singleton
    fun provideDashboardApi(retrofit: Retrofit): DashboardApi {
        return retrofit.create(DashboardApi::class.java)
    }

    @Provides
    @Singleton
    fun provideDashboardRepository(repository: DashboardRepositoryImpl): DashboardRepository = repository

    @Provides
    @Singleton
    fun provideDashboardUseCases(repository: DashboardRepository): DashboardUseCases {
        return DashboardUseCases(
            blogIndex = BlogIndexUseCase(repository),
            blogShow = BlogShowUseCase(repository),
            traceLookup = TraceLookupUseCase(repository),
            traceShow = TraceShowUseCase(repository),
            adminDashboard = AdminDashboardUseCase(repository),
            adminUsers = AdminUsersUseCase(repository),
            updateAdminUserStatus = UpdateAdminUserStatusUseCase(repository),
            approvePartner = ApprovePartnerUseCase(repository),
            adminSettings = AdminSettingsUseCase(repository),
            updateAdminSettings = UpdateAdminSettingsUseCase(repository),
            adminCms = AdminCmsUseCase(repository),
            saveAdminCms = SaveAdminCmsUseCase(repository),
            adminBlog = AdminBlogUseCase(repository),
            saveAdminBlog = SaveAdminBlogUseCase(repository),
            adminApiKeys = AdminApiKeysUseCase(repository),
            createAdminApiKey = CreateAdminApiKeyUseCase(repository),
            setAdminLotPublish = SetAdminLotPublishUseCase(repository),
            adminTraceAssignments = AdminTraceAssignmentsUseCase(repository),
            saveTraceAssignment = SaveTraceAssignmentUseCase(repository),
            deleteTraceAssignment = DeleteTraceAssignmentUseCase(repository),
            adminTraceEvents = AdminTraceEventsUseCase(repository),
            partnerLots = PartnerLotsUseCase(repository),
            partnerLotDetail = PartnerLotDetailUseCase(repository),
            createPartnerLot = CreatePartnerLotUseCase(repository),
            updatePartnerLot = UpdatePartnerLotUseCase(repository),
            deletePartnerLot = DeletePartnerLotUseCase(repository),
            regeneratePartnerLotQr = RegeneratePartnerLotQrUseCase(repository),
            partnerPackages = PartnerPackagesUseCase(repository),
            createPartnerPackage = CreatePartnerPackageUseCase(repository),
            updatePartnerPackage = UpdatePartnerPackageUseCase(repository),
            deletePartnerPackage = DeletePartnerPackageUseCase(repository),
            regeneratePartnerPackageQr = RegeneratePartnerPackageQrUseCase(repository),
            supplyAssignments = SupplyAssignmentsUseCase(repository),
            supplyEvents = SupplyEventsUseCase(repository),
            createSupplyEvent = CreateSupplyEventUseCase(repository),
            updateSupplyEvent = UpdateSupplyEventUseCase(repository),
            observeScanHistory = ObserveScanHistoryUseCase(repository),
            saveScanHistory = SaveScanHistoryUseCase(repository),
            clearScanHistory = ClearScanHistoryUseCase(repository)
        )
    }
}
