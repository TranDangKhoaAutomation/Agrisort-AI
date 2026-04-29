package com.example.agrisort_ai.features.dashboard.presentation

import android.Manifest
import android.content.Context
import android.content.ContextWrapper
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Rect
import android.graphics.RectF
import android.net.Uri
import android.provider.Settings
import androidx.activity.ComponentActivity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.camera.core.Camera
import androidx.camera.core.CameraSelector
import androidx.camera.core.ExperimentalGetImage
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.ImageProxy
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Button
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.CornerRadius
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.agrisort_ai.R
import com.example.agrisort_ai.features.dashboard.domain.model.ScanHistory
import com.example.agrisort_ai.ui.components.AppCard
import com.example.agrisort_ai.ui.components.AppMessage
import com.example.agrisort_ai.ui.components.AppToastEffect
import com.example.agrisort_ai.ui.components.MessageType
import com.google.mlkit.vision.barcode.BarcodeScanner
import com.google.mlkit.vision.barcode.BarcodeScannerOptions
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.common.InputImage
import java.util.concurrent.Executors
import kotlin.math.max
import kotlin.math.min

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TraceScanScreen(
    onNavigateBack: () -> Unit,
    onNavigateQuickScan: () -> Unit,
    onNavigateHistory: () -> Unit,
    onNavigateResult: (String) -> Unit,
    viewModel: TraceViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val activity = context.findActivity()

    val traceScanTitle = stringResource(R.string.trace_scan_title)
    val realtimeTitle = stringResource(R.string.trace_scan_realtime_title)
    val quickModeLabel = stringResource(R.string.trace_scan_quick_mode)
    val flashLabel = stringResource(R.string.trace_scan_flash)
    val permissionMessage = stringResource(R.string.trace_scan_permission_message)
    val grantPermissionLabel = stringResource(R.string.trace_scan_grant_camera)
    val openSettingsLabel = stringResource(R.string.trace_scan_open_settings)
    val fallbackTitle = stringResource(R.string.trace_scan_fallback_title)
    val manualTokenLabel = stringResource(R.string.trace_scan_manual_token)
    val lookupTokenLabel = stringResource(R.string.trace_scan_lookup_token)
    val pickQrImageLabel = stringResource(R.string.trace_scan_pick_qr_image)
    val historyLabel = stringResource(R.string.trace_scan_history)
    val backLabel = stringResource(R.string.trace_scan_back)
    val galleryReadError = stringResource(R.string.trace_scan_gallery_error)
    val tableTitle = stringResource(R.string.trace_scan_table_title)
    val tableDeduplicateOn = stringResource(R.string.trace_scan_table_deduplicate_on)
    val tableDeduplicateOff = stringResource(R.string.trace_scan_table_deduplicate_off)
    val tableEmpty = stringResource(R.string.trace_scan_table_empty)
    val clearTableLabel = stringResource(R.string.trace_scan_clear_table)

    val scanner = remember {
        BarcodeScanning.getClient(
            BarcodeScannerOptions.Builder()
                .setBarcodeFormats(Barcode.FORMAT_QR_CODE)
                .build()
        )
    }

    var hasCameraPermission by remember {
        mutableStateOf(
            ContextCompat.checkSelfPermission(
                context,
                Manifest.permission.CAMERA
            ) == PackageManager.PERMISSION_GRANTED
        )
    }

    val cameraPermissionLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        hasCameraPermission = granted
    }

    val galleryLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.GetContent()
    ) { uri ->
        if (uri != null) {
            scanFromGallery(
                context = context,
                uri = uri,
                scanner = scanner,
                onResult = viewModel::onTokenDetectedFromGallery,
                onError = { viewModel.onScanError(galleryReadError) }
            )
        }
    }

    val shouldShowSettingsHint = activity?.let {
        !hasCameraPermission &&
            !ActivityCompat.shouldShowRequestPermissionRationale(it, Manifest.permission.CAMERA)
    } ?: false

    DisposableEffect(Unit) {
        onDispose { scanner.close() }
    }

    AppToastEffect(
        message = uiState.error ?: uiState.message,
        eventKey = uiState.feedbackId
    )

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(traceScanTitle) }
            )
        }
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            item {
                AppCard(modifier = Modifier.fillMaxWidth()) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Text(
                            text = realtimeTitle,
                            style = androidx.compose.material3.MaterialTheme.typography.titleMedium
                        )
                        OutlinedButton(
                            onClick = onNavigateQuickScan,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text(quickModeLabel)
                        }
                        if (hasCameraPermission) {
                            var camera by remember { mutableStateOf<Camera?>(null) }
                            CameraPreviewScanner(
                                scanner = scanner,
                                scanFeedback = uiState.scanFeedback,
                                onCameraReady = { boundCamera ->
                                    camera = boundCamera
                                    camera?.cameraControl?.enableTorch(uiState.torchEnabled)
                                },
                                onTokenDetected = viewModel::onCameraFrameToken,
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .height(280.dp)
                            )
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(flashLabel)
                                Switch(
                                    checked = uiState.torchEnabled,
                                    onCheckedChange = { enabled ->
                                        viewModel.setTorchEnabled(enabled)
                                        camera?.cameraControl?.enableTorch(enabled)
                                    }
                                )
                            }
                        } else {
                            AppMessage(
                                message = permissionMessage,
                                type = MessageType.INFO
                            )
                            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                Button(
                                    onClick = { cameraPermissionLauncher.launch(Manifest.permission.CAMERA) },
                                    modifier = Modifier.weight(1f)
                                ) {
                                    Text(grantPermissionLabel)
                                }
                                if (shouldShowSettingsHint) {
                                    OutlinedButton(
                                        onClick = {
                                            context.startActivity(
                                                Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS).apply {
                                                    data = Uri.fromParts("package", context.packageName, null)
                                                }
                                            )
                                        },
                                        modifier = Modifier.weight(1f)
                                    ) {
                                        Text(openSettingsLabel)
                                    }
                                }
                            }
                        }
                    }
                }
            }

            item {
                AppCard(modifier = Modifier.fillMaxWidth()) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Text(
                            text = fallbackTitle,
                            style = androidx.compose.material3.MaterialTheme.typography.titleMedium
                        )
                        OutlinedTextField(
                            value = uiState.manualToken,
                            onValueChange = viewModel::onManualTokenChange,
                            label = { Text(manualTokenLabel) },
                            modifier = Modifier.fillMaxWidth()
                        )
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            Button(
                                onClick = viewModel::submitManualToken,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text(lookupTokenLabel)
                            }
                            OutlinedButton(
                                onClick = { galleryLauncher.launch("image/*") },
                                modifier = Modifier.weight(1f)
                            ) {
                                Text(pickQrImageLabel)
                            }
                        }

                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedButton(
                                onClick = onNavigateBack,
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Text(backLabel)
                            }
                        }
                    }
                }
            }

            item {
                AppCard(modifier = Modifier.fillMaxWidth()) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        Text(
                            text = tableTitle,
                            style = MaterialTheme.typography.titleMedium
                        )
                        Text(
                            text = if (uiState.deduplicateHistory) {
                                tableDeduplicateOn
                            } else {
                                tableDeduplicateOff
                            },
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                        if (uiState.history.isEmpty()) {
                            Text(
                                text = tableEmpty,
                                style = MaterialTheme.typography.bodySmall
                            )
                        } else {
                            TraceHistoryTable(
                                history = uiState.history.take(12),
                                onOpenToken = onNavigateResult
                            )
                            if (uiState.history.size > 12) {
                                Text(
                                    text = stringResource(R.string.trace_scan_table_showing_latest, 12),
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }

                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedButton(
                                onClick = onNavigateHistory,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text(historyLabel)
                            }
                            OutlinedButton(
                                onClick = viewModel::clearHistory,
                                modifier = Modifier.weight(1f),
                                enabled = uiState.history.isNotEmpty()
                            ) {
                                Text(clearTableLabel)
                            }
                        }
                    }
                }
            }

            if (uiState.message != null) {
                item {
                    AppMessage(message = uiState.message ?: "", type = MessageType.SUCCESS)
                }
            }
            if (uiState.error != null) {
                item {
                    AppMessage(message = uiState.error ?: "", type = MessageType.ERROR)
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TraceQuickScanScreen(
    onNavigateBack: () -> Unit,
    onNavigateFullScan: () -> Unit,
    onNavigateHistory: () -> Unit,
    onNavigateResult: (String) -> Unit,
    viewModel: TraceViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val activity = context.findActivity()

    val quickTitle = stringResource(R.string.trace_scan_quick_title)
    val quickSubtitle = stringResource(R.string.trace_scan_quick_subtitle)
    val flashLabel = stringResource(R.string.trace_scan_flash)
    val permissionMessage = stringResource(R.string.trace_scan_permission_message)
    val grantPermissionLabel = stringResource(R.string.trace_scan_grant_camera)
    val openSettingsLabel = stringResource(R.string.trace_scan_open_settings)
    val fallbackTitle = stringResource(R.string.trace_scan_fallback_title)
    val manualTokenLabel = stringResource(R.string.trace_scan_manual_token)
    val lookupTokenLabel = stringResource(R.string.trace_scan_lookup_token)
    val pickQrImageLabel = stringResource(R.string.trace_scan_pick_qr_image)
    val historyLabel = stringResource(R.string.trace_scan_history)
    val backLabel = stringResource(R.string.trace_scan_back)
    val openFullLabel = stringResource(R.string.trace_scan_full_mode)
    val galleryReadError = stringResource(R.string.trace_scan_gallery_error)
    val quickRecentTitle = stringResource(R.string.trace_scan_quick_recent_title)

    val scanner = remember {
        BarcodeScanning.getClient(
            BarcodeScannerOptions.Builder()
                .setBarcodeFormats(Barcode.FORMAT_QR_CODE)
                .build()
        )
    }

    var hasCameraPermission by remember {
        mutableStateOf(
            ContextCompat.checkSelfPermission(
                context,
                Manifest.permission.CAMERA
            ) == PackageManager.PERMISSION_GRANTED
        )
    }

    val cameraPermissionLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        hasCameraPermission = granted
    }

    val galleryLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.GetContent()
    ) { uri ->
        if (uri != null) {
            scanFromGallery(
                context = context,
                uri = uri,
                scanner = scanner,
                onResult = viewModel::onTokenDetectedFromGallery,
                onError = { viewModel.onScanError(galleryReadError) }
            )
        }
    }

    val shouldShowSettingsHint = activity?.let {
        !hasCameraPermission &&
            !ActivityCompat.shouldShowRequestPermissionRationale(it, Manifest.permission.CAMERA)
    } ?: false

    DisposableEffect(Unit) {
        onDispose { scanner.close() }
    }

    AppToastEffect(
        message = uiState.error ?: uiState.message,
        eventKey = uiState.feedbackId
    )

    LaunchedEffect(uiState.feedbackId, uiState.scanFeedback, uiState.currentResult?.token) {
        val token = uiState.currentResult?.token
        if (uiState.scanFeedback == ScanFeedback.SUCCESS && !token.isNullOrBlank()) {
            viewModel.clearMessages()
            onNavigateResult(token)
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(quickTitle) }
            )
        }
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            item {
                AppCard(modifier = Modifier.fillMaxWidth()) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        Text(
                            text = quickSubtitle,
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )

                        if (hasCameraPermission) {
                            var camera by remember { mutableStateOf<Camera?>(null) }
                            CameraPreviewScanner(
                                scanner = scanner,
                                scanFeedback = uiState.scanFeedback,
                                onCameraReady = { boundCamera ->
                                    camera = boundCamera
                                    camera?.cameraControl?.enableTorch(uiState.torchEnabled)
                                },
                                onTokenDetected = viewModel::onCameraFrameToken,
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .height(360.dp)
                            )
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(flashLabel)
                                Switch(
                                    checked = uiState.torchEnabled,
                                    onCheckedChange = { enabled ->
                                        viewModel.setTorchEnabled(enabled)
                                        camera?.cameraControl?.enableTorch(enabled)
                                    }
                                )
                            }
                        } else {
                            AppMessage(
                                message = permissionMessage,
                                type = MessageType.INFO
                            )
                            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                Button(
                                    onClick = { cameraPermissionLauncher.launch(Manifest.permission.CAMERA) },
                                    modifier = Modifier.weight(1f)
                                ) {
                                    Text(grantPermissionLabel)
                                }
                                if (shouldShowSettingsHint) {
                                    OutlinedButton(
                                        onClick = {
                                            context.startActivity(
                                                Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS).apply {
                                                    data = Uri.fromParts("package", context.packageName, null)
                                                }
                                            )
                                        },
                                        modifier = Modifier.weight(1f)
                                    ) {
                                        Text(openSettingsLabel)
                                    }
                                }
                            }
                        }
                    }
                }
            }

            item {
                AppCard(modifier = Modifier.fillMaxWidth()) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Text(
                            text = fallbackTitle,
                            style = MaterialTheme.typography.titleMedium
                        )
                        OutlinedTextField(
                            value = uiState.manualToken,
                            onValueChange = viewModel::onManualTokenChange,
                            label = { Text(manualTokenLabel) },
                            modifier = Modifier.fillMaxWidth()
                        )
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            Button(
                                onClick = viewModel::submitManualToken,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text(lookupTokenLabel)
                            }
                            OutlinedButton(
                                onClick = { galleryLauncher.launch("image/*") },
                                modifier = Modifier.weight(1f)
                            ) {
                                Text(pickQrImageLabel)
                            }
                        }
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedButton(
                                onClick = onNavigateHistory,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text(historyLabel)
                            }
                            OutlinedButton(
                                onClick = onNavigateFullScan,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text(openFullLabel)
                            }
                        }
                        OutlinedButton(
                            onClick = onNavigateBack,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text(backLabel)
                        }
                    }
                }
            }

            if (uiState.history.isNotEmpty()) {
                item {
                    AppCard(modifier = Modifier.fillMaxWidth()) {
                        Column(
                            modifier = Modifier.padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            Text(
                                text = quickRecentTitle,
                                style = MaterialTheme.typography.titleMedium
                            )
                            TraceHistoryTable(
                                history = uiState.history.take(3),
                                onOpenToken = onNavigateResult
                            )
                        }
                    }
                }
            }

            if (uiState.message != null) {
                item {
                    AppMessage(message = uiState.message ?: "", type = MessageType.SUCCESS)
                }
            }
            if (uiState.error != null) {
                item {
                    AppMessage(message = uiState.error ?: "", type = MessageType.ERROR)
                }
            }
        }
    }
}

