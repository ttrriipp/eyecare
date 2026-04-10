package com.example.opticalsystem.notifications

import android.content.Context
import androidx.hilt.work.HiltWorker
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import dagger.assisted.Assisted
import dagger.assisted.AssistedInject

@HiltWorker
class OrderStatusWorker @AssistedInject constructor(
    @Assisted appContext: Context,
    @Assisted params: WorkerParameters,
    private val notifier: OrderStatusNotifier,
) : CoroutineWorker(appContext, params) {

    override suspend fun doWork(): Result {
        return try {
            notifier.createNotificationChannel()
            notifier.checkForStatusChanges()
            Result.success()
        } catch (_: Exception) {
            Result.retry()
        }
    }
}
