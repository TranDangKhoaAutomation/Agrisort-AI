package com.example.agrisort_ai.features.auth.data.repository

import com.example.agrisort_ai.core.data.TokenStore
import com.example.agrisort_ai.core.data.UserSessionStore
import com.example.agrisort_ai.core.network.ApiEnvelope
import com.example.agrisort_ai.core.network.Resource
import com.example.agrisort_ai.features.auth.data.remote.AuthApi
import com.example.agrisort_ai.features.auth.data.remote.AuthSessionData
import com.example.agrisort_ai.features.auth.data.remote.AuthUserData
import com.example.agrisort_ai.features.auth.data.remote.ForgotPasswordRequest
import com.example.agrisort_ai.features.auth.data.remote.LoginRequest
import com.example.agrisort_ai.features.auth.data.remote.RegisterRequest
import com.example.agrisort_ai.features.auth.data.remote.ResetPasswordRequest
import com.example.agrisort_ai.features.auth.data.remote.UpdatePasswordRequest
import com.example.agrisort_ai.features.auth.data.remote.UpdateProfileRequest
import com.example.agrisort_ai.features.auth.domain.model.User
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.toList
import kotlinx.coroutines.runBlocking
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test
import retrofit2.HttpException
import retrofit2.Response

class AuthRepositoryImplTest {

    @Test
    fun loginSuccessSavesAccessToken() = runBlocking {
        val tokenStore = FakeTokenStore()
        val sessionStore = FakeUserSessionStore()
        val api = FakeAuthApi().apply {
            loginResponse = ApiEnvelope(
                success = true,
                message = "Đăng nhập thành công.",
                data = AuthSessionData(
                    accessToken = "access_123",
                    user = User(
                        id = 7,
                        fullName = "Demo User",
                        email = "demo@example.com",
                        role = "farmer",
                        status = "active",
                        roleLabel = "Nông dân"
                    )
                )
            )
        }
        val repository = AuthRepositoryImpl(api, tokenStore, sessionStore)

        val emissions = repository.login("demo@example.com", "Demo@12345").toList()
        val result = emissions.last()

        assertTrue(result is Resource.Success)
        assertEquals("access_123", tokenStore.currentToken())
        assertEquals("demo@example.com", sessionStore.currentUser()?.email)
    }

    @Test
    fun logoutClearsTokenWhenApiSuccess() = runBlocking {
        val tokenStore = FakeTokenStore()
        val sessionStore = FakeUserSessionStore().apply {
            saveUser(
                User(
                    id = 1,
                    fullName = "Demo User",
                    email = "demo@example.com",
                    role = "farmer",
                    status = "active",
                    roleLabel = "Nông dân"
                )
            )
        }
        tokenStore.saveToken("old_token")

        val api = FakeAuthApi().apply {
            logoutResponse = ApiEnvelope(success = true, message = "Đăng xuất thành công.")
        }
        val repository = AuthRepositoryImpl(api, tokenStore, sessionStore)

        val emissions = repository.logout().toList()
        val result = emissions.last()

        assertTrue(result is Resource.Success)
        assertNull(tokenStore.currentToken())
        assertNull(sessionStore.currentUser())
    }

    @Test
    fun logoutClearsTokenWhenApiFails() = runBlocking {
        val tokenStore = FakeTokenStore()
        val sessionStore = FakeUserSessionStore()
        tokenStore.saveToken("old_token")

        val api = FakeAuthApi().apply {
            logoutException = RuntimeException("network down")
        }
        val repository = AuthRepositoryImpl(api, tokenStore, sessionStore)

        val emissions = repository.logout().toList()
        val result = emissions.last()

        assertTrue(result is Resource.Success)
        assertNull(tokenStore.currentToken())
        assertNull(sessionStore.currentUser())
    }

    @Test
    fun registerReturnsServer422Message() = runBlocking {
        val api = FakeAuthApi().apply {
            registerException = httpException(
                code = 422,
                json = """{"success":false,"message":"Dữ liệu đăng ký chưa hợp lệ."}"""
            )
        }
        val repository = AuthRepositoryImpl(api, FakeTokenStore(), FakeUserSessionStore())

        val emissions = repository.register(
            fullName = "Farmer Demo",
            email = "farmer.demo@example.com",
            password = "Demo@12345",
            organizationName = "",
            representativeName = "",
            phone = "",
            address = "",
            region = "",
            taxCode = ""
        ).toList()

        val result = emissions.last()
        assertTrue(result is Resource.Error)
        result as Resource.Error
        assertEquals(422, result.code)
        assertEquals("Dữ liệu không hợp lệ", result.message)
    }

    @Test
    fun registerSendsExtendedPayloadAndReturnsMessage() = runBlocking {
        val api = FakeAuthApi().apply {
            registerResponse = ApiEnvelope(success = true, message = "Đăng ký tài khoản thành công.")
        }
        val repository = AuthRepositoryImpl(api, FakeTokenStore(), FakeUserSessionStore())

        val emissions = repository.register(
            fullName = "Farmer Demo",
            email = "farmer.demo@example.com",
            password = "Demo@12345",
            organizationName = "HTX Demo",
            representativeName = "Farmer Demo",
            phone = "0900000000",
            address = "Can Tho",
            region = "Mekong",
            taxCode = "TAX123"
        ).toList()

        val last = emissions.last()
        assertTrue(last is Resource.Success)
        last as Resource.Success<String>
        assertEquals("Đăng ký tài khoản thành công.", last.data)

        val request = api.lastRegisterRequest
        assertNotNull(request)
        assertEquals("HTX Demo", request?.organizationName)
        assertEquals("Mekong", request?.region)
    }

