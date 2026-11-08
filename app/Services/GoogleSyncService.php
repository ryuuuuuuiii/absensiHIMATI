<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSyncService
{
    public function getScriptUrl(): ?string
    {
        $url = Setting::get('google_script_url');
        if (empty($url)) {
            $url = env('GOOGLE_SCRIPT_URL', '');
        }

        return $url ?: null;
    }

    public function getDriveFolderId(): ?string
    {
        $folderId = Setting::get('google_drive_folder_id');
        if (empty($folderId)) {
            $folderId = env('GOOGLE_DRIVE_FOLDER_ID', '');
        }

        return $folderId ?: null;
    }

    public function syncMeeting(Meeting $meeting): array
    {
        $url = $this->getScriptUrl();
        if (! $url) {
            return ['success' => false, 'message' => 'Google Apps Script URL belum dikonfigurasi.'];
        }

        try {
            $attendances = $meeting->attendances()
                ->with('member')
                ->get();

            $data = [];
            foreach ($attendances as $att) {
                $member = $att->member;
                $data[] = [
                    'tanggal' => $meeting->tanggal ? $meeting->tanggal->format('Y-m-d') : '',
                    'jenis_rapat' => $meeting->jenis_rapat,
                    'departemen_rapat' => $meeting->departemen ?? '',
                    'nama_event' => $meeting->nama_event ?? '',
                    'fokus_bahasan' => $meeting->fokus_bahasan ?? '',
                    'nama_peserta' => $member ? $member->nama_lengkap : 'Unknown',
                    'departemen_peserta' => $member ? $member->departemen : '',
                    'jabatan' => $member ? $member->jabatan : '',
                    'status' => $att->status,
                    'keterangan' => $att->keterangan ?? '',
                    'waktu_checkin' => $att->scanned_at ? $att->scanned_at->format('Y-m-d H:i:s') : '',
                    'mode' => $meeting->mode_rapat,
                ];
            }

            if (empty($data)) {
                return ['success' => false, 'message' => 'Tidak ada data peserta untuk disinkronkan.'];
            }

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->post($url, [
                    'action' => 'appendAttendance',
                    'data' => $data,
                ]);

            if ($response->successful()) {
                return $response->json() ?? ['success' => true, 'message' => 'Berhasil disinkronkan ke Google Sheets.'];
            }

            return ['success' => false, 'message' => 'Respon Google HTTP '.$response->status()];
        } catch (\Throwable $e) {
            Log::error('Google Sheets sync error: '.$e->getMessage());

            return ['success' => false, 'message' => 'Gagal sync: '.$e->getMessage()];
        }
    }

    public function uploadPhotoToDrive(string $base64Image, string $filename): array
    {
        $url = $this->getScriptUrl();
        $folderId = $this->getDriveFolderId();

        if (! $url || ! $folderId) {
            return ['success' => false, 'message' => 'Konfigurasi Google Drive belum lengkap.'];
        }

        try {
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->post($url, [
                    'action' => 'savePhoto',
                    'data' => [
                        'image' => $base64Image,
                        'filename' => $filename,
                        'folderId' => $folderId,
                    ],
                ]);

            return $response->json() ?? ['success' => false, 'message' => 'Invalid response from Google Drive script'];
        } catch (\Throwable $e) {
            Log::error('Google Drive upload error: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
