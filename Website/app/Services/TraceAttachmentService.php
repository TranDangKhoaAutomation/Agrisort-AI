<?php
declare(strict_types=1);

namespace App\Services;

final class TraceAttachmentService
{
    private const MAX_FILE_BYTES = 10 * 1024 * 1024;

    /** @var array<string,string> */
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    /**
     * @param array<string,mixed>|null $payload
     * @return array<int,array<string,mixed>>
     */
    public static function storeUploadedFiles(?array $payload): array
    {
        if (!$payload || !isset($payload['tmp_name'])) {
            return [];
        }

        $items = self::normalizeUploadPayload($payload);
        $stored = [];

        $dir = BASE_PATH . '/public/uploads/trace-events';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        foreach ($items as $item) {
            if ((int) ($item['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ((int) ($item['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                throw new \RuntimeException(lang_text('Tải lên tệp đính kèm không thành công.', 'Attachment upload failed.'));
            }

            $size = (int) ($item['size'] ?? 0);
            if ($size <= 0 || $size > self::MAX_FILE_BYTES) {
                throw new \RuntimeException(lang_text('Kích thước tệp đính kèm vượt quá 10 MB.', 'Attachment size exceeds 10MB.'));
            }

            $tmpName = (string) ($item['tmp_name'] ?? '');
            if ($tmpName === '' || !is_file($tmpName)) {
                throw new \RuntimeException(lang_text('Không thể đọc tập tin tải lên tạm thời.', 'Cannot read temporary uploaded file.'));
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = strtolower((string) $finfo->file($tmpName));
            if (!isset(self::ALLOWED_MIME[$mime])) {
                throw new \RuntimeException(lang_text('Các loại tệp đính kèm được phép: JPG/PNG/WEBP/PDF.', 'Allowed attachment types: JPG/PNG/WEBP/PDF.'));
            }

            $ext = self::ALLOWED_MIME[$mime];
            $name = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $relative = 'public/uploads/trace-events/' . $name;
            $target = BASE_PATH . '/' . $relative;

            if (!move_uploaded_file($tmpName, $target)) {
                throw new \RuntimeException(lang_text('Không thể lưu trữ tệp đính kèm.', 'Failed to store attachment.'));
            }

            $stored[] = [
                'file_path' => $relative,
                'original_name' => (string) ($item['name'] ?? $name),
                'mime_type' => $mime,
                'file_size' => $size,
            ];
        }

        return $stored;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<int,array<string,mixed>>
     */
    private static function normalizeUploadPayload(array $payload): array
    {
        $tmpName = $payload['tmp_name'] ?? null;
        if (!is_array($tmpName)) {
            return [$payload];
        }

        $names = is_array($payload['name'] ?? null) ? $payload['name'] : [];
        $sizes = is_array($payload['size'] ?? null) ? $payload['size'] : [];
        $types = is_array($payload['type'] ?? null) ? $payload['type'] : [];
        $errors = is_array($payload['error'] ?? null) ? $payload['error'] : [];

        $out = [];
        foreach ($tmpName as $index => $tmp) {
            $out[] = [
                'name' => (string) ($names[$index] ?? ''),
                'type' => (string) ($types[$index] ?? ''),
                'tmp_name' => (string) $tmp,
                'error' => (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($sizes[$index] ?? 0),
            ];
        }

        return $out;
    }
}