    @Test
    fun updateProfileSendsExtendedPayload() = runBlocking {
        val updatedUser = User(
            id = 3,
            fullName = "Farmer Demo Updated",
            email = "farmer.demo@example.com",
            role = "farmer",
            status = "active",
            roleLabel = "Nông dân"
        )
        val sessionStore = FakeUserSessionStore()
        val api = FakeAuthApi().apply {
            updateProfileResponse = ApiEnvelope(
                success = true,
                message = "Cập nhật hồ sơ thành công.",
                data = AuthUserData(user = updatedUser)
            )
        }
        val repository = AuthRepositoryImpl(api, FakeTokenStore(), sessionStore)

        val emissions = repository.updateProfile(
            fullName = "Farmer Demo Updated",
            email = "farmer.demo@example.com",
            organizationName = "HTX Demo Updated",
            representativeName = "Farmer Demo",
            phone = "0911111111",
            address = "Can Tho",
            region = "Mekong",
            taxCode = "TAX999"
        ).toList()

        val last = emissions.last()
        assertTrue(last is Resource.Success)
        last as Resource.Success<User>
        assertEquals("Farmer Demo Updated", last.data.fullName)
        assertEquals("farmer.demo@example.com", sessionStore.currentUser()?.email)

        val request = api.lastUpdateProfileRequest
        assertNotNull(request)
        assertEquals("HTX Demo Updated", request?.organizationName)
        assertEquals("TAX999", request?.taxCode)
    }

    private fun httpException(code: Int, json: String): HttpException {
        val body = json.toResponseBody("application/json".toMediaType())
        return HttpException(Response.error<Any>(code, body))
    }
}

private class FakeTokenStore : TokenStore {
    private val tokenState = MutableStateFlow<String?>(null)
    override val token: Flow<String?> = tokenState

    override suspend fun saveToken(token: String) {
        tokenState.value = token
    }

    override suspend fun clearToken() {
        tokenState.value = null
    }

    suspend fun currentToken(): String? = token.first()
}

private class FakeUserSessionStore : UserSessionStore {
    private val userState = MutableStateFlow<User?>(null)

    override val cachedUser: Flow<User?> = userState

    override suspend fun saveUser(user: User) {
        userState.value = user
    }

    override suspend fun clearUser() {
        userState.value = null
    }

    suspend fun currentUser(): User? = userState.first()
}

private class FakeAuthApi : AuthApi {
    var loginResponse: ApiEnvelope<AuthSessionData> = ApiEnvelope(success = true, message = "OK")
    var registerResponse: ApiEnvelope<AuthSessionData> = ApiEnvelope(success = true, message = "OK")
    var logoutResponse: ApiEnvelope<kotlinx.serialization.json.JsonObject> = ApiEnvelope(success = true, message = "OK")
    var updateProfileResponse: ApiEnvelope<AuthUserData> = ApiEnvelope(success = true, message = "OK")

    var loginException: Exception? = null
    var registerException: Exception? = null
    var logoutException: Exception? = null
    var updateProfileException: Exception? = null

    var lastLoginRequest: LoginRequest? = null
    var lastRegisterRequest: RegisterRequest? = null
    var lastUpdateProfileRequest: UpdateProfileRequest? = null

    override suspend fun register(request: RegisterRequest): ApiEnvelope<AuthSessionData> {
        lastRegisterRequest = request
        registerException?.let { throw it }
        return registerResponse
    }

    override suspend fun login(request: LoginRequest): ApiEnvelope<AuthSessionData> {
        lastLoginRequest = request
        loginException?.let { throw it }
        return loginResponse
    }

    override suspend fun logout(): ApiEnvelope<kotlinx.serialization.json.JsonObject> {
        logoutException?.let { throw it }
        return logoutResponse
    }

    override suspend fun getMe(): ApiEnvelope<AuthUserData> {
        throw UnsupportedOperationException("Not used in this test")
    }

    override suspend fun updateProfile(request: UpdateProfileRequest): ApiEnvelope<AuthUserData> {
        lastUpdateProfileRequest = request
        updateProfileException?.let { throw it }
        return updateProfileResponse
    }

    override suspend fun updatePassword(request: UpdatePasswordRequest): ApiEnvelope<AuthUserData> {
        throw UnsupportedOperationException("Not used in this test")
    }

    override suspend fun forgotPassword(request: ForgotPasswordRequest): ApiEnvelope<kotlinx.serialization.json.JsonObject> {
        throw UnsupportedOperationException("Not used in this test")
    }

    override suspend fun resetPassword(request: ResetPasswordRequest): ApiEnvelope<kotlinx.serialization.json.JsonObject> {
        throw UnsupportedOperationException("Not used in this test")
    }
}
