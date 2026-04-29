package com.example.agrisort_ai.core.network

import kotlinx.coroutines.runBlocking
import okhttp3.Interceptor
import okhttp3.Response

class AuthHeaderInterceptor(
    private val fetchToken: suspend () -> String?,
    private val clearToken: suspend () -> Unit
) : Interceptor {

    override fun intercept(chain: Interceptor.Chain): Response {
        val originalRequest = chain.request()
        val token = runBlocking { fetchToken() }
        val encodedPath = originalRequest.url.encodedPath

        val requestBuilder = originalRequest.newBuilder()
            .header("Accept", "application/json")

        if (!token.isNullOrBlank() && !isPublicAuthEndpoint(encodedPath)) {
            requestBuilder.header("Authorization", "Bearer $token")
        }

        val response = chain.proceed(requestBuilder.build())

        if (response.code == 401 || response.code == 419) {
            runBlocking { clearToken() }
        }

        return response
    }

    companion object {
        fun isPublicAuthEndpoint(encodedPath: String): Boolean {
            return encodedPath.contains("/auth/login") ||
                encodedPath.contains("/auth/register") ||
                encodedPath.contains("/auth/forgot-password") ||
                encodedPath.contains("/auth/reset-password")
        }
    }
}
