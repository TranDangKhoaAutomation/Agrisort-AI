package com.example.agrisort_ai.features.auth.di

import com.example.agrisort_ai.core.data.SessionSnapshotStore
import com.example.agrisort_ai.core.data.TokenManager
import com.example.agrisort_ai.core.data.TokenStore
import com.example.agrisort_ai.core.data.UserSessionStore
import com.example.agrisort_ai.features.auth.data.remote.AuthApi
import com.example.agrisort_ai.features.auth.data.repository.AuthRepositoryImpl
import com.example.agrisort_ai.features.auth.domain.repository.AuthRepository
import com.example.agrisort_ai.features.auth.domain.usecase.*
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import retrofit2.Retrofit
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object AuthModule {

    @Provides
    @Singleton
    fun provideAuthApi(retrofit: Retrofit): AuthApi {
        return retrofit.create(AuthApi::class.java)
    }

    @Provides
    @Singleton
    fun provideAuthRepository(repository: AuthRepositoryImpl): AuthRepository {
        return repository
    }

    @Provides
    @Singleton
    fun provideTokenStore(tokenManager: TokenManager): TokenStore {
        return tokenManager
    }

    @Provides
    @Singleton
    fun provideUserSessionStore(store: SessionSnapshotStore): UserSessionStore {
        return store
    }

    @Provides
    @Singleton
    fun provideAuthUseCases(repository: AuthRepository): AuthUseCases {
        return AuthUseCases(
            login = LoginUseCase(repository),
            register = RegisterUseCase(repository),
            logout = LogoutUseCase(repository),
            observeToken = ObserveTokenUseCase(repository),
            getMe = GetMeUseCase(repository),
            updateProfile = UpdateProfileUseCase(repository),
            forgotPassword = ForgotPasswordUseCase(repository),
            updatePassword = UpdatePasswordUseCase(repository),
            resetPassword = ResetPasswordUseCase(repository)
        )
    }
}
