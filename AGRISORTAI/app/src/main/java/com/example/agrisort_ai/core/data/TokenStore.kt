package com.example.agrisort_ai.core.data

import kotlinx.coroutines.flow.Flow

interface TokenStore {
    val token: Flow<String?>
    suspend fun saveToken(token: String)
    suspend fun clearToken()
}
