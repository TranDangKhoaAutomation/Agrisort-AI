package com.example.agrisort_ai.core.network

import android.net.Uri
import okhttp3.MultipartBody
import okhttp3.RequestBody
import java.io.IOException

interface UploadPartFactory {
    fun textPart(value: String): RequestBody

    @Throws(IOException::class)
    fun filePart(fieldName: String, uri: Uri): MultipartBody.Part

    @Throws(IOException::class)
    fun fileParts(fieldName: String, uris: List<Uri>): List<MultipartBody.Part>
}
