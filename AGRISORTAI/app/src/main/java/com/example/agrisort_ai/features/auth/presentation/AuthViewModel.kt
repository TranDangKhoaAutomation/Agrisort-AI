package com.example.agrisort_ai.features.auth.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.agrisort_ai.core.data.UserSessionStore
import com.example.agrisort_ai.core.network.Resource
import com.example.agrisort_ai.features.auth.domain.model.User
import com.example.agrisort_ai.features.auth.domain.usecase.AuthUseCases
import com.example.agrisort_ai.features.dashboard.domain.model.AppRoleDestination
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import javax.inject.Inject

sealed class AuthUiState {
    object Idle : AuthUiState()
    object Loading : AuthUiState()
    data class Success(val user: User? = null, val message: String? = null) : AuthUiState()
    data class Error(val message: String) : AuthUiState()
}

sealed class SessionState {
    object Loading : SessionState()
    object Authenticated : SessionState()
    object Unauthenticated : SessionState()
    data class Inactive(val message: String) : SessionState()
}

@HiltViewModel
class AuthViewModel @Inject constructor(
    private val authUseCases: AuthUseCases,
    private val sessionSnapshotStore: UserSessionStore
) : ViewModel() {

    private val _uiState = MutableStateFlow<AuthUiState>(AuthUiState.Idle)
    val uiState: StateFlow<AuthUiState> = _uiState.asStateFlow()

    private val _currentUser = MutableStateFlow<User?>(null)
    val currentUser: StateFlow<User?> = _currentUser.asStateFlow()

    private val _sessionState = MutableStateFlow<SessionState>(SessionState.Loading)
    val sessionState: StateFlow<SessionState> = _sessionState.asStateFlow()

    private val _roleDestination = MutableStateFlow(AppRoleDestination.UNKNOWN)
    val roleDestination: StateFlow<AppRoleDestination> = _roleDestination.asStateFlow()

    init {
        authUseCases.observeToken()
            .distinctUntilChanged()
            .onEach { token ->
                if (token.isNullOrBlank()) {
                    sessionSnapshotStore.clearUser()
                    clearLocalSession(SessionState.Unauthenticated)
                    return@onEach
                }

                val cachedUser = sessionSnapshotStore.cachedUser.first()
                if (cachedUser != null) {
                    _currentUser.value = cachedUser
                    evaluateSessionByUser(cachedUser)
                    _uiState.value = AuthUiState.Success(user = cachedUser)
                    getProfile(showLoading = false, keepCurrentSessionOnFailure = true)
                } else {
                    _sessionState.value = SessionState.Loading
                    getProfile(showLoading = false, keepCurrentSessionOnFailure = false)
                }
            }
            .launchIn(viewModelScope)
    }

    fun login(email: String, password: String) {
        val emailError = validateEmail(email)
        val passwordError = validatePassword(password)

        if (emailError != null || passwordError != null) {
            _uiState.value = AuthUiState.Error(emailError ?: passwordError!!)
            return
        }

        authUseCases.login(email, password).onEach { result ->
            when (result) {
                is Resource.Loading -> _uiState.value = AuthUiState.Loading
                is Resource.Success -> {
                    _currentUser.value = result.data
                    evaluateSessionByUser(result.data)
                    _uiState.value = AuthUiState.Success(user = result.data)
                    getProfile(showLoading = false, keepCurrentSessionOnFailure = true)
                }
                is Resource.Error -> {
                    _roleDestination.value = AppRoleDestination.UNKNOWN
                    _sessionState.value = SessionState.Unauthenticated
                    _uiState.value = AuthUiState.Error(result.message)
                }
            }
        }.launchIn(viewModelScope)
    }

    fun register(
        fullName: String,
        email: String,
        password: String,
        organizationName: String,
        representativeName: String,
        phone: String,
        address: String,
        region: String,
        taxCode: String
    ) {
        val emailError = validateEmail(email)
        val passwordError = validatePassword(password)

        if (fullName.isBlank()) {
            _uiState.value = AuthUiState.Error("Họ và tên không được để trống")
            return
        }
        if (emailError != null || passwordError != null) {
            _uiState.value = AuthUiState.Error(emailError ?: passwordError!!)
            return
        }

        val normalizedRepresentativeName = representativeName.ifBlank { fullName }

        authUseCases.register(
            fullName = fullName,
            email = email,
            password = password,
            organizationName = organizationName,
            representativeName = normalizedRepresentativeName,
            phone = phone,
            address = address,
            region = region,
            taxCode = taxCode
        ).onEach { result ->
            when (result) {
                is Resource.Loading -> _uiState.value = AuthUiState.Loading
                is Resource.Success -> _uiState.value = AuthUiState.Success(message = result.data)
                is Resource.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun updateProfile(
        fullName: String,
        email: String,
        role: String,
        organizationName: String,
        representativeName: String,
        phone: String,
        address: String,
        region: String,
        taxCode: String
    ) {
        val emailError = validateEmail(email)
        if (fullName.isBlank()) {
            _uiState.value = AuthUiState.Error("Họ và tên không được để trống")
            return
        }
        if (emailError != null) {
            _uiState.value = AuthUiState.Error(emailError)
            return
        }

        val normalizedRole = role.trim().lowercase()
        val requiresOrganization = normalizedRole == "partner"
        if (requiresOrganization && organizationName.isBlank()) {
            _uiState.value = AuthUiState.Error("Tên tổ chức là bắt buộc cho tài khoản đối tác")
            return
        }

        val normalizedRepresentativeName = representativeName.ifBlank { fullName }

        authUseCases.updateProfile(
            fullName = fullName,
            email = email,
            organizationName = organizationName,
            representativeName = normalizedRepresentativeName,
            phone = phone,
            address = address,
            region = region,
            taxCode = taxCode
        ).onEach { result ->
            when (result) {
                is Resource.Loading -> _uiState.value = AuthUiState.Loading
                is Resource.Success -> {
                    _currentUser.value = result.data
                    evaluateSessionByUser(result.data)
                    _uiState.value = AuthUiState.Success(
                        user = result.data,
                        message = "Cập nhật hồ sơ thành công"
                    )
                }
                is Resource.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun forgotPassword(email: String) {
        val emailError = validateEmail(email)
        if (emailError != null) {
            _uiState.value = AuthUiState.Error(emailError)
            return
        }

        authUseCases.forgotPassword(email).onEach { result ->
            when (result) {
                is Resource.Loading -> _uiState.value = AuthUiState.Loading
                is Resource.Success -> _uiState.value = AuthUiState.Success(message = result.data)
                is Resource.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun updatePassword(
        currentPassword: String,
        newPassword: String,
        confirmPassword: String
    ) {
        if (currentPassword.isBlank()) {
            _uiState.value = AuthUiState.Error("Mật khẩu hiện tại không được để trống")
            return
        }
        val passwordError = validatePassword(newPassword)
        if (passwordError != null) {
            _uiState.value = AuthUiState.Error(passwordError)
            return
        }
        val confirmError = validatePasswordConfirmation(newPassword, confirmPassword)
        if (confirmError != null) {
            _uiState.value = AuthUiState.Error(confirmError)
            return
        }

        authUseCases.updatePassword(
            currentPassword = currentPassword,
            newPassword = newPassword,
            confirmPassword = confirmPassword
        ).onEach { result ->
            when (result) {
                is Resource.Loading -> _uiState.value = AuthUiState.Loading
                is Resource.Success -> _uiState.value = AuthUiState.Success(
                    user = _currentUser.value,
                    message = result.data
                )
                is Resource.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun resetPassword(
        token: String,
        newPassword: String,
        confirmPassword: String
    ) {
        if (token.isBlank()) {
            _uiState.value = AuthUiState.Error("Token đặt lại mật khẩu không được để trống")
            return
        }
        val passwordError = validatePassword(newPassword)
        if (passwordError != null) {
            _uiState.value = AuthUiState.Error(passwordError)
            return
        }
        val confirmError = validatePasswordConfirmation(newPassword, confirmPassword)
        if (confirmError != null) {
            _uiState.value = AuthUiState.Error(confirmError)
            return
        }

        authUseCases.resetPassword(
            token = token,
            newPassword = newPassword,
            confirmPassword = confirmPassword
        ).onEach { result ->
            when (result) {
                is Resource.Loading -> _uiState.value = AuthUiState.Loading
                is Resource.Success -> _uiState.value = AuthUiState.Success(message = result.data)
                is Resource.Error -> _uiState.value = AuthUiState.Error(result.message)
            }
        }.launchIn(viewModelScope)
    }

    fun getProfile() {
        getProfile(showLoading = true, keepCurrentSessionOnFailure = false)
    }

    private fun getProfile(
        showLoading: Boolean,
        keepCurrentSessionOnFailure: Boolean
    ) {
        authUseCases.getMe().onEach { result ->
            when (result) {
                is Resource.Loading -> if (showLoading) {
                    _uiState.value = AuthUiState.Loading
                }
                is Resource.Success -> {
                    _currentUser.value = result.data
                    evaluateSessionByUser(result.data)
                    _uiState.value = AuthUiState.Success(user = result.data)
                }
                is Resource.Error -> {
                    if (result.code == 401 || result.code == 419) {
                        clearLocalSession(SessionState.Unauthenticated)
                    }
                    if (keepCurrentSessionOnFailure && _currentUser.value != null) {
                        _uiState.value = AuthUiState.Success(user = _currentUser.value)
                    } else {
                        _uiState.value = AuthUiState.Error(result.message)
                    }
                }
            }
        }.launchIn(viewModelScope)
    }

    fun logout() {
        authUseCases.logout().onEach { result ->
            when (result) {
                is Resource.Loading -> _uiState.value = AuthUiState.Loading
                is Resource.Success -> {
                    clearLocalSession(SessionState.Unauthenticated)
                    _uiState.value = AuthUiState.Idle
                }
                is Resource.Error -> {
                    clearLocalSession(SessionState.Unauthenticated)
                    _uiState.value = AuthUiState.Error(result.message)
                }
            }
        }.launchIn(viewModelScope)
    }

    private fun clearLocalSession(sessionState: SessionState) {
        _currentUser.value = null
        _roleDestination.value = AppRoleDestination.UNKNOWN
        _sessionState.value = sessionState
    }

    private fun evaluateSessionByUser(user: User) {
        val role = user.role.trim().lowercase()
        val status = user.status.trim().lowercase()

        _roleDestination.value = mapRoleDestination(role)

        if (role != "admin" && status != "active") {
            _sessionState.value = SessionState.Inactive(
                message = "Tài khoản hiện chưa được kích hoạt. Vui lòng liên hệ quản trị viên."
            )
            return
        }

        _sessionState.value = SessionState.Authenticated
    }

    private fun mapRoleDestination(role: String): AppRoleDestination {
        return when (role) {
            "admin" -> AppRoleDestination.ADMIN
            "partner", "farmer" -> AppRoleDestination.PARTNER
            "transporter", "warehouse", "seller" -> AppRoleDestination.SUPPLY
            "buyer", "customer", "user" -> AppRoleDestination.BUYER
            else -> AppRoleDestination.BUYER
        }
    }

    private fun validateEmail(email: String): String? {
        if (email.isBlank()) return "Email không được để trống"
        if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) return "Email không đúng định dạng"
        return null
    }

    private fun validatePassword(password: String): String? {
        if (password.isBlank()) return "Mật khẩu không được để trống"
        if (password.length < 8) return "Mật khẩu phải có ít nhất 8 ký tự"
        return null
    }

    private fun validatePasswordConfirmation(password: String, confirmation: String): String? {
        if (confirmation.isBlank()) return "Xác nhận mật khẩu không được để trống"
        if (password != confirmation) return "Mật khẩu xác nhận không khớp"
        return null
    }

    fun clearError() {
        _uiState.value = AuthUiState.Idle
    }
}
