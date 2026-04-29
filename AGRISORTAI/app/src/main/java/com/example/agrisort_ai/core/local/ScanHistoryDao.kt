package com.example.agrisort_ai.core.local

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import kotlinx.coroutines.flow.Flow

@Dao
interface ScanHistoryDao {

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(entry: ScanHistoryEntity)

    @Query("SELECT * FROM scan_history ORDER BY scanned_at DESC LIMIT :limit")
    fun observeRecent(limit: Int = 100): Flow<List<ScanHistoryEntity>>

    @Query("DELETE FROM scan_history WHERE token = :token")
    suspend fun deleteByToken(token: String)

    @Query("DELETE FROM scan_history")
    suspend fun clearAll()
}
