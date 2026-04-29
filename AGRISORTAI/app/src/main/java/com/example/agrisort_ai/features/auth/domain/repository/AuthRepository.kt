package com.example.agrisort_ai.features.auth.domain.repository

import com.example.agrisort_ai.core.network.Resource
import com.example.agrisort_ai.features.auth.domain.model.User
import kotlinx.coroutines.flow.Flow

interface AuthRepository {
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
    ): Flow<Resource<String>>

    fun login(email: String, password: String): Flow<Resource<User>>
    fun logout(): Flow<Resource<Unit>>
    fun getMe(): Flow<Resource<User>>
    fun updateProfile(
        fullName: String,
        email: String,
        organizationName: String,
        representativeName: String,
        phone: String,
        address: String,
        region: String,
        taxCode: String
    ): Flow<Resource<User>>

    fun updatePassword(currentPassword: String, newPassword: String, confirmPassword: String): Flow<Resource<String>>
    fun forgotPassword(email: String): Flow<Resource<String>>
    fun resetPassword(token: String, newPassword: String, confirmPassword: String): Flow<Resource<String>>
    fun getToken(): Flow<String?>
}
