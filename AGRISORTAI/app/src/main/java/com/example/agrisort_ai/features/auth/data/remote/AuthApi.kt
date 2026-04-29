package com.example.agrisort_ai.features.auth.data.remote

import com.example.agrisort_ai.core.network.ApiEnvelope
import com.example.agrisort_ai.features.auth.domain.model.User
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonObject
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT

@Serializable
data class LoginRequest(
    val email: String,
    val password: String
)

@Serializable
data class RegisterRequest(
    @SerialName("full_name") val fullName: String,
    val email: String,
    val password: String,
    @SerialName("organization_name") val organizationName: String,
    @SerialName("representative_name") val representativeName: String,
    val phone: String,
    val address: String,
    val region: String,
    @SerialName("tax_code") val taxCode: String
)

@Serializable
data class ForgotPasswordRequest(
    val email: String
)

@Serializable
data class ResetPasswordRequest(
    val token: String,
    val password: String,
    @SerialName("password_confirmation") val passwordConfirmation: String
)

@Serializable
data class UpdateProfileRequest(
    @SerialName("full_name") val fullName: String,
    val email: String,
    @SerialName("organization_name") val organizationName: String,
    @SerialName("representative_name") val representativeName: String,
    val phone: String,
    val address: String,
    val region: String,
    @SerialName("tax_code") val taxCode: String
)

@Serializable
data class UpdatePasswordRequest(
    @SerialName("current_password") val currentPassword: String,
    val password: String,
    @SerialName("password_confirmation") val passwordConfirmation: String
)

@Serializable
data class AuthSessionData(
    @SerialName("token_type") val tokenType: String? = null,
    @SerialName("access_token") val accessToken: String? = null,
    @SerialName("expires_at") val expiresAt: String? = null,
    val user: User? = null
)

@Serializable
data class AuthUserData(
    val user: User? = null,
    @SerialName("expires_at") val expiresAt: String? = null
)

interface AuthApi {
    @POST("auth/register")
    suspend fun register(@Body request: RegisterRequest): ApiEnvelope<AuthSessionData>

    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): ApiEnvelope<AuthSessionData>

    @POST("auth/logout")
    suspend fun logout(): ApiEnvelope<JsonObject>

    @GET("auth/me")
    suspend fun getMe(): ApiEnvelope<AuthUserData>

    @PUT("auth/profile")
    suspend fun updateProfile(@Body request: UpdateProfileRequest): ApiEnvelope<AuthUserData>

    @PUT("auth/password")
    suspend fun updatePassword(@Body request: UpdatePasswordRequest): ApiEnvelope<AuthUserData>

    @POST("auth/forgot-password")
    suspend fun forgotPassword(@Body request: ForgotPasswordRequest): ApiEnvelope<JsonObject>

    @POST("auth/reset-password")
    suspend fun resetPassword(@Body request: ResetPasswordRequest): ApiEnvelope<JsonObject>
}
