package com.example.agrisort_ai.features.auth.data.repository

import com.example.agrisort_ai.core.data.TokenStore
import com.example.agrisort_ai.core.data.UserSessionStore
import com.example.agrisort_ai.core.network.Resource
import com.example.agrisort_ai.features.auth.data.remote.AuthApi
import com.example.agrisort_ai.features.auth.data.remote.ForgotPasswordRequest
import com.example.agrisort_ai.features.auth.data.remote.LoginRequest
import com.example.agrisort_ai.features.auth.data.remote.RegisterRequest
import com.example.agrisort_ai.features.auth.data.remote.ResetPasswordRequest
import com.example.agrisort_ai.features.auth.data.remote.UpdatePasswordRequest
import com.example.agrisort_ai.features.auth.data.remote.UpdateProfileRequest
import com.example.agrisort_ai.features.auth.domain.model.User
import com.example.agrisort_ai.features.auth.domain.repository.AuthRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import org.json.JSONObject
import retrofit2.HttpException
import java.io.IOException
import javax.inject.Inject

class AuthRepositoryImpl @Inject constructor(
    private val api: AuthApi,
    private val tokenStore: TokenStore,
    private val sessionSnapshotStore: UserSessionStore
) : AuthRepository {

    override fun login(email: String, password: String): Flow<Resource<User>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.login(LoginRequest(email, password))
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }

            val sessionData = response.data
            val accessToken = sessionData?.accessToken
            val user = sessionData?.user

            if (accessToken.isNullOrBlank() || user == null) {
                emit(Resource.Error("Phản hồi đăng nhập không hợp lệ từ máy chủ."))
                return@flow
            }

            tokenStore.saveToken(accessToken)
            sessionSnapshotStore.saveUser(user)
            emit(Resource.Success(user))
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun register(
        fullName: String,
        email: String,
        password: String,
        organizationName: String,
        representativeName: String,
        phone: String,
        address: String,
        region: String,
        taxCode: String
    ): Flow<Resource<String>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.register(
                RegisterRequest(
                    fullName = fullName,
                    email = email,
                    password = password,
                    organizationName = organizationName,
                    representativeName = representativeName,
                    phone = phone,
                    address = address,
                    region = region,
                    taxCode = taxCode
                )
            )

            if (response.success) {
                emit(Resource.Success(response.message))
            } else {
                emit(Resource.Error(response.message))
            }
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun logout(): Flow<Resource<Unit>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.logout()
            clearSessionLocally()
            if (response.success) {
                emit(Resource.Success(Unit))
            } else {
                emit(Resource.Error(response.message))
            }
        } catch (_: Exception) {
            clearSessionLocally()
            emit(Resource.Success(Unit))
        }
    }

    override fun getMe(): Flow<Resource<User>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.getMe()
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }

            val user = response.data?.user
            if (user != null) {
                sessionSnapshotStore.saveUser(user)
                emit(Resource.Success(user))
            } else {
                emit(Resource.Error("Không tìm thấy thông tin người dùng trong phản hồi."))
            }
        } catch (e: Exception) {
            if (e is HttpException && (e.code() == 401 || e.code() == 419)) {
                clearSessionLocally()
            }
            emit(handleError(e))
        }
    }

    override fun updateProfile(
        fullName: String,
        email: String,
        organizationName: String,
        representativeName: String,
        phone: String,
        address: String,
        region: String,
        taxCode: String
    ): Flow<Resource<User>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.updateProfile(
                UpdateProfileRequest(
                    fullName = fullName,
                    email = email,
                    organizationName = organizationName,
                    representativeName = representativeName,
                    phone = phone,
                    address = address,
                    region = region,
                    taxCode = taxCode
                )
            )
            if (!response.success) {
                emit(Resource.Error(response.message))
                return@flow
            }

            val user = response.data?.user
            if (user != null) {
                sessionSnapshotStore.saveUser(user)
                emit(Resource.Success(user))
            } else {
                emit(Resource.Error("Không tìm thấy thông tin hồ sơ sau khi cập nhật."))
            }
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun updatePassword(
        currentPassword: String,
        newPassword: String,
        confirmPassword: String
    ): Flow<Resource<String>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.updatePassword(
                UpdatePasswordRequest(
                    currentPassword = currentPassword,
                    password = newPassword,
                    passwordConfirmation = confirmPassword
                )
            )

            if (response.success) {
                emit(Resource.Success(response.message))
            } else {
                emit(Resource.Error(response.message))
            }
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun forgotPassword(email: String): Flow<Resource<String>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.forgotPassword(ForgotPasswordRequest(email))
            if (response.success) {
                emit(Resource.Success(response.message))
            } else {
                emit(Resource.Error(response.message))
            }
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun resetPassword(
        token: String,
        newPassword: String,
        confirmPassword: String
    ): Flow<Resource<String>> = flow {
        emit(Resource.Loading)
        try {
            val response = api.resetPassword(
                ResetPasswordRequest(
                    token = token,
                    password = newPassword,
                    passwordConfirmation = confirmPassword
                )
            )

            if (response.success) {
                emit(Resource.Success(response.message))
            } else {
                emit(Resource.Error(response.message))
            }
        } catch (e: Exception) {
            emit(handleError(e))
        }
    }

    override fun getToken(): Flow<String?> = tokenStore.token

    private suspend fun clearSessionLocally() {
        tokenStore.clearToken()
        sessionSnapshotStore.clearUser()
    }

    private fun handleError(e: Exception): Resource.Error {
        return when (e) {
            is HttpException -> {
                val serverMessage = extractServerMessage(e)
                val message = when (e.code()) {
                    401 -> serverMessage ?: "Phiên đăng nhập hết hạn"
                    403 -> serverMessage ?: "Bạn không có quyền thực hiện hành động này"
                    404 -> "Không tìm thấy endpoint API (404). Vui lòng kiểm tra API prefix/base URL."
                    419 -> serverMessage ?: "Phiên làm việc hết hạn (419). Vui lòng đăng nhập lại."
                    422 -> serverMessage ?: "Dữ liệu không hợp lệ"
                    429 -> serverMessage ?: "Quá nhiều yêu cầu"
                    500 -> serverMessage ?: "Máy chủ tạm thời lỗi (500). Vui lòng thử lại sau."
                    else -> serverMessage ?: "Lỗi hệ thống (${e.code()})"
                }
                Resource.Error(message, e.code())
            }

            is IOException -> Resource.Error("Lỗi kết nối mạng")
            else -> Resource.Error("Lỗi không xác định: ${e.message}")
        }
    }

    private fun extractServerMessage(e: HttpException): String? {
        val rawBody = try {
            e.response()?.errorBody()?.string()
        } catch (_: Exception) {
            null
        }

        if (rawBody.isNullOrBlank()) return null

        return try {
            val json = JSONObject(rawBody)
            val message = json.optString("message")
            if (message.isNotBlank()) {
                message
            } else {
                val error = json.optString("error")
                if (error.isNotBlank()) error else null
            }
        } catch (_: Exception) {
            null
        }
    }
}
