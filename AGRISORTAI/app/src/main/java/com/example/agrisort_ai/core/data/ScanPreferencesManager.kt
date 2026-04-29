package com.example.agrisort_ai.core.data

import android.content.Context
import androidx.datastore.preferences.core.booleanPreferencesKey
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.preferencesDataStore
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

private val Context.scanPreferencesDataStore by preferencesDataStore(name = "scan_prefs")

@Singleton
class ScanPreferencesManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val deduplicateHistoryKey = booleanPreferencesKey("deduplicate_scan_history")

    val deduplicateScanHistory: Flow<Boolean> = context.scanPreferencesDataStore.data.map { preferences ->
        preferences[deduplicateHistoryKey] ?: true
    }

    suspend fun setDeduplicateScanHistory(enabled: Boolean) {
        context.scanPreferencesDataStore.edit { preferences ->
            preferences[deduplicateHistoryKey] = enabled
        }
    }
}
