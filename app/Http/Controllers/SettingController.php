<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SettingController extends Controller
{
    public function index()
    {
        $googleScriptUrl     = Setting::get('google_script_url');
        $googleDriveFolderId = Setting::get('google_drive_folder_id');
        $expiryHours         = Setting::get('online_link_expiry_hours', '3');
        $logo1               = Setting::get('logo_1');
        $logo2               = Setting::get('logo_2');
        $logo3               = Setting::get('logo_3');

        return view('settings.index', compact(
            'googleScriptUrl',
            'googleDriveFolderId',
            'expiryHours',
            'logo1',
            'logo2',
            'logo3'
        ));
    }

    public function updateGeneral(Request $request)
    {
        $request->validate([
            'online_link_expiry_hours' => ['required', 'integer', 'min:1', 'max:72'],
        ]);

        Setting::set('online_link_expiry_hours', (string) $request->input('online_link_expiry_hours'));

        return back()->with('success', 'Pengaturan umum berhasil disimpan.');
    }

    public function updateGoogle(Request $request)
    {
        $request->validate([
            'google_script_url'      => ['nullable', 'url'],
            'google_drive_folder_id' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::set('google_script_url', $request->input('google_script_url', ''));
        Setting::set('google_drive_folder_id', $request->input('google_drive_folder_id', ''));

        return back()->with('success', 'Konfigurasi Google Apps Script berhasil diperbarui.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo_slot' => ['required', 'in:logo_1,logo_2,logo_3'],
            'logo_file' => ['required', 'image', 'max:5120'], // 5MB
        ]);

        $slot = $request->input('logo_slot');
        $file = $request->file('logo_file');

        $filename = $slot . '_' . time() . '.' . $file->getClientOriginalExtension();
        $targetDir = public_path('uploads/logos');

        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $file->move($targetDir, $filename);

        Setting::set($slot, $filename);

        return back()->with('success', 'Logo ' . strtoupper(str_replace('_', ' ', $slot)) . ' berhasil diunggah.');
    }
}
