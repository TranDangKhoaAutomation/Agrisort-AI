package com.example.agrisort_ai.core.data

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject
import javax.inject.Singleton

private val Context.dataStore by preferencesDataStore(name = "auth_prefs")

@Singleton
class TokenManager @Inject constructor(@ApplicationContext private val context: Context) : TokenStore {
    private val TOKEN_KEY = stringPreferencesKey("auth_token")

    override val token: Flow<String?> = context.dataStore.data.map { it[TOKEN_KEY] }

    override suspend fun saveToken(token: String) {
        context.dataStore.edit { it[TOKEN_KEY] = token }
    }

    override suspend fun clearToken() {
        context.dataStore.edit { it.remove(TOKEN_KEY) }
    }
}
