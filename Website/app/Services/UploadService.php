<?php
declare(strict_types=1);

namespace App\Services;

final class UploadService
{
    public static function image(?array $file): ?string
    {
        if (!$file || !isset($file['tmp_name']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if ((int) $file['size'] > 5 * 1024 * 1024) {
            throw new \RuntimeException(lang_text('Hình ảnh quá lớn (tối đa 5MB).', 'Image too large (max 5MB).'));
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mime])) {
            throw new \RuntimeException(lang_text('Loại hình ảnh không hợp lệ.', 'Invalid image type.'));
        }

        $ext = $allowed[$mime];
        $name = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $relative = 'public/uploads/' . $name;
        $target = BASE_PATH . '/' . $relative;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new \RuntimeException(lang_text('Tải lên không thành công.', 'Upload failed.'));
        }

        return $relative;
    }
}
