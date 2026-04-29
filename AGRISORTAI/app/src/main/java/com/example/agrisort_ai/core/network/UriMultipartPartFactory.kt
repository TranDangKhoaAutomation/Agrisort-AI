package com.example.agrisort_ai.core.network

import android.content.ContentResolver
import android.content.Context
import android.net.Uri
import android.provider.OpenableColumns
import android.webkit.MimeTypeMap
import dagger.hilt.android.qualifiers.ApplicationContext
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import java.io.IOException
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class UriMultipartPartFactory @Inject constructor(
    @ApplicationContext private val context: Context
) : UploadPartFactory {

    override fun textPart(value: String): RequestBody {
        return value.toRequestBody("text/plain".toMediaTypeOrNull())
    }

    @Throws(IOException::class)
    override fun filePart(fieldName: String, uri: Uri): MultipartBody.Part {
        val fileName = resolveFileName(uri, fieldName)
        val mimeType = resolveMimeType(uri, fileName)
        val cacheFile = copyToCache(uri, fileName)
        val requestBody = cacheFile.asRequestBody(mimeType.toMediaTypeOrNull())
        return MultipartBody.Part.createFormData(fieldName, fileName, requestBody)
    }

    @Throws(IOException::class)
    override fun fileParts(fieldName: String, uris: List<Uri>): List<MultipartBody.Part> {
        return uris.map { uri -> filePart(fieldName, uri) }
    }

    @Throws(IOException::class)
    private fun copyToCache(uri: Uri, fileName: String): File {
        val inputStream = context.contentResolver.openInputStream(uri)
            ?: throw IOException("Không thể đọc tệp đã chọn.")

        val safeName = fileName.ifBlank { "upload_${System.currentTimeMillis()}" }
        val cacheFile = File(context.cacheDir, safeName)
        inputStream.use { input ->
            cacheFile.outputStream().use { output ->
                input.copyTo(output)
            }
        }
        return cacheFile
    }

    private fun resolveMimeType(uri: Uri, fileName: String): String {
        val fromResolver = context.contentResolver.getType(uri)
        if (!fromResolver.isNullOrBlank()) {
            return fromResolver
        }

        val extension = fileName.substringAfterLast('.', "").lowercase()
        return MimeTypeMap.getSingleton().getMimeTypeFromExtension(extension)
            ?: "application/octet-stream"
    }

    private fun resolveFileName(uri: Uri, fieldName: String): String {
        queryDisplayName(uri)?.takeIf { it.isNotBlank() }?.let { return it }

        val fromPath = uri.lastPathSegment
            ?.substringAfterLast('/')
            ?.substringAfterLast(':')
            ?.takeIf { it.isNotBlank() }
        if (fromPath != null) {
            return fromPath
        }

        return "${fieldName}_${System.currentTimeMillis()}"
    }

    private fun queryDisplayName(uri: Uri): String? {
        if (uri.scheme != ContentResolver.SCHEME_CONTENT) {
            return null
        }

        return context.contentResolver.query(
            uri,
            arrayOf(OpenableColumns.DISPLAY_NAME),
            null,
            null,
            null
        )?.use { cursor ->
            val index = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
            if (index == -1 || !cursor.moveToFirst()) {
                null
            } else {
                cursor.getString(index)
            }
        }
    }
}
