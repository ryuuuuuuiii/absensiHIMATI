<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $googleScriptUrl = Setting::get('google_script_url');
        $googleDriveFolderId = Setting::get('google_drive_folder_id');
        $expiryHours = Setting::get('online_link_expiry_hours', '3');

        return view('settings.index', compact(
            'googleScriptUrl',
            'googleDriveFolderId',
            'expiryHours'
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
            'google_script_url' => ['nullable', 'url'],
            'google_drive_folder_id' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::set('google_script_url', $request->input('google_script_url', ''));
        Setting::set('google_drive_folder_id', $request->input('google_drive_folder_id', ''));

        return back()->with('success', 'Konfigurasi Google Apps Script berhasil diperbarui.');
    }
}
