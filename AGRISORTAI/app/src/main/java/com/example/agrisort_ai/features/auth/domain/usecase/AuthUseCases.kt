package com.example.agrisort_ai.features.auth.domain.usecase

import com.example.agrisort_ai.features.auth.domain.repository.AuthRepository
import javax.inject.Inject

data class AuthUseCases(
    val login: LoginUseCase,
    val register: RegisterUseCase,
    val logout: LogoutUseCase,
    val observeToken: ObserveTokenUseCase,
    val getMe: GetMeUseCase,
    val updateProfile: UpdateProfileUseCase,
    val forgotPassword: ForgotPasswordUseCase,
    val updatePassword: UpdatePasswordUseCase,
    val resetPassword: ResetPasswordUseCase
)

class LoginUseCase @Inject constructor(private val repository: AuthRepository) {
    operator fun invoke(email: String, pword: String) = repository.login(email, pword)
}

class RegisterUseCase @Inject constructor(private val repository: AuthRepository) {
    operator fun invoke(
        fullName: String,
        email: String,
        password: String,
        organizationName: String,
        representativeName: String,
        phone: String,
        address: String,
        region: String,
        taxCode: String
    ) = repository.register(
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
}

class LogoutUseCase @Inject constructor(private val repository: AuthRepository) {
    operator fun invoke() = repository.logout()
}

class ObserveTokenUseCase @Inject constructor(private val repository: AuthRepository) {
    operator fun invoke() = repository.getToken()
}

class GetMeUseCase @Inject constructor(private val repository: AuthRepository) {
    operator fun invoke() = repository.getMe()
}

class UpdateProfileUseCase @Inject constructor(private val repository: AuthRepository) {
    operator fun invoke(
        fullName: String,
        email: String,
        organizationName: String,
        representativeName: String,
        phone: String,
        address: String,
        region: String,
        taxCode: String
    ) = repository.updateProfile(
        fullName = fullName,
        email = email,
        organizationName = organizationName,
        representativeName = representativeName,
        phone = phone,
        address = address,
        region = region,
        taxCode = taxCode
    )
}

class ForgotPasswordUseCase @Inject constructor(private val repository: AuthRepository) {
    operator fun invoke(email: String) = repository.forgotPassword(email)
}

class UpdatePasswordUseCase @Inject constructor(private val repository: AuthRepository) {
    operator fun invoke(currentPassword: String, newPassword: String, confirmPassword: String) =
        repository.updatePassword(currentPassword, newPassword, confirmPassword)
}

class ResetPasswordUseCase @Inject constructor(private val repository: AuthRepository) {
    operator fun invoke(token: String, newPassword: String, confirmPassword: String) =
        repository.resetPassword(token, newPassword, confirmPassword)
}
