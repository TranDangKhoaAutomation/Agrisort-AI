package com.example.agrisort_ai.core.di

import android.content.Context
import androidx.room.Room
import com.example.agrisort_ai.core.local.AppDatabase
import com.example.agrisort_ai.core.local.ScanHistoryDao
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object LocalModule {

    @Provides
    @Singleton
    fun provideAppDatabase(
        @ApplicationContext context: Context
    ): AppDatabase {
        return Room.databaseBuilder(
            context,
            AppDatabase::class.java,
            "agrisort_ai.db"
        ).fallbackToDestructiveMigration().build()
    }

    @Provides
    @Singleton
    fun provideScanHistoryDao(db: AppDatabase): ScanHistoryDao = db.scanHistoryDao()
}
