package com.example.agrisort_ai.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable

private val LightColors = lightColorScheme(
    primary = LeafGreen,
    onPrimary = SurfaceLight,
    primaryContainer = MintGreen,
    onPrimaryContainer = DeepLeafGreen,
    secondary = SkyBlue,
    onSecondary = SurfaceLight,
    tertiary = SunOrange,
    onTertiary = SurfaceLight,
    background = BackgroundLight,
    onBackground = OnSurfaceLight,
    surface = SurfaceLight,
    onSurface = OnSurfaceLight,
    surfaceVariant = SurfaceVariantLight,
    onSurfaceVariant = DeepLeafGreen,
    error = DangerRed
)

private val DarkColors = darkColorScheme(
    primary = MintGreen,
    onPrimary = DeepLeafGreen,
    primaryContainer = DeepLeafGreen,
    onPrimaryContainer = MintGreen,
    secondary = ColorBlueDark,
    onSecondary = SurfaceDark,
    tertiary = ColorOrangeDark,
    onTertiary = SurfaceDark,
    background = BackgroundDark,
    onBackground = OnSurfaceDark,
    surface = SurfaceDark,
    onSurface = OnSurfaceDark,
    surfaceVariant = SurfaceVariantDark,
    onSurfaceVariant = MintGreen,
    error = ColorErrorDark
)

@Composable
fun AgriSortAITheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit
) {
    MaterialTheme(
        colorScheme = if (darkTheme) DarkColors else LightColors,
        typography = Typography,
        content = content
    )
}
