<?php
declare(strict_types=1);

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final class TraceDecodeService
{
    private const MAX_UPLOAD_BYTES = 10485760; // 10MB

    /** @var array<string,bool> */
    private const ALLOWED_MIME = [
        'image/jpeg' => true,
        'image/png' => true,
        'image/webp' => true,
        'application/pdf' => true,
    ];

    public static function decodeUploadedFile(?array $file): string
    {
        if (!$file || !isset($file['tmp_name']) || !isset($file['error'])) {
            throw new \RuntimeException(lang_text('Thiếu tập tin để theo dõi.', 'Missing file for trace.'));
        }

        $errorCode = (int) $file['error'];
        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::uploadErrorMessage($errorCode));
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new \RuntimeException(lang_text('Tệp đã tải lên trống.', 'Uploaded file is empty.'));
        }

        if ($size > self::MAX_UPLOAD_BYTES) {
            throw new \RuntimeException(lang_text('Tệp quá lớn (tối đa 10 MB).', 'File too large (max 10MB).'));
        }

        $tmpPath = (string) $file['tmp_name'];
        if ($tmpPath === '' || !is_file($tmpPath)) {
            throw new \RuntimeException(lang_text('Không thể đọc tệp tạm thời đã tải lên.', 'Cannot read uploaded temporary file.'));
        }

        $mime = self::detectMime($tmpPath, (string) ($file['name'] ?? ''));
        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new \RuntimeException(lang_text('Định dạng tệp không được hỗ trợ. ', 'Unsupported file format. Allowed: JPG, PNG, WEBP, PDF.'));
        }

        if ($mime === 'application/pdf') {
            return self::decodePdfFile($tmpPath);
        }

        return self::decodeImageFile($tmpPath);
    }

    private static function decodeImageFile(string $path): string
    {
        if (!extension_loaded('gd') && !self::imagickAvailable()) {
            throw new \RuntimeException(lang_text('Máy chủ yêu cầu GD hoặc Imagick để giải mã QR hình ảnh. ', 'Server requires GD or Imagick to decode image QR. Enable extensions and restart Apache.'));
        }

        try {
            if (extension_loaded('gd')) {
                return self::decodeWithReaderAttempts($path, false);
            }

            return self::decodeWithReaderAttempts($path, true);
        } catch (\Throwable $e) {
            throw new \RuntimeException(lang_text('Không thể giải mã QR từ hình ảnh được tải lên. ', 'Cannot decode QR from uploaded image. Try a clearer image or move QR closer.'), 0, $e);
        }
    }

    private static function decodePdfFile(string $path): string
    {
        if (!self::imagickAvailable()) {
            throw new \RuntimeException(lang_text('Máy chủ yêu cầu Imagick giải mã QR từ PDF.', 'Server requires Imagick to decode QR from PDF.'));
        }

        try {
            return self::decodeWithReaderAttempts($path, true);
        } catch (\Throwable $e) {
            throw new \RuntimeException(lang_text('Không thể giải mã QR từ tệp PDF đã tải lên.', 'Cannot decode QR from uploaded PDF file.'), 0, $e);
        }
    }

    private static function decodeWithReaderAttempts(string $path, bool $useImagickReader): string
    {
        $attempts = [
            ['grayscale' => true, 'contrast' => true, 'invert' => false],
            ['grayscale' => false, 'contrast' => false, 'invert' => false],
            ['grayscale' => true, 'contrast' => false, 'invert' => false],
            ['grayscale' => false, 'contrast' => true, 'invert' => false],
            ['grayscale' => true, 'contrast' => true, 'invert' => true],
            ['grayscale' => false, 'contrast' => false, 'invert' => true],
        ];

        $lastError = null;

        foreach ($attempts as $attempt) {
            try {
                $options = new QROptions([
                    'readerUseImagickIfAvailable' => $useImagickReader,
                    'readerGrayscale' => (bool) $attempt['grayscale'],
                    'readerIncreaseContrast' => (bool) $attempt['contrast'],
                    'readerInvertColors' => (bool) $attempt['invert'],
                ]);

                $result = (new QRCode($options))->readFromFile($path);
                $decodedText = trim((string) $result);

                if ($decodedText !== '') {
                    return $decodedText;
                }
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }

        if ($lastError !== null) {
            throw $lastError;
        }

        throw new \RuntimeException(lang_text('Không tìm thấy mã QR hợp lệ trong hình ảnh/PDF.', 'No valid QR found in image/PDF.'));
    }

    private static function detectMime(string $path, string $originalName = ''): string
    {
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = strtolower(trim((string) $finfo->file($path)));
            if ($mime !== '') {
                return $mime;
            }
        }

        $mimeContentType = function_exists('mime_content_type') ? (string) mime_content_type($path) : '';
        $mimeContentType = strtolower(trim($mimeContentType));

        if ($mimeContentType !== '') {
            return $mimeContentType;
        }

        $ext = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    private static function imagickAvailable(): bool
    {
        return extension_loaded('imagick') && class_exists(\Imagick::class);
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => lang_text('Tệp vượt quá giới hạn kích thước tải lên.', 'File exceeds upload size limit.'),
            UPLOAD_ERR_PARTIAL => lang_text('Tệp chỉ được tải lên một phần.', 'File was only partially uploaded.'),
            UPLOAD_ERR_NO_FILE => lang_text('Không có tập tin nào được tải lên.', 'No file was uploaded.'),
            UPLOAD_ERR_NO_TMP_DIR => lang_text('Máy chủ thiếu thư mục tạm thời để tải lên.', 'Server is missing a temporary folder for uploads.'),
            UPLOAD_ERR_CANT_WRITE => lang_text('Máy chủ không ghi được tập tin đã tải lên.', 'Server failed to write uploaded file.'),
            UPLOAD_ERR_EXTENSION => lang_text('Tải lên bị chặn bởi tiện ích mở rộng máy chủ.', 'Upload blocked by a server extension.'),
            default => lang_text('Tải tệp lên không thành công. ', 'File upload failed. Please try again.'),
        };
    }
}
