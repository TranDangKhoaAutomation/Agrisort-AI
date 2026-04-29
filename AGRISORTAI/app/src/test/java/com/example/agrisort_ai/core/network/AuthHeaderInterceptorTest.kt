package com.example.agrisort_ai.core.network

import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test

class AuthHeaderInterceptorTest {

    private lateinit var server: MockWebServer

    @Before
    fun setUp() {
        server = MockWebServer()
        server.start()
    }

    @After
    fun tearDown() {
        server.shutdown()
    }

    @Test
    fun doesNotAttachAuthorizationHeaderForLogin() {
        var cleared = false
        val client = OkHttpClient.Builder()
            .addInterceptor(
                AuthHeaderInterceptor(
                    fetchToken = { "old_token" },
                    clearToken = { cleared = true }
                )
            )
            .build()

        server.enqueue(MockResponse().setResponseCode(200).setBody("{}"))

        val request = Request.Builder()
            .url(server.url("/api/v1/app/auth/login"))
            .post("{}".toRequestBody("application/json".toMediaType()))
            .build()

        client.newCall(request).execute().close()

        val recorded = server.takeRequest()
        assertNull(recorded.getHeader("Authorization"))
        assertEquals("application/json", recorded.getHeader("Accept"))
        assertTrue(!cleared)
    }

    @Test
    fun attachesAuthorizationHeaderForProtectedEndpoint() {
        val client = OkHttpClient.Builder()
            .addInterceptor(
                AuthHeaderInterceptor(
                    fetchToken = { "valid_token" },
                    clearToken = { }
                )
            )
            .build()

        server.enqueue(MockResponse().setResponseCode(200).setBody("{}"))

        val request = Request.Builder()
            .url(server.url("/api/v1/app/auth/me"))
            .get()
            .build()

        client.newCall(request).execute().close()

        val recorded = server.takeRequest()
        assertEquals("Bearer valid_token", recorded.getHeader("Authorization"))
    }

    @Test
    fun clearsTokenWhenServerReturns401Or419() {
        var clearCallCount = 0
        val client = OkHttpClient.Builder()
            .addInterceptor(
                AuthHeaderInterceptor(
                    fetchToken = { "token_to_clear" },
                    clearToken = { clearCallCount += 1 }
                )
            )
            .build()

        server.enqueue(MockResponse().setResponseCode(419).setBody("{}"))
        val first = Request.Builder().url(server.url("/api/v1/app/auth/me")).get().build()
        client.newCall(first).execute().close()

        server.enqueue(MockResponse().setResponseCode(401).setBody("{}"))
        val second = Request.Builder().url(server.url("/api/v1/app/auth/me")).get().build()
        client.newCall(second).execute().close()

        assertEquals(2, clearCallCount)
    }
}
