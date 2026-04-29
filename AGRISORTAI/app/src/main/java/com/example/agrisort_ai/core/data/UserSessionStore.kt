package com.example.agrisort_ai.core.data

import com.example.agrisort_ai.features.auth.domain.model.User
import kotlinx.coroutines.flow.Flow

interface UserSessionStore {
    val cachedUser: Flow<User?>

    suspend fun saveUser(user: User)

    suspend fun clearUser()
}
