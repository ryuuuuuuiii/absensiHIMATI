@extends('layouts.app')

@section('title', 'Pengaturan')

@section('page_icon')
    <i class="fas fa-cogs"></i>
@endsection

@section('content')
<div style="max-width: 750px; margin: 0 auto;">
    <!-- Google Integration -->
    <div class="card mb-6">
        <h3 class="card-title mb-6"><i class="fab fa-google"></i> Integrasi Google Sheets & Drive</h3>
        
        <form action="{{ route('settings.google') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="google_script_url">Google Apps Script Web App URL</label>
                <input type="url" 
                       id="google_script_url" 
                       name="google_script_url" 
                       class="form-control" 
                       value="{{ old('google_script_url', $googleScriptUrl) }}"
                       placeholder="https://script.google.com/macros/s/.../exec">
                <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                    URL Web App dari deployment Google Apps Script untuk sinkronisasi otomatis absensi ke Spreadsheet.
                </small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="google_drive_folder_id">Google Drive Folder ID (Opsional)</label>
                <input type="text" 
                       id="google_drive_folder_id" 
                       name="google_drive_folder_id" 
                       class="form-control" 
                       value="{{ old('google_drive_folder_id', $googleDriveFolderId) }}"
                       placeholder="1a2b3c4d5e6f...">
                <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                    Folder ID tempat menyimpan upload foto dokumentasi di Google Drive.
                </small>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Konfigurasi Google
            </button>
        </form>
    </div>
    
    <!-- General Settings -->
    <div class="card">
        <h3 class="card-title mb-6"><i class="fas fa-sliders-h"></i> Pengaturan Umum</h3>
        
        <form action="{{ route('settings.general') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="online_link_expiry_hours">Batas Waktu Link Absensi Online (Jam)</label>
                <input type="number" 
                       id="online_link_expiry_hours" 
                       name="online_link_expiry_hours" 
                       class="form-control" 
                       style="max-width: 150px;"
                       min="1" 
                       max="72"
                       value="{{ old('online_link_expiry_hours', $expiryHours) }}">
                <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                    Berapa jam link absensi online tetap aktif setelah waktu rapat dimulai.
                </small>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Pengaturan
            </button>
        </form>
    </div>
</div>
@endsection
