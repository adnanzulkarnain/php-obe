<?php

namespace App\Utils;

/**
 * File Upload Helper
 * Handles file upload validation and processing
 */
class FileUploadHelper
{
    private static ?int $maxFileSize = null;
    private static ?array $allowedTypes = null;
    private static ?string $uploadPath = null;

    /**
     * Initialize configuration
     */
    public static function init(): void
    {
        self::$maxFileSize = (int) (getenv('UPLOAD_MAX_SIZE') ?: 10485760); // 10MB default
        self::$allowedTypes = explode(',', getenv('UPLOAD_ALLOWED_TYPES') ?: 'pdf,docx,xlsx,jpg,png');
        self::$uploadPath = getenv('UPLOAD_PATH') ?: 'storage/uploads';

        // Ensure upload directory exists
        if (!is_dir(self::$uploadPath)) {
            mkdir(self::$uploadPath, 0755, true);
        }
    }

    /**
     * Validate uploaded file
     *
     * @param array $file The $_FILES['fieldname'] array
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public static function validate(array $file): array
    {
        if (self::$maxFileSize === null) {
            self::init();
        }

        // Check if file was uploaded
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file was uploaded'];
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => self::getUploadErrorMessage($file['error'])];
        }

        // Check file size
        if ($file['size'] > self::$maxFileSize) {
            $maxSizeMB = self::$maxFileSize / 1048576;
            return ['valid' => false, 'error' => "File size exceeds maximum allowed size of {$maxSizeMB}MB"];
        }

        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::$allowedTypes)) {
            $allowedStr = implode(', ', self::$allowedTypes);
            return ['valid' => false, 'error' => "File type not allowed. Allowed types: $allowedStr"];
        }

        // Check MIME type for additional security
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!self::isValidMimeType($mimeType, $extension)) {
            return ['valid' => false, 'error' => 'Invalid file type detected'];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Upload file
     *
     * @param array $file The $_FILES['fieldname'] array
     * @param string $category Document category for organizing uploads
     * @return array ['success' => bool, 'file_path' => string|null, 'error' => string|null]
     */
    public static function upload(array $file, string $category = 'general'): array
    {
        if (self::$uploadPath === null) {
            self::init();
        }

        // Validate file first
        $validation = self::validate($file);
        if (!$validation['valid']) {
            return ['success' => false, 'file_path' => null, 'error' => $validation['error']];
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = self::generateUniqueFilename($extension);

        // Create category subdirectory if needed
        $categoryPath = self::$uploadPath . '/' . $category;
        if (!is_dir($categoryPath)) {
            mkdir($categoryPath, 0755, true);
        }

        // Full path for the file
        $filePath = $categoryPath . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'file_path' => null, 'error' => 'Failed to move uploaded file'];
        }

        // Make path relative for storage
        $relativePath = $category . '/' . $filename;

        return [
            'success' => true,
            'file_path' => $relativePath,
            'filename' => $filename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'extension' => $extension,
            'error' => null
        ];
    }

    /**
     * Delete file
     */
    public static function delete(string $filePath): bool
    {
        if (self::$uploadPath === null) {
            self::init();
        }

        $fullPath = self::$uploadPath . '/' . $filePath;

        if (!file_exists($fullPath)) {
            return false;
        }

        return unlink($fullPath);
    }

    /**
     * Get full path to file
     */
    public static function getFullPath(string $filePath): string
    {
        if (self::$uploadPath === null) {
            self::init();
        }

        return self::$uploadPath . '/' . $filePath;
    }

    /**
     * Check if file exists
     */
    public static function exists(string $filePath): bool
    {
        return file_exists(self::getFullPath($filePath));
    }

    /**
     * Get file info
     */
    public static function getFileInfo(string $filePath): ?array
    {
        $fullPath = self::getFullPath($filePath);

        if (!file_exists($fullPath)) {
            return null;
        }

        return [
            'size' => filesize($fullPath),
            'modified' => filemtime($fullPath),
            'extension' => pathinfo($fullPath, PATHINFO_EXTENSION),
            'mime_type' => mime_content_type($fullPath)
        ];
    }

    /**
     * Generate unique filename
     */
    private static function generateUniqueFilename(string $extension): string
    {
        return uniqid('file_', true) . '_' . time() . '.' . $extension;
    }

    /**
     * Get upload error message
     */
    private static function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload directory',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension'
        ];

        return $errors[$errorCode] ?? 'Unknown upload error';
    }

    /**
     * Validate MIME type
     */
    private static function isValidMimeType(string $mimeType, string $extension): bool
    {
        $validMimes = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'text/plain']
        ];

        if (!isset($validMimes[$extension])) {
            return false;
        }

        return in_array($mimeType, $validMimes[$extension]);
    }

    /**
     * Get max file size
     */
    public static function getMaxFileSize(): int
    {
        if (self::$maxFileSize === null) {
            self::init();
        }

        return self::$maxFileSize;
    }

    /**
     * Get allowed types
     */
    public static function getAllowedTypes(): array
    {
        if (self::$allowedTypes === null) {
            self::init();
        }

        return self::$allowedTypes;
    }

    /**
     * Format file size to human readable
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
