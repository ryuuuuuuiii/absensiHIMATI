<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class FileUploadService
{
    /**
     * Pastikan tabel stored_uploads ada di database (fallback untuk Vercel read-only).
     */
    public function ensureTable(): void
    {
        try {
            if (! Schema::hasTable('stored_uploads')) {
                DB::statement('CREATE TABLE IF NOT EXISTS `stored_uploads` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `folder` VARCHAR(50) NOT NULL,
                    `filename` VARCHAR(255) NOT NULL,
                    `mime_type` VARCHAR(100) NOT NULL,
                    `file_size` INT UNSIGNED NOT NULL,
                    `payload` LONGBLOB NOT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_folder_filename` (`folder`, `filename`),
                    KEY `idx_filename` (`filename`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
            }
        } catch (\Throwable $e) {
            // Abaikan kegagalan DDL jika koneksi bermasalah
        }
    }

    /**
     * Simpan file upload ke disk (jika writable) atau ke database (Vercel serverless).
     */
    public function store(UploadedFile $file, string $folder, ?string $filename = null): string
    {
        $filename = $filename ?: uniqid($folder.'_').'.'.$file->getClientOriginalExtension();
        $targetDir = public_path('uploads/'.$folder);

        $savedToDisk = false;

        // Coba simpan ke filesystem (untuk localhost & Docker/Render)
        try {
            if (! File::isDirectory($targetDir)) {
                @File::makeDirectory($targetDir, 0755, true);
            }

            if (is_dir($targetDir) && is_writable($targetDir)) {
                $file->move($targetDir, $filename);
                $savedToDisk = true;
            }
        } catch (\Throwable $e) {
            $savedToDisk = false;
        }

        // Jika filesystem read-only (seperti di Vercel), simpan ke database TiDB
        if (! $savedToDisk) {
            $this->ensureTable();

            $content = file_get_contents($file->getRealPath());
            DB::table('stored_uploads')->updateOrInsert(
                [
                    'folder' => $folder,
                    'filename' => $filename,
                ],
                [
                    'mime_type' => $file->getClientMimeType() ?: ($file->getMimeType() ?: 'image/jpeg'),
                    'file_size' => strlen($content),
                    'payload' => $content,
                    'created_at' => now(),
                ]
            );
        }

        return $filename;
    }

    /**
     * Hapus file dari filesystem dan database jika ada.
     */
    public function delete(string $folder, ?string $filename): void
    {
        if (! $filename) {
            return;
        }

        $filepath = public_path('uploads/'.$folder.'/'.$filename);
        if (File::exists($filepath)) {
            @File::delete($filepath);
        }

        try {
            DB::table('stored_uploads')
                ->where('folder', $folder)
                ->where('filename', $filename)
                ->delete();
        } catch (\Throwable $e) {
            // Ignore if table does not exist
        }
    }

    /**
     * Ambil response file untuk folder uploads.
     */
    public function response(string $folder, string $filename): ?Response
    {
        $localPath = public_path("uploads/{$folder}/{$filename}");
        if (File::exists($localPath)) {
            $mime = File::mimeType($localPath) ?: 'image/jpeg';

            return response(File::get($localPath))
                ->header('Content-Type', $mime)
                ->header('Cache-Control', 'public, max-age=31536000');
        }

        try {
            $this->ensureTable();
            $stored = DB::table('stored_uploads')
                ->where('folder', $folder)
                ->where('filename', $filename)
                ->first();

            if ($stored) {
                return response($stored->payload)
                    ->header('Content-Type', $stored->mime_type)
                    ->header('Cache-Control', 'public, max-age=31536000');
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        // Fallback jika logo lama diakses
        if ($folder === 'logos') {
            $fallback = str_contains($filename, 'logo_1')
                ? public_path('assets/img/logo1.png')
                : public_path('assets/img/logo2.png');

            if (File::exists($fallback)) {
                return response(File::get($fallback))
                    ->header('Content-Type', 'image/png')
                    ->header('Cache-Control', 'public, max-age=31536000');
            }
        }

        return null;
    }
}
