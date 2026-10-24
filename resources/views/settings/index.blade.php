@extends('layouts.app')

@section('title', 'Pengaturan')

@section('page_icon')
    <i class="fas fa-cogs"></i>
@endsection

@section('content')
<div style="max-width: 750px; margin: 0 auto;">
    <!-- Logo Upload -->
    <div class="card mb-6">
        <h3 class="card-title mb-6"><i class="fas fa-image"></i> Kelola Logo Instansi / Organisasi</h3>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4);" class="logo-grid-mobile">
            @for ($slot = 1; $slot <= 3; $slot++)
            @php
                $slotVar = 'logo' . $slot;
                $currentLogo = $$slotVar ?? null;
            @endphp
            <div style="text-align: center;">
                <div style="width: 100%; aspect-ratio: 1; border-radius: var(--radius-md); border: 2px dashed var(--dark-border); display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-3); overflow: hidden; background: var(--dark-surface-2);">
                    @if ($currentLogo && file_exists(public_path('uploads/logos/' . $currentLogo)))
                        <img src="{{ asset('uploads/logos/' . $currentLogo) }}" alt="Logo {{ $slot }}" style="width: 100%; height: 100%; object-fit: contain; padding: 8px;">
                    @else
                        <i class="fas fa-image" style="font-size: 2rem; color: var(--text-muted);"></i>
                    @endif
                </div>
                <form action="{{ route('settings.logo') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="logo_slot" value="logo_{{ $slot }}">
                    <label class="btn btn-sm btn-secondary btn-block" style="cursor: pointer;">
                        <i class="fas fa-upload"></i> Ganti Logo {{ $slot }}
                        <input type="file" name="logo_file" accept="image/*" style="display: none;" onchange="this.form.submit()">
                    </label>
                </form>
            </div>
            @endfor
        </div>
    </div>
    
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

@push('styles')
<style>
    @media (max-width: 600px) {
        .logo-grid-mobile {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush
