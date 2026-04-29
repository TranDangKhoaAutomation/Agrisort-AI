package com.example.agrisort_ai.core.data

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.example.agrisort_ai.features.auth.domain.model.User
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.serialization.json.Json

private val Context.authSessionDataStore by preferencesDataStore(name = "auth_session_prefs")

@Singleton
class SessionSnapshotStore @Inject constructor(
    @ApplicationContext private val context: Context
) : UserSessionStore {
    private val cachedUserKey = stringPreferencesKey("cached_user_json")
    private val json = Json { ignoreUnknownKeys = true }

    override val cachedUser: Flow<User?> = context.authSessionDataStore.data.map { preferences ->
        preferences[cachedUserKey]?.let { raw ->
            runCatching { json.decodeFromString<User>(raw) }.getOrNull()
        }
    }

    override suspend fun saveUser(user: User) {
        context.authSessionDataStore.edit { preferences ->
            preferences[cachedUserKey] = json.encodeToString(User.serializer(), user)
        }
    }

    override suspend fun clearUser() {
        context.authSessionDataStore.edit { preferences ->
            preferences.remove(cachedUserKey)
        }
    }
}
