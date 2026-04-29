package com.example.agrisort_ai.core.network

import kotlinx.serialization.json.JsonArray
import kotlinx.serialization.json.JsonElement
import kotlinx.serialization.json.JsonNull
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.JsonPrimitive
import kotlinx.serialization.json.booleanOrNull
import kotlinx.serialization.json.contentOrNull
import kotlinx.serialization.json.intOrNull
import kotlinx.serialization.json.longOrNull

fun JsonElement?.asJsonObjectOrNull(): JsonObject? = (this as? JsonObject)

fun JsonElement?.asJsonArrayOrNull(): JsonArray? = (this as? JsonArray)

fun JsonObject.string(key: String): String? = (this[key] as? JsonPrimitive)?.contentOrNull

fun JsonObject.int(key: String): Int? = (this[key] as? JsonPrimitive)?.intOrNull

fun JsonObject.long(key: String): Long? = (this[key] as? JsonPrimitive)?.longOrNull

fun JsonObject.bool(key: String): Boolean? = (this[key] as? JsonPrimitive)?.booleanOrNull

fun JsonElement?.extractArray(preferredKeys: List<String> = emptyList()): JsonArray {
    if (this == null || this is JsonNull) return JsonArray(emptyList())

    if (this is JsonArray) return this
    if (this is JsonObject) {
        preferredKeys.forEach { key ->
            val value = this[key]
            if (value is JsonArray) return value
        }
        val firstArray = this.values.firstOrNull { it is JsonArray } as? JsonArray
        if (firstArray != null) return firstArray
    }
    return JsonArray(emptyList())
}

fun JsonObject.extractObject(preferredKeys: List<String> = emptyList()): JsonObject {
    preferredKeys.forEach { key ->
        val value = this[key]
        if (value is JsonObject) return value
    }
    return this
}

fun jsonString(value: String?): JsonPrimitive = JsonPrimitive(value ?: "")
