package com.example.agrisort_ai.core.local

import androidx.room.Database
import androidx.room.RoomDatabase

@Database(
    entities = [ScanHistoryEntity::class],
    version = 1,
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    abstract fun scanHistoryDao(): ScanHistoryDao
}
