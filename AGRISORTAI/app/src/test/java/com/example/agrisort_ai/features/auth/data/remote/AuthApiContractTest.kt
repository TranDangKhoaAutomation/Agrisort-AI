package com.example.agrisort_ai.features.auth.data.remote

import com.jakewharton.retrofit2.converter.kotlinx.serialization.asConverterFactory
import kotlinx.coroutines.runBlocking
import kotlinx.serialization.json.Json
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import retrofit2.Retrofit

class AuthApiContractTest {

    private lateinit var server: MockWebServer
    private lateinit var api: AuthApi

    @Before
    fun setUp() {
        server = MockWebServer()
        server.start()

        val json = Json {
            ignoreUnknownKeys = true
            coerceInputValues = true
        }

        val retrofit = Retrofit.Builder()
            .baseUrl(server.url("/api/v1/app/"))
            .client(OkHttpClient.Builder().build())
            .addConverterFactory(json.asConverterFactory("application/json".toMediaType()))
            .build()

        api = retrofit.create(AuthApi::class.java)
    }

    @After
    fun tearDown() {
        server.shutdown()
    }

    @Test
    fun loginCallsCorrectPathAndParsesSessionEnvelope() = runBlocking {
        server.enqueue(
            MockResponse()
                .setResponseCode(200)
                .setBody(
                    """
                    {
                      "success": true,
                      "message": "Login successful.",
                      "data": {
                        "token_type": "Bearer",
                        "access_token": "abc123",
                        "expires_at": "2026-12-31 00:00:00",
                        "user": {
                          "id": 10,
                          "full_name": "Nguyen Van A",
                          "email": "a@example.com",
                          "role": "partner",
                          "status": "active",
                          "role_label": "Partner"
                        }
                      },
                      "errors": null,
                      "meta": null
                    }
                    """.trimIndent()
                )
        )

        val response = api.login(LoginRequest(email = "a@example.com", password = "secret123"))

        assertTrue(response.success)
        assertEquals("abc123", response.data?.accessToken)
        assertNotNull(response.data?.user)
        assertEquals("Nguyen Van A", response.data?.user?.fullName)

        val recorded = server.takeRequest()
        assertEquals("/api/v1/app/auth/login", recorded.path)
        val payload = recorded.body.readUtf8()
        assertTrue(payload.contains("\"email\":\"a@example.com\""))
        assertTrue(payload.contains("\"password\":\"secret123\""))
    }

    @Test
    fun registerUsesSnakeCaseFieldsAndExtendedProfilePayload() = runBlocking {
        server.enqueue(
            MockResponse()
                .setResponseCode(200)
                .setBody(
                    """
                    {
                      "success": true,
                      "message": "Register successful.",
                      "data": null,
                      "errors": null,
                      "meta": null
                    }
                    """.trimIndent()
                )
        )

        api.register(
            RegisterRequest(
                fullName = "Nguyen Van B",
                email = "b@example.com",
                password = "secret123",
                organizationName = "HTX Test",
                representativeName = "Nguyen Van B",
                phone = "0900000000",
                address = "Can Tho",
                region = "Mekong",
                taxCode = "TAX123"
            )
        )

        val recorded = server.takeRequest()
        assertEquals("/api/v1/app/auth/register", recorded.path)
        val payload = recorded.body.readUtf8()
        assertTrue(payload.contains("\"full_name\":\"Nguyen Van B\""))
        assertTrue(payload.contains("\"email\":\"b@example.com\""))
        assertTrue(payload.contains("\"password\":\"secret123\""))
        assertFalse(payload.contains("\"role\":"))
        assertTrue(payload.contains("\"organization_name\":\"HTX Test\""))
        assertTrue(payload.contains("\"representative_name\":\"Nguyen Van B\""))
        assertTrue(payload.contains("\"phone\":\"0900000000\""))
        assertTrue(payload.contains("\"address\":\"Can Tho\""))
        assertTrue(payload.contains("\"region\":\"Mekong\""))
        assertTrue(payload.contains("\"tax_code\":\"TAX123\""))
    }

    @Test
    fun updateProfileUsesExtendedSnakeCaseFields() = runBlocking {
        server.enqueue(
            MockResponse()
                .setResponseCode(200)
                .setBody(
                    """
                    {
                      "success": true,
                      "message": "Profile updated.",
                      "data": null,
                      "errors": null,
                      "meta": null
                    }
                    """.trimIndent()
                )
        )

        api.updateProfile(
            UpdateProfileRequest(
                fullName = "Nguyen Van C",
                email = "c@example.com",
                organizationName = "HTX C",
                representativeName = "Nguyen Van C",
                phone = "0911111111",
                address = "Dong Thap",
                region = "Mekong",
                taxCode = "TAX999"
            )
        )

        val recorded = server.takeRequest()
        assertEquals("/api/v1/app/auth/profile", recorded.path)
        val payload = recorded.body.readUtf8()
        assertTrue(payload.contains("\"full_name\":\"Nguyen Van C\""))
        assertTrue(payload.contains("\"email\":\"c@example.com\""))
        assertTrue(payload.contains("\"organization_name\":\"HTX C\""))
        assertTrue(payload.contains("\"representative_name\":\"Nguyen Van C\""))
        assertTrue(payload.contains("\"phone\":\"0911111111\""))
        assertTrue(payload.contains("\"address\":\"Dong Thap\""))
        assertTrue(payload.contains("\"region\":\"Mekong\""))
        assertTrue(payload.contains("\"tax_code\":\"TAX999\""))
    }

    @Test
    fun updatePasswordUsesBackendFieldNames() = runBlocking {
        server.enqueue(
            MockResponse()
                .setResponseCode(200)
                .setBody(
                    """
                    {
                      "success": true,
                      "message": "Password updated.",
                      "data": null,
                      "errors": null,
                      "meta": null
                    }
                    """.trimIndent()
                )
        )

        api.updatePassword(
            UpdatePasswordRequest(
                currentPassword = "oldpass",
                password = "newpass123",
                passwordConfirmation = "newpass123"
            )
        )

        val recorded = server.takeRequest()
        assertEquals("/api/v1/app/auth/password", recorded.path)
        val payload = recorded.body.readUtf8()
        assertTrue(payload.contains("\"current_password\":\"oldpass\""))
        assertTrue(payload.contains("\"password\":\"newpass123\""))
        assertTrue(payload.contains("\"password_confirmation\":\"newpass123\""))
    }
}
