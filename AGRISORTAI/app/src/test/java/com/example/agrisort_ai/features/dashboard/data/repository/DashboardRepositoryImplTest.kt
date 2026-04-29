package com.example.agrisort_ai.features.dashboard.data.repository

import android.net.Uri
import com.example.agrisort_ai.core.local.ScanHistoryDao
import com.example.agrisort_ai.core.local.ScanHistoryEntity
import com.example.agrisort_ai.core.network.ApiEnvelope
import com.example.agrisort_ai.core.network.Resource
import com.example.agrisort_ai.core.network.UploadPartFactory
import com.example.agrisort_ai.features.dashboard.data.remote.AdminSettingsRequest
import com.example.agrisort_ai.features.dashboard.data.remote.AdminUserStatusRequest
import com.example.agrisort_ai.features.dashboard.data.remote.CreateApiKeyRequest
import com.example.agrisort_ai.features.dashboard.data.remote.DashboardApi
import com.example.agrisort_ai.features.dashboard.data.remote.LotPublishRequest
import com.example.agrisort_ai.features.dashboard.data.remote.TraceAssignmentRequest
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.toList
import kotlinx.coroutines.runBlocking
import kotlinx.serialization.SerializationException
import kotlinx.serialization.json.JsonArray
import kotlinx.serialization.json.JsonElement
import okhttp3.MultipartBody
import okhttp3.RequestBody
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class DashboardRepositoryImplTest {

    @Test
    fun partnerLotsReturnsFriendlyMessageOnSerializationError() = runBlocking {
        val api = FakeDashboardApi().apply {
            partnerLotsException = SerializationException("Unexpected JSON token")
        }
        val repository = DashboardRepositoryImpl(
            api = api,
            scanHistoryDao = FakeScanHistoryDao(),
            multipartPartFactory = FakeUploadPartFactory()
        )

        val emissions = repository.partnerLots().toList()
        val result = emissions.last()

        assertTrue(result is Resource.Error)
        result as Resource.Error
        assertEquals(
            "Dữ liệu trả về từ máy chủ chưa đúng định dạng mong đợi.",
            result.message
        )
    }
}

private class FakeDashboardApi : DashboardApi {
    var partnerLotsException: Exception? = null

    override suspend fun blogIndex(limit: Int): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun blogShow(slug: String): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun traceLookup(token: String): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun traceShow(qrToken: String): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun adminDashboard(): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun adminUsers(
        query: String?,
        role: String?,
        status: String?,
        limit: Int
    ): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun updateAdminUserStatus(
        userId: Int,
        request: AdminUserStatusRequest
    ): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun approvePartner(userId: Int): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun adminSettings(): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun updateAdminSettings(request: AdminSettingsRequest): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun adminCms(sectionKey: String): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun saveAdminCms(
        sectionKey: String,
        parts: Map<String, RequestBody>,
        image: MultipartBody.Part?
    ): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun adminBlog(): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun saveAdminBlog(
        parts: Map<String, RequestBody>,
        thumbnail: MultipartBody.Part?
    ): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun adminApiKeys(): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun createAdminApiKey(request: CreateApiKeyRequest): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun setAdminLotPublish(
        lotId: Int,
        request: LotPublishRequest
    ): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun adminTraceAssignments(): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun saveTraceAssignment(request: TraceAssignmentRequest): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun deleteTraceAssignment(assignmentId: Int): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun adminTraceEvents(limit: Int): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun partnerLots(): ApiEnvelope<JsonElement> {
        partnerLotsException?.let { throw it }
        return ApiEnvelope(success = true, message = "OK", data = JsonArray(emptyList()))
    }

    override suspend fun partnerLotDetail(lotId: Int): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun createPartnerLot(
        parts: Map<String, RequestBody>,
        image: MultipartBody.Part
    ): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun updatePartnerLot(
        lotId: Int,
        parts: Map<String, RequestBody>,
        image: MultipartBody.Part?
    ): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun deletePartnerLot(lotId: Int): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun regeneratePartnerLotQr(lotId: Int): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun partnerPackages(): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun createPartnerPackage(request: com.example.agrisort_ai.features.dashboard.data.remote.CreatePackageRequest): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun updatePartnerPackage(
        packageId: Int,
        request: com.example.agrisort_ai.features.dashboard.data.remote.UpdatePackageRequest
    ): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun deletePartnerPackage(packageId: Int): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun regeneratePartnerPackageQr(packageId: Int): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun supplyAssignments(): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun supplyEvents(): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun createSupplyEvent(
        parts: Map<String, RequestBody>,
        attachments: List<MultipartBody.Part>
    ): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }

    override suspend fun updateSupplyEvent(
        eventId: Int,
        parts: Map<String, RequestBody>,
        attachments: List<MultipartBody.Part>
    ): ApiEnvelope<JsonElement> {
        throw UnsupportedOperationException()
    }
}

private class FakeUploadPartFactory : UploadPartFactory {
    override fun textPart(value: String): RequestBody {
        throw UnsupportedOperationException("Not used in this test")
    }

    override fun filePart(fieldName: String, uri: Uri): MultipartBody.Part {
        throw UnsupportedOperationException("Not used in this test")
    }

    override fun fileParts(fieldName: String, uris: List<Uri>): List<MultipartBody.Part> {
        throw UnsupportedOperationException("Not used in this test")
    }
}

private class FakeScanHistoryDao : ScanHistoryDao {
    private val entries = MutableStateFlow<List<ScanHistoryEntity>>(emptyList())

    override suspend fun insert(entry: ScanHistoryEntity) {
        entries.value = listOf(entry) + entries.value
    }

    override fun observeRecent(limit: Int): Flow<List<ScanHistoryEntity>> = entries

    override suspend fun deleteByToken(token: String) {
        entries.value = entries.value.filterNot { it.token == token }
    }

    override suspend fun clearAll() {
        entries.value = emptyList()
    }
}
