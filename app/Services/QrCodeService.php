<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class QrCodeService
{
    protected string $qrDirectory;

    public function __construct()
    {
        $this->qrDirectory = public_path('assets/img/qrcodes/');
        if (! File::isDirectory($this->qrDirectory)) {
            File::makeDirectory($this->qrDirectory, 0755, true);
        }
    }

    public function ensureMemberQr(Member $member, bool $force = false): ?string
    {
        $cleanCode = preg_replace('/[^a-zA-Z0-9_-]/', '', $member->qr_code);
        $filename = 'qr_'.$member->id.'_'.$cleanCode.'.png';
        $filepath = $this->qrDirectory.$filename;

        if (! $force && File::exists($filepath) && File::size($filepath) > 100) {
            if ($member->qr_image !== $filename) {
                $member->update(['qr_image' => $filename]);
            }

            return $filename;
        }

        $qrData = $member->qr_data;
        $apiUrls = [
            'https://api.qrserver.com/v1/create-qr-code/?size=500x500&margin=15&data='.urlencode($qrData),
            'https://quickchart.io/qr?size=500&margin=2&text='.urlencode($qrData),
        ];

        $imageData = null;
        foreach ($apiUrls as $url) {
            try {
                $response = Http::timeout(8)->withoutVerifying()->get($url);
                if ($response->successful() && strlen($response->body()) > 100) {
                    $imageData = $response->body();
                    break;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        if ($imageData) {
            File::put($filepath, $imageData);
            $member->update(['qr_image' => $filename]);

            return $filename;
        }

        return null;
    }

    public function generateAll(): int
    {
        $members = Member::orderBy('departemen')->orderBy('nama_lengkap')->get();
        $count = 0;
        foreach ($members as $member) {
            if ($this->ensureMemberQr($member, true)) {
                $count++;
            }
        }

        return $count;
    }

    public function createZipArchive(): ?string
    {
        $members = Member::orderBy('departemen')->orderBy('nama_lengkap')->get();
        if ($members->isEmpty()) {
            return null;
        }

        $zipFilename = 'QR_Codes_HIMATI_'.date('Ymd_His').'.zip';
        $zipPath = storage_path('app/'.$zipFilename);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        foreach ($members as $member) {
            $filename = $this->ensureMemberQr($member);
            if ($filename && File::exists($this->qrDirectory.$filename)) {
                $deptFolder = preg_replace('/[^a-zA-Z0-9_-]/', '_', $member->departemen);
                $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $member->nama_lengkap);
                $entryName = $deptFolder.'/'.$cleanName.'_'.$member->qr_code.'.png';
                $zip->addFile($this->qrDirectory.$filename, $entryName);
            }
        }

        $zip->close();

        return $zipPath;
    }
}
