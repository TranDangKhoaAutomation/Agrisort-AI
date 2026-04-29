package com.example.agrisort_ai.core.local

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "scan_history")
data class ScanHistoryEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    @ColumnInfo(name = "token") val token: String,
    @ColumnInfo(name = "source") val source: String,
    @ColumnInfo(name = "status") val status: String,
    @ColumnInfo(name = "message") val message: String?,
    @ColumnInfo(name = "scanned_at") val scannedAt: Long
)
