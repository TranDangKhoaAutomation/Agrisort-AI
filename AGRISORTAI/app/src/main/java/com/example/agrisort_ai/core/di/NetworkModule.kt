package com.example.agrisort_ai.core.di

import com.example.agrisort_ai.BuildConfig
import com.example.agrisort_ai.core.data.TokenManager
import com.example.agrisort_ai.core.network.AuthHeaderInterceptor
import com.example.agrisort_ai.core.network.UploadPartFactory
import com.example.agrisort_ai.core.network.UriMultipartPartFactory
import com.jakewharton.retrofit2.converter.kotlinx.serialization.asConverterFactory
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import kotlinx.coroutines.flow.first
import kotlinx.serialization.json.Json
import okhttp3.Interceptor
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {

    @Provides
    @Singleton
    fun provideJson(): Json = Json {
        ignoreUnknownKeys = true
        coerceInputValues = true
    }

    @Provides
    @Singleton
    fun provideAuthInterceptor(tokenManager: TokenManager): Interceptor {
        return AuthHeaderInterceptor(
            fetchToken = { tokenManager.token.first() },
            clearToken = { tokenManager.clearToken() }
        )
    }

    @Provides
    @Singleton
    fun provideOkHttpClient(
        authInterceptor: Interceptor
    ): OkHttpClient {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }
        return OkHttpClient.Builder()
            .addInterceptor(authInterceptor)
            .addInterceptor(logging)
            .build()
    }

    @Provides
    @Singleton
    fun provideUploadPartFactory(factory: UriMultipartPartFactory): UploadPartFactory {
        return factory
    }

    @Provides
    @Singleton
    fun provideRetrofit(okHttpClient: OkHttpClient, json: Json): Retrofit {
        val contentType = "application/json".toMediaType()
        val apiPath = BuildConfig.API_PATH.trim().trim('/')
        val normalizedBaseUrl = "${BuildConfig.API_BASE_URL.trimEnd('/')}/$apiPath/"
        
        return Retrofit.Builder()
            .baseUrl(normalizedBaseUrl)
            .client(okHttpClient)
            .addConverterFactory(json.asConverterFactory(contentType))
            .build()
    }
}
