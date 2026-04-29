package com.example.agrisort_ai.ui.components

import android.widget.Toast
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.BoxScope
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Error
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import com.example.agrisort_ai.R
import com.example.agrisort_ai.ui.theme.DeepLeafGreen
import com.example.agrisort_ai.ui.theme.LeafGreen
import com.example.agrisort_ai.ui.theme.MintGreen
import com.example.agrisort_ai.ui.theme.SuccessGreen
import com.example.agrisort_ai.ui.theme.WarningOrange

enum class MessageType {
    ERROR,
    SUCCESS,
    INFO
}

@Composable
fun AppToastEffect(
    message: String?,
    eventKey: Any?
) {
    val context = LocalContext.current

    LaunchedEffect(message, eventKey) {
        if (!message.isNullOrBlank() && eventKey != null) {
            Toast.makeText(context, message, Toast.LENGTH_SHORT).show()
        }
    }
}

@Composable
fun AuthGradientBackground(
    modifier: Modifier = Modifier,
    content: @Composable BoxScope.() -> Unit
) {
    Box(
        modifier = modifier.background(
            Brush.verticalGradient(
                colors = listOf(
                    MintGreen.copy(alpha = 0.35f),
                    MaterialTheme.colorScheme.background,
                    MaterialTheme.colorScheme.background
                )
            )
        ),
        content = content
    )
}

@Composable
fun AppCard(
    modifier: Modifier = Modifier,
    content: @Composable () -> Unit
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(24.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surface
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        content = { content() }
    )
}

@Composable
fun AppMessage(
    message: String,
    type: MessageType,
    modifier: Modifier = Modifier
) {
    val (icon, containerColor, contentColor) = when (type) {
        MessageType.ERROR -> Triple(
            Icons.Default.Error,
            MaterialTheme.colorScheme.error.copy(alpha = 0.12f),
            MaterialTheme.colorScheme.error
        )

        MessageType.SUCCESS -> Triple(
            Icons.Default.CheckCircle,
            SuccessGreen.copy(alpha = 0.12f),
            SuccessGreen
        )

        MessageType.INFO -> Triple(
            Icons.Default.Info,
            WarningOrange.copy(alpha = 0.16f),
            DeepLeafGreen
        )
    }

    Row(
        modifier = modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(14.dp))
            .background(containerColor)
            .padding(horizontal = 12.dp, vertical = 10.dp),
        horizontalArrangement = Arrangement.spacedBy(8.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(imageVector = icon, contentDescription = null, tint = contentColor)
        Text(
            text = message,
            color = contentColor,
            style = MaterialTheme.typography.bodySmall
        )
    }
}

@Composable
fun AppTextField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    modifier: Modifier = Modifier,
    singleLine: Boolean = true,
    supportingText: String? = null
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        modifier = modifier.fillMaxWidth(),
        singleLine = singleLine,
        shape = RoundedCornerShape(14.dp),
        supportingText = supportingText?.let {
            { Text(text = it) }
        }
    )
}

@Composable
fun AppPasswordField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    modifier: Modifier = Modifier,
    supportingText: String? = null
) {
    var visible by remember { mutableStateOf(false) }
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        modifier = modifier.fillMaxWidth(),
        singleLine = true,
        shape = RoundedCornerShape(14.dp),
        visualTransformation = if (visible) VisualTransformation.None else PasswordVisualTransformation(),
        trailingIcon = {
            IconButton(onClick = { visible = !visible }) {
                Icon(
                    imageVector = if (visible) Icons.Default.Visibility else Icons.Default.VisibilityOff,
                    contentDescription = if (visible) {
                        stringResource(R.string.ui_hide_password)
                    } else {
                        stringResource(R.string.ui_show_password)
                    },
                    tint = LeafGreen
                )
            }
        },
        supportingText = supportingText?.let {
            { Text(text = it) }
        }
    )
}

@Composable
fun ScreenHeader(
    title: String,
    subtitle: String,
    modifier: Modifier = Modifier,
    showBrandMark: Boolean = false
) {
    val centered = showBrandMark
    Column(
        modifier = modifier,
        verticalArrangement = Arrangement.spacedBy(if (centered) 14.dp else 8.dp),
        horizontalAlignment = if (centered) Alignment.CenterHorizontally else Alignment.Start
    ) {
        if (showBrandMark) {
            AgriSortBrandMark(modifier = Modifier.fillMaxWidth(0.68f))
        }
        Text(
            text = title,
            style = MaterialTheme.typography.headlineMedium,
            color = MaterialTheme.colorScheme.onSurface,
            modifier = Modifier.fillMaxWidth(),
            textAlign = if (centered) TextAlign.Center else TextAlign.Start
        )
        Text(
            text = subtitle,
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.fillMaxWidth(),
            textAlign = if (centered) TextAlign.Center else TextAlign.Start
        )
    }
}

@Composable
fun AgriSortBrandMark(
    modifier: Modifier = Modifier
) {
    Image(
        painter = painterResource(R.drawable.agrisort_logo),
        contentDescription = stringResource(R.string.app_name),
        modifier = modifier.aspectRatio(677f / 369f),
        contentScale = ContentScale.Fit
    )
}

@Composable
fun LoadingState(
    text: String = "",
    modifier: Modifier = Modifier
) {
    val loadingText = text.trim()
    Column(
        modifier = modifier.fillMaxSize(),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        CircularProgressIndicator()
        if (loadingText.isNotEmpty()) {
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = loadingText,
                style = MaterialTheme.typography.bodyMedium
            )
        }
    }
}

@Composable
fun EmptyState(
    title: String,
    message: String,
    actionLabel: String = "",
    onAction: () -> Unit,
    modifier: Modifier = Modifier
) {
    val resolvedActionLabel = if (actionLabel.isBlank()) stringResource(R.string.ui_retry) else actionLabel
    AppCard(modifier = modifier.fillMaxWidth()) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(
                text = title,
                style = MaterialTheme.typography.titleMedium,
                modifier = Modifier.fillMaxWidth(),
                textAlign = TextAlign.Center
            )
            Text(
                text = message,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.fillMaxWidth(),
                textAlign = TextAlign.Center
            )
            OutlinedButton(onClick = onAction) {
                Icon(imageVector = Icons.Default.Refresh, contentDescription = null)
                Text(" $resolvedActionLabel")
            }
        }
    }
}