@Composable
private fun TraceHistoryTable(
    history: List<ScanHistory>,
    onOpenToken: (String) -> Unit
) {
    Column(modifier = Modifier.fillMaxWidth()) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .background(
                    color = MaterialTheme.colorScheme.surfaceVariant,
                    shape = androidx.compose.foundation.shape.RoundedCornerShape(12.dp)
                )
                .padding(horizontal = 12.dp, vertical = 10.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = stringResource(R.string.trace_scan_table_token),
                modifier = Modifier.weight(1f),
                style = MaterialTheme.typography.labelMedium
            )
            Text(
                text = stringResource(R.string.trace_scan_table_status),
                modifier = Modifier.width(84.dp),
                style = MaterialTheme.typography.labelMedium
            )
        }

        history.forEachIndexed { index, item ->
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .clickable { onOpenToken(item.token) }
                    .padding(horizontal = 4.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column(
                    modifier = Modifier.weight(1f),
                    verticalArrangement = Arrangement.spacedBy(2.dp)
                ) {
                    Text(
                        text = item.token,
                        style = MaterialTheme.typography.bodyMedium,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    Text(
                        text = formatScanTime(item.scannedAt),
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
                Text(
                    text = formatScanStatus(item.status),
                    modifier = Modifier.width(84.dp),
                    style = MaterialTheme.typography.bodySmall,
                    color = if (item.status.equals("success", ignoreCase = true)) {
                        Color(0xFF2E7D32)
                    } else {
                        MaterialTheme.colorScheme.error
                    }
                )
                OutlinedButton(onClick = { onOpenToken(item.token) }) {
                    Text(stringResource(R.string.trace_scan_view))
                }
            }

            if (index < history.lastIndex) {
                HorizontalDivider()
            }
        }
    }
}

@Composable
private fun CameraPreviewScanner(
    scanner: BarcodeScanner,
    scanFeedback: ScanFeedback,
    onCameraReady: (Camera) -> Unit,
    onTokenDetected: (String?) -> Unit,
    modifier: Modifier = Modifier
) {
    val lifecycleOwner = LocalLifecycleOwner.current
    val cameraExecutor = remember { Executors.newSingleThreadExecutor() }
    var detectedRect by remember { mutableStateOf<RectF?>(null) }

    DisposableEffect(Unit) {
        onDispose {
            cameraExecutor.shutdown()
        }
    }

    Box(modifier = modifier) {
        AndroidView(
            modifier = Modifier.fillMaxSize(),
            factory = { ctx ->
                val previewView = PreviewView(ctx).apply {
                    scaleType = PreviewView.ScaleType.FILL_CENTER
                }

                val cameraProviderFuture = ProcessCameraProvider.getInstance(ctx)
                cameraProviderFuture.addListener({
                    val cameraProvider = cameraProviderFuture.get()
                    val preview = Preview.Builder().build().apply {
                        setSurfaceProvider(previewView.surfaceProvider)
                    }

                    val imageAnalysis = ImageAnalysis.Builder()
                        .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
                        .build()
                        .apply {
                            setAnalyzer(
                                cameraExecutor,
                                QrAnalyzer(
                                    scanner = scanner,
                                    mainExecutor = ContextCompat.getMainExecutor(ctx),
                                    previewSizeProvider = { previewView.width to previewView.height },
                                    onDetected = { token, mappedRect ->
                                        detectedRect = mappedRect
                                        onTokenDetected(token)
                                    }
                                )
                            )
                        }

                    cameraProvider.unbindAll()
                    val camera = cameraProvider.bindToLifecycle(
                        lifecycleOwner,
                        CameraSelector.DEFAULT_BACK_CAMERA,
                        preview,
                        imageAnalysis
                    )
                    onCameraReady(camera)
                }, ContextCompat.getMainExecutor(ctx))

                previewView
            }
        )

        QrDetectionOverlay(
            detectedRect = detectedRect,
            scanFeedback = scanFeedback,
            modifier = Modifier.fillMaxSize()
        )
    }
}

@OptIn(ExperimentalGetImage::class)
private class QrAnalyzer(
    private val scanner: BarcodeScanner,
    private val mainExecutor: java.util.concurrent.Executor,
    private val previewSizeProvider: () -> Pair<Int, Int>,
    private val onDetected: (String?, RectF?) -> Unit
) : ImageAnalysis.Analyzer {

    override fun analyze(imageProxy: ImageProxy) {
        val mediaImage = imageProxy.image
        if (mediaImage == null) {
            imageProxy.close()
            return
        }
        val inputImage = InputImage.fromMediaImage(mediaImage, imageProxy.imageInfo.rotationDegrees)
        val rotationDegrees = imageProxy.imageInfo.rotationDegrees
        val imageWidth = imageProxy.width
        val imageHeight = imageProxy.height
        scanner.process(inputImage)
            .addOnSuccessListener { barcodes ->
                val firstBarcode = barcodes.firstOrNull()
                val token = firstBarcode?.rawValue
                val mappedRect = firstBarcode?.boundingBox?.let { box ->
                    val (previewWidth, previewHeight) = previewSizeProvider()
                    mapBarcodeToPreviewRect(
                        boundingBox = box,
                        imageWidth = imageWidth,
                        imageHeight = imageHeight,
                        rotationDegrees = rotationDegrees,
                        previewWidth = previewWidth,
                        previewHeight = previewHeight
                    )
                }
                mainExecutor.execute {
                    onDetected(token, mappedRect)
                }
            }
            .addOnFailureListener {
                mainExecutor.execute {
                    onDetected(null, null)
                }
            }
            .addOnCompleteListener { imageProxy.close() }
    }
}

@Composable
private fun QrDetectionOverlay(
    detectedRect: RectF?,
    scanFeedback: ScanFeedback,
    modifier: Modifier = Modifier
) {
    Canvas(modifier = modifier) {
        val rect = detectedRect ?: return@Canvas
        val indicatorColor = when (scanFeedback) {
            ScanFeedback.SUCCESS -> Color(0xFF28C76F)
            ScanFeedback.ERROR -> Color(0xFFE53935)
            ScanFeedback.IDLE -> Color(0xCCFFFFFF)
        }

        drawRoundRect(
            color = indicatorColor,
            topLeft = Offset(rect.left, rect.top),
            size = Size(rect.width(), rect.height()),
            cornerRadius = CornerRadius(14.dp.toPx(), 14.dp.toPx()),
            style = Stroke(width = 3.dp.toPx())
        )
    }
}

private fun mapBarcodeToPreviewRect(
    boundingBox: Rect,
    imageWidth: Int,
    imageHeight: Int,
    rotationDegrees: Int,
    previewWidth: Int,
    previewHeight: Int
): RectF? {
    if (previewWidth <= 0 || previewHeight <= 0 || imageWidth <= 0 || imageHeight <= 0) {
        return null
    }

    val sourceWidth =
        if (rotationDegrees == 90 || rotationDegrees == 270) imageHeight.toFloat() else imageWidth.toFloat()
    val sourceHeight =
        if (rotationDegrees == 90 || rotationDegrees == 270) imageWidth.toFloat() else imageHeight.toFloat()

    val scale = max(previewWidth / sourceWidth, previewHeight / sourceHeight)
    val offsetX = (previewWidth - sourceWidth * scale) / 2f
    val offsetY = (previewHeight - sourceHeight * scale) / 2f

    val left = boundingBox.left * scale + offsetX
    val top = boundingBox.top * scale + offsetY
    val right = boundingBox.right * scale + offsetX
    val bottom = boundingBox.bottom * scale + offsetY

    return RectF(
        min(previewWidth.toFloat(), max(0f, left)),
        min(previewHeight.toFloat(), max(0f, top)),
        min(previewWidth.toFloat(), max(0f, right)),
        min(previewHeight.toFloat(), max(0f, bottom))
    )
}

private fun scanFromGallery(
    context: Context,
    uri: Uri,
    scanner: BarcodeScanner,
    onResult: (String) -> Unit,
    onError: () -> Unit
) {
    val inputImage = try {
        InputImage.fromFilePath(context, uri)
    } catch (_: Exception) {
        onError()
        return
    }
    scanner.process(inputImage)
        .addOnSuccessListener { barcodes ->
            val token = barcodes.firstOrNull()?.rawValue
            if (!token.isNullOrBlank()) onResult(token) else onError()
        }
        .addOnFailureListener { onError() }
}

private fun formatScanTime(timestamp: Long): String {
    return java.text.SimpleDateFormat(
        "dd/MM HH:mm",
        java.util.Locale.getDefault()
    ).format(java.util.Date(timestamp))
}

private fun formatScanStatus(status: String): String {
    val isVietnamese = java.util.Locale.getDefault().language == "vi"
    return if (status.equals("success", ignoreCase = true)) {
        if (isVietnamese) "Hợp lệ" else "Valid"
    } else {
        if (isVietnamese) "Lỗi" else "Error"
    }
}

private fun Context.findActivity(): ComponentActivity? {
    var currentContext = this
    while (currentContext is ContextWrapper) {
        if (currentContext is ComponentActivity) return currentContext
        currentContext = currentContext.baseContext
    }
    return null
}
