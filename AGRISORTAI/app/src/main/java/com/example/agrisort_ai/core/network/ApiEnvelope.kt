package com.example.agrisort_ai.core.network

import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonElement

@Serializable
data class ApiEnvelope<T>(
    val success: Boolean = false,
    val message: String = "",
    val data: T? = null,
    val errors: JsonElement? = null,
    val meta: JsonElement? = null
)
