package com.example.opticalsystem.di

import com.example.opticalsystem.data.api.AuthApi
import com.example.opticalsystem.data.api.AppointmentApi
import com.example.opticalsystem.data.api.BillApi
import com.example.opticalsystem.data.api.ConversationApi
import com.example.opticalsystem.data.api.FeedbackApi
import com.example.opticalsystem.data.api.OrderApi
import com.example.opticalsystem.data.api.ProductApi
import com.example.opticalsystem.util.TokenManager
import com.google.gson.GsonBuilder
import com.google.gson.Strictness
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import kotlinx.coroutines.runBlocking
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {

    // For emulator use 10.0.2.2; for physical device use your PC's local IP
    // harvey: 192.168.254.101
    private const val BASE_URL = "http://192.168.254.101:6969/api/v1/"

    @Provides
    @Singleton
    fun provideAuthInterceptor(tokenManager: TokenManager): Interceptor {
        return Interceptor { chain ->
            val token = runBlocking { tokenManager.getToken() }
            val request = chain.request().newBuilder().apply {
                addHeader("Accept", "application/json")
                if (token != null) {
                    addHeader("Authorization", "Bearer $token")
                }
            }.build()
            chain.proceed(request)
        }
    }

    @Provides
    @Singleton
    fun provideOkHttpClient(authInterceptor: Interceptor): OkHttpClient {
        return OkHttpClient.Builder()
            .addInterceptor(authInterceptor)
            .addInterceptor(HttpLoggingInterceptor().apply {
                // Level.BODY reads the full response body; OkHttp still returns the same
                // ResponseBody instance, which can leave Gson/Retrofit reading a truncated or
                // exhausted stream (EOF at $.meta, etc.). HEADERS logs status + headers only.
                level = HttpLoggingInterceptor.Level.HEADERS
            })
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .build()
    }

    @Provides
    @Singleton
    fun provideRetrofit(okHttpClient: OkHttpClient): Retrofit {
        // Gson 2.11+ uses Strictness.STRICT by default; some Laravel payloads (e.g. unescaped
        // control characters in category.description) fail parsing until the reader is lenient.
        val gson = GsonBuilder()
            .setStrictness(Strictness.LENIENT)
            .create()
        return Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create(gson))
            .build()
    }

    @Provides
    @Singleton
    fun provideAuthApi(retrofit: Retrofit): AuthApi {
        return retrofit.create(AuthApi::class.java)
    }

    @Provides
    @Singleton
    fun provideProductApi(retrofit: Retrofit): ProductApi {
        return retrofit.create(ProductApi::class.java)
    }

    @Provides
    @Singleton
    fun provideOrderApi(retrofit: Retrofit): OrderApi {
        return retrofit.create(OrderApi::class.java)
    }

    @Provides
    @Singleton
    fun provideBillApi(retrofit: Retrofit): BillApi {
        return retrofit.create(BillApi::class.java)
    }

    @Provides
    @Singleton
    fun provideFeedbackApi(retrofit: Retrofit): FeedbackApi {
        return retrofit.create(FeedbackApi::class.java)
    }

    @Provides
    @Singleton
    fun provideAppointmentApi(retrofit: Retrofit): AppointmentApi {
        return retrofit.create(AppointmentApi::class.java)
    }

    @Provides
    @Singleton
    fun provideConversationApi(retrofit: Retrofit): ConversationApi {
        return retrofit.create(ConversationApi::class.java)
    }
}
