package com.example.agrisort_ai.features.auth.domain.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class User(
    val id: Int,
    val email: String,
    @SerialName("full_name") val fullName: String,
    val role: String = "",
    val status: String = "",
    @SerialName("role_label") val roleLabel: String = "",
    @SerialName("partner_profile") val partnerProfile: PartnerProfile? = null
)

@Serializable
data class PartnerProfile(
    val id: String? = null,
    @SerialName("user_id") val userId: String? = null,
    @SerialName("organization_name") val organizationName: String? = null,
    @SerialName("representative_name") val representativeName: String? = null,
    val phone: String? = null,
    val address: String? = null,
    val region: String? = null,
    @SerialName("tax_code") val taxCode: String? = null
)
