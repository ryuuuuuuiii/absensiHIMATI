@extends('layouts.portal')

@section('title', 'Profil Saya')
@section('page_icon')
    <i class="fas fa-user-circle"></i>
@endsection

@push('styles')
<style>
.profile-wrapper {
    max-width: 620px;
    margin: 0 auto;
}

/* Centered Showcase Card — Pomegranate Profile Card Style */
.profile-center-card {
    background: #FFFFFF;
    border: 2px solid #061A2E;
    border-radius: 28px;
    padding: 36px 22px 24px;
    text-align: center;
    box-shadow: 0 6px 0 #061A2E;
    margin-bottom: 18px;
    position: relative;
    overflow: hidden;
}

.profile-center-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 92px;
    background: linear-gradient(135deg, #92EEFF 0%, #D8FFC5 100%);
    border-bottom: 2px solid #061A2E;
}

.profile-avatar-wrapper {
    position: relative;
    width: 108px;
    height: 108px;
    margin: 6px auto 14px auto;
    z-index: 2;
}

.profile-avatar-large {
    width: 108px;
    height: 108px;
    border-radius: 50%;
    padding: 4px;
    background: #30AFFF;
    border: 2.5px solid #061A2E;
    box-shadow: 0 5px 0 #061A2E;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    background: var(--bg-surface);
}

.profile-avatar-initials {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #30AFFF 0%, #92EEFF 100%);
    color: #061A2E;
    font-family: 'Gliker', 'Fredoka', sans-serif;
    font-size: 2.2rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.profile-avatar-edit-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #D8FFC5;
    color: #061A2E;
    border: 2px solid #061A2E;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    box-shadow: 0 3px 0 #061A2E;
    cursor: pointer;
    transition: transform var(--transition-fast);
}

.profile-avatar-edit-badge:hover {
    transform: translateY(-2px) scale(1.06);
    background: #92EEFF;
}

.profile-name {
    font-family: 'Gliker', 'Fredoka', sans-serif;
    font-size: 1.45rem;
    font-weight: 700;
    color: #061A2E;
    margin: 0 0 8px 0;
    line-height: 1.2;
}

.profile-meta-badges {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.profile-qr-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    background: #F4FBFF;
    border: 1.5px solid #061A2E;
    border-radius: var(--radius-full);
    font-size: 0.78rem;
    font-weight: 600;
    color: #061A2E;
    margin-bottom: 16px;
    box-shadow: 0 2px 0 #061A2E;
}

.profile-qr-badge code {
    font-weight: 800;
    color: #0284C7;
}

/* Action Trigger Buttons */
.profile-action-buttons {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 12px;
}

.profile-btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 11px 16px;
    border-radius: 9999px;
    font-size: 0.82rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    cursor: pointer;
    transition: all var(--transition-fast);
    border: 2px solid #061A2E;
    box-shadow: 0 4px 0 #061A2E;
    text-decoration: none;
}

.profile-btn-action.btn-edit {
    background: #30AFFF;
    color: #061A2E;
}
.profile-btn-action.btn-edit:hover, .profile-btn-action.btn-edit.active {
    background: #92EEFF;
    color: #061A2E;
    box-shadow: 0 6px 0 #061A2E;
    transform: translateY(-2px);
}

.profile-btn-action.btn-pwd {
    background: #D8FFC5;
    color: #061A2E;
}
.profile-btn-action.btn-pwd:hover, .profile-btn-action.btn-pwd.active {
    background: #92EEFF;
    color: #061A2E;
    box-shadow: 0 6px 0 #061A2E;
    transform: translateY(-2px);
}

/* Collapsible Form Card */
.profile-collapse-card {
    background: var(--bg-surface);
    border: 1.5px solid var(--blue-border);
    border-radius: var(--radius-xl);
    padding: 18px 16px;
    margin-bottom: 14px;
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.08);
    animation: profileSlideDown 0.24s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes profileSlideDown {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.collapse-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 12px;
    margin-bottom: 14px;
    border-bottom: 1px solid var(--border-color);
}

.collapse-header h3 {
    font-size: 1.02rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.collapse-close-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--bg-surface-subtle);
    border: 1px solid var(--border-color);
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all var(--transition-fast);
}

.collapse-close-btn:hover {
    background: rgba(239, 68, 68, 0.16);
    color: var(--danger);
    border-color: var(--danger-border);
}

.photo-preview-wrapper {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 12px;
    background: var(--bg-surface-subtle);
    border-radius: var(--radius-md);
    margin-top: 8px;
}

.photo-preview-thumb {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 1.5px solid var(--border-color);
}

/* Info Summary Table */
.info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px dashed var(--border-color);
    font-size: 0.86rem;
}

.info-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.info-label {
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-val {
    font-weight: 600;
    color: var(--text-primary);
    text-align: right;
}

/* Logout Bar */
.profile-logout-wrap {
    margin-top: 18px;
    text-align: center;
}

/* Feedback Trigger Button */
.profile-feedback-trigger-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 16px;
    border-radius: var(--radius-xl);
    background: var(--bg-surface);
    border: 1.5px solid var(--blue-border);
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08);
    transition: all var(--transition-fast);
    text-align: left;
    margin-bottom: 12px;
}

.profile-feedback-trigger-btn:hover {
    border-color: var(--blue-primary);
    box-shadow: 0 6px 18px rgba(37, 99, 235, 0.16);
    transform: translateY(-1px);
}

.profile-feedback-trigger-btn.active {
    background: var(--blue-subtle);
    border-color: var(--blue-primary);
}

.trigger-btn-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.trigger-btn-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--blue-subtle);
    color: var(--blue-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    transition: all var(--transition-fast);
}

.profile-feedback-trigger-btn.active .trigger-btn-icon {
    background: var(--blue-primary);
    color: #FFFFFF;
}

.trigger-btn-title {
    display: block;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.25;
}

.trigger-btn-desc {
    display: block;
    font-size: 0.74rem;
    color: var(--text-muted);
    margin-top: 2px;
}

.trigger-btn-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.trigger-chevron {
    color: var(--text-muted);
    font-size: 0.88rem;
    transition: transform var(--transition-fast);
}

.profile-feedback-trigger-btn.active .trigger-chevron {
    transform: rotate(180deg);
    color: var(--blue-primary);
}

/* Feedback Section */
.profile-feedback-card {
    background: var(--bg-surface);
    border: 1.5px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 18px 18px;
    margin-bottom: 14px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
}

.feedback-category-pills {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}

@media (min-width: 540px) {
    .feedback-category-pills {
        grid-template-columns: repeat(4, 1fr);
    }
}

.feedback-pill-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 10px;
    border-radius: var(--radius-md);
    border: 1.5px solid var(--border-color);
    background: var(--bg-surface-subtle);
    color: var(--text-secondary);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-fast);
    text-align: center;
    user-select: none;
}

.feedback-pill-label:hover {
    background: var(--bg-surface-hover);
    color: var(--text-primary);
}

.feedback-pill-input {
    display: none;
}

.feedback-pill-input:checked + .feedback-pill-label {
    background: var(--blue-subtle);
    border-color: var(--blue-primary);
    color: var(--blue-primary);
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.16);
    font-weight: 700;
}

.feedback-upload-zone {
    border: 1.5px dashed var(--border-color);
    background: var(--bg-surface-subtle);
    border-radius: var(--radius-lg);
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all var(--transition-fast);
    margin-bottom: 12px;
}

.feedback-upload-zone:hover {
    border-color: var(--blue-primary);
    background: var(--blue-subtle);
}

.feedback-upload-icon {
    width: 38px;
    height: 38px;
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    color: var(--blue-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    border: 1px solid var(--border-color);
}

.feedback-upload-info {
    flex: 1;
    min-width: 0;
}

.feedback-upload-title {
    font-size: 0.84rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 2px;
}

.feedback-upload-hint {
    font-size: 0.72rem;
    color: var(--text-muted);
}

.feedback-preview-box {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: var(--bg-surface-subtle);
    border: 1px solid var(--blue-border);
    border-radius: var(--radius-lg);
    margin-bottom: 12px;
    position: relative;
}

.feedback-preview-thumb {
    width: 52px;
    height: 52px;
    border-radius: var(--radius-md);
    object-fit: cover;
    border: 1px solid var(--border-color);
    cursor: pointer;
}

.feedback-preview-meta {
    flex: 1;
    min-width: 0;
}

.feedback-preview-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.feedback-preview-size {
    font-size: 0.72rem;
    color: var(--text-muted);
}

.feedback-btn-remove {
    background: rgba(239, 68, 68, 0.12);
    color: var(--danger);
    border: 1px solid var(--danger-border);
    border-radius: var(--radius-md);
    padding: 6px 10px;
    font-size: 0.76rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all var(--transition-fast);
}

.feedback-btn-remove:hover {
    background: var(--danger);
    color: #FFFFFF;
}

.feedback-history-wrap {
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid var(--border-color);
}

.feedback-history-title {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.feedback-item {
    background: var(--bg-surface-subtle);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 12px 14px;
    margin-bottom: 8px;
    transition: all var(--transition-fast);
}

.feedback-item:last-child {
    margin-bottom: 0;
}

.feedback-item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}

.feedback-item-subjek {
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.feedback-item-pesan {
    font-size: 0.8rem;
    color: var(--text-secondary);
    line-height: 1.45;
    margin-bottom: 6px;
    white-space: pre-line;
}

.feedback-item-reply {
    background: rgba(37, 99, 235, 0.08);
    border-left: 3px solid var(--blue-primary);
    border-radius: 4px;
    padding: 8px 10px;
    margin-top: 8px;
    font-size: 0.78rem;
    color: var(--text-primary);
}

/* ==========================================================================
   Dark Mode Overrides for Profile & Feedback Buttons
   ========================================================================== */
[data-theme="dark"] .profile-feedback-trigger-btn {
    background: #111827;
    border-color: rgba(59, 130, 246, 0.28);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
}

[data-theme="dark"] .profile-feedback-trigger-btn:hover {
    background: #162032;
    border-color: #3B82F6;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.5);
}

[data-theme="dark"] .profile-feedback-trigger-btn.active {
    background: rgba(37, 99, 235, 0.18);
    border-color: #3B82F6;
}

[data-theme="dark"] .profile-feedback-trigger-btn .trigger-btn-icon {
    background: rgba(37, 99, 235, 0.28);
    color: #60A5FA;
}

[data-theme="dark"] .profile-feedback-trigger-btn.active .trigger-btn-icon {
    background: #2563EB;
    color: #FFFFFF;
}

[data-theme="dark"] .profile-feedback-trigger-btn .trigger-btn-title {
    color: #F8FAFC;
}

[data-theme="dark"] .profile-feedback-trigger-btn .trigger-btn-desc {
    color: #94A3B8;
}

[data-theme="dark"] .profile-feedback-trigger-btn .trigger-chevron {
    color: #64748B;
}

[data-theme="dark"] .profile-feedback-trigger-btn.active .trigger-chevron {
    color: #60A5FA;
}

[data-theme="dark"] .profile-logout-wrap .btn-danger {
    background: rgba(239, 68, 68, 0.14) !important;
    color: #F87171 !important;
    border: 1.5px solid rgba(239, 68, 68, 0.35) !important;
    box-shadow: none !important;
}

[data-theme="dark"] .profile-logout-wrap .btn-danger:hover {
    background: #DC2626 !important;
    color: #FFFFFF !important;
    border-color: #DC2626 !important;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.4) !important;
}

/* Feedback Form Elements in Dark Mode */
[data-theme="dark"] .feedback-pill-label {
    background: #1F2937;
    border-color: #374151;
    color: #CBD5E1;
}

[data-theme="dark"] .feedback-pill-label:hover {
    background: #374151;
    color: #FFFFFF;
}

[data-theme="dark"] .feedback-pill-input:checked + .feedback-pill-label {
    background: rgba(37, 99, 235, 0.25);
    border-color: #3B82F6;
    color: #93C5FD;
    box-shadow: 0 2px 10px rgba(59, 130, 246, 0.25);
}

[data-theme="dark"] .feedback-upload-zone {
    background: #0B0F19;
    border-color: #374151;
}

[data-theme="dark"] .feedback-upload-zone:hover {
    background: rgba(37, 99, 235, 0.12);
    border-color: #3B82F6;
}

[data-theme="dark"] .feedback-upload-icon {
    background: #1F2937;
    color: #60A5FA;
    border-color: #374151;
}

[data-theme="dark"] .feedback-upload-title {
    color: #F8FAFC;
}

[data-theme="dark"] .feedback-upload-hint {
    color: #94A3B8;
}

[data-theme="dark"] .feedback-preview-box {
    background: #1F2937;
    border-color: rgba(59, 130, 246, 0.35);
}

[data-theme="dark"] .feedback-preview-name {
    color: #F8FAFC;
}

[data-theme="dark"] .feedback-preview-size {
    color: #94A3B8;
}

[data-theme="dark"] .feedback-item {
    background: #111827;
    border-color: #1F2937;
}

[data-theme="dark"] .feedback-item-subjek {
    color: #F8FAFC;
}

[data-theme="dark"] .feedback-item-pesan {
    color: #CBD5E1;
}

[data-theme="dark"] .feedback-item-reply {
    background: rgba(37, 99, 235, 0.14);
    border-left-color: #3B82F6;
    color: #E2E8F0;
}
</style>
@endpush

@section('content')
<div class="profile-wrapper">

    <div class="portal-page-header">
        <h1 class="portal-page-title">
            <i class="fas fa-user-circle"></i> Profil Saya
        </h1>
    </div>

    <!-- 1. Centered Showcase Profile Card (Foto Gede di Tengah) -->
    <div class="profile-center-card">
        <div class="profile-avatar-wrapper">
            <div class="profile-avatar-large">
                @if ($member->profile_photo_url)
                    <img src="{{ $member->profile_photo_url }}" alt="{{ $member->nama_lengkap }}" id="profileDisplayImg">
                @else
                    <div class="profile-avatar-initials">{{ $member->initials }}</div>
                @endif
            </div>
            <button type="button" class="profile-avatar-edit-badge" onclick="toggleEditSection('edit')" title="Ubah Foto Profil">
                <i class="fas fa-camera"></i>
            </button>
        </div>

        <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--blue-primary); margin-bottom: 4px;">
            Profil Pengurus
        </div>
        <h2 class="profile-name">{{ $member->nama_lengkap }}</h2>

        <div class="profile-meta-badges">
            <span class="badge badge-primary">
                <i class="fas fa-id-badge"></i> {{ $member->jabatan }}
            </span>
            @if ($member->isBphInti())
                <span class="badge badge-warning">
                    <i class="fas fa-crown"></i> Pengurus Inti
                </span>
            @else
                <span class="badge badge-dept">
                    <i class="fas fa-building text-primary"></i> Dept. {{ $member->departemen }}
                </span>
                @if ($member->is_bph)
                    <span class="badge badge-warning">
                        <i class="fas fa-shield-alt"></i> BPH
                    </span>
                @endif
            @endif
            @if ($member->sp_level == 1)
                <span class="badge badge-sp-1">
                    <i class="fas fa-exclamation-triangle"></i> SP 1
                </span>
            @elseif ($member->sp_level == 2)
                <span class="badge badge-sp-2">
                    <i class="fas fa-exclamation-circle"></i> SP 2
                </span>
            @elseif ($member->sp_level >= 3)
                <span class="badge badge-sp-3">
                    <i class="fas fa-user-times"></i> SP 3 (Diberhentikan)
                </span>
            @endif
        </div>

        <div class="profile-qr-badge">
            <i class="fas fa-qrcode"></i> ID Anggota: <code>{{ $member->qr_code }}</code>
        </div>

        <!-- Tombol Aksi: Klik untuk Membuka Form Edit / Password -->
        <div class="profile-action-buttons">
            <button type="button" class="profile-btn-action btn-edit" id="btnToggleEdit" onclick="toggleEditSection('edit')">
                <i class="fas fa-user-edit"></i>
                <span>Edit Akun</span>
            </button>
            <button type="button" class="profile-btn-action btn-pwd" id="btnTogglePwd" onclick="toggleEditSection('password')">
                <i class="fas fa-key"></i>
                <span>Ganti Password</span>
            </button>
        </div>
    </div>

    <!-- 2. Form Edit Profil (Tersembunyi, Hanya Muncul Saat Tombol Diklik) -->
    <div id="editProfileCollapse" class="profile-collapse-card" style="display: {{ ($errors->has('email') || $errors->has('profile_photo')) ? 'block' : 'none' }};">
        <div class="collapse-header">
            <h3><i class="fas fa-user-edit text-primary"></i> Edit Data Akun</h3>
            <button type="button" class="collapse-close-btn" onclick="toggleEditSection('close')" title="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="alert alert-info" style="font-size: 0.8rem; padding: 10px 12px; margin-bottom: 14px;">
            <i class="fas fa-info-circle"></i>
            <span>Nama lengkap, jabatan, dan departemen hanya dapat diubah oleh Admin. Anda dapat mengubah email dan foto profil Anda.</span>
        </div>

        <form action="{{ route('portal.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group mb-4">
                <label class="form-label" for="email">Email Login <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-control" required value="{{ old('email', $member->email) }}" placeholder="nama@himati.id">
                <small style="color: var(--text-muted); display: block; margin-top: 4px; font-size: 0.76rem;">
                    Email ini digunakan untuk masuk ke Portal Pengurus.
                </small>
            </div>

            <div class="form-group mb-4">
                <label class="form-label" for="profile_photo">Unggah Foto Profil Baru</label>
                <input type="file" id="profile_photo" name="profile_photo" class="form-control" accept="image/*" onchange="previewSelectedPhoto(this)">
                <small style="color: var(--text-muted); display: block; margin-top: 4px; font-size: 0.76rem;">
                    Format JPG, JPEG, PNG atau WEBP (Maksimal 5MB).
                </small>

                <div id="photoPreviewWrap" class="photo-preview-wrapper" style="display: none;">
                    <img id="photoPreviewImg" src="" alt="Preview Foto" class="photo-preview-thumb">
                    <span style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 600;">Pratinjau foto terpilih</span>
                </div>
            </div>

            @if ($member->profile_photo_url)
                <div class="form-group mb-4">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--danger); font-size: 0.85rem; font-weight: 600;">
                        <input type="checkbox" name="remove_photo" value="1" {{ old('remove_photo') ? 'checked' : '' }} style="accent-color: var(--danger);">
                        <span>Hapus foto profil saat ini (gunakan inisial)</span>
                    </label>
                </div>
            @endif

            <div style="display: flex; gap: 8px; margin-top: 14px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleEditSection('close')">
                    Batal
                </button>
            </div>
        </form>
    </div>

    <!-- 3. Form Ganti Password (Tersembunyi, Hanya Muncul Saat Tombol Diklik) -->
    <div id="changePasswordCollapse" class="profile-collapse-card" style="display: {{ ($errors->has('current_password') || $errors->has('password')) ? 'block' : 'none' }};">
        <div class="collapse-header">
            <h3><i class="fas fa-key text-warning"></i> Ganti Password</h3>
            <button type="button" class="collapse-close-btn" onclick="toggleEditSection('close')" title="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('portal.profile.password') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group mb-3">
                <label class="form-label" for="current_password">Password Saat Ini <span class="required">*</span></label>
                <div style="position: relative;">
                    <input type="password" id="current_password" name="current_password" class="form-control" required autocomplete="current-password" placeholder="Masukkan password saat ini">
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label" for="password">Password Baru <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password" placeholder="Minimal 8 karakter">
            </div>

            <div class="form-group mb-4">
                <label class="form-label" for="password_confirmation">Konfirmasi Password Baru <span class="required">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required autocomplete="new-password" placeholder="Ketik ulang password baru">
            </div>

            <div style="display: flex; gap: 8px; margin-top: 14px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-check-circle"></i> Perbarui Password
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleEditSection('close')">
                    Batal
                </button>
            </div>
        </form>
    </div>

    @if ($member->sp_level > 0)
        <div class="sp-portal-alert sp-level-{{ $member->sp_level }} mb-4">
            <div class="sp-portal-alert-icon">
                @if ($member->sp_level == 1)
                    <i class="fas fa-exclamation-triangle"></i>
                @elseif ($member->sp_level == 2)
                    <i class="fas fa-exclamation-circle"></i>
                @else
                    <i class="fas fa-user-times"></i>
                @endif
            </div>
            <div class="sp-portal-alert-body">
                <div class="sp-portal-alert-header">
                    <strong>Status Akun: {{ $member->sp_full_label }}</strong>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: 6px;">
                    <p class="sp-portal-alert-desc" style="margin: 0;">
                        <strong>Pelanggaran:</strong> {{ $member->sp_reason_display }}
                    </p>
                    <a
                        href="{{ route('portal.announcements.index') }}"
                        class="btn btn-secondary btn-sm"
                        style="font-size: 0.75rem; padding: 4px 12px; flex-shrink: 0; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;"
                        title="Buka rincian lengkap di pesan">
                        <i class="fas fa-envelope-open-text"></i>
                        <span>Lihat Rincian</span>
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- 4. Kartu Ringkasan Informasi Akun -->
    <div class="card mb-4" style="padding: 16px 18px;">
        <div class="card-header" style="margin-bottom: 10px; padding-bottom: 10px;">
            <h3 class="card-title" style="font-size: 0.95rem;">
                <i class="fas fa-info-circle text-primary"></i> Informasi Akun
            </h3>
            <span class="badge badge-success"><i class="fas fa-check"></i> Akun Aktif</span>
        </div>

        <div class="info-row">
            <span class="info-label"><i class="far fa-envelope"></i> Email Terdaftar</span>
            <span class="info-val">{{ $member->email ?? 'Belum disetel' }}</span>
        </div>

        <div class="info-row">
            <span class="info-label"><i class="far fa-clock"></i> Login Terakhir</span>
            <span class="info-val">
                {{ $member->last_login_at ? $member->last_login_at->locale('id')->translatedFormat('d M Y, H:i') . ' WITA' : 'Baru saja' }}
            </span>
        </div>

        <div class="info-row">
            <span class="info-label"><i class="far fa-calendar-alt"></i> Terdaftar Sejak</span>
            <span class="info-val">
                {{ $member->created_at ? $member->created_at->locale('id')->translatedFormat('d M Y') : '-' }}
            </span>
        </div>

        <div class="info-row">
            <span class="info-label"><i class="fas fa-qrcode"></i> Akses Cepat QR</span>
            <span class="info-val">
                <a href="{{ route('portal.qr') }}" class="btn-chip btn-chip-blue" style="font-size: 0.72rem; padding: 2px 10px;">
                    <i class="fas fa-external-link-alt"></i> Buka QR Saya
                </a>
            </span>
        </div>
    </div>

    <!-- 5. Tombol Pemicu Feedback (Muncul di atas tombol Logout) -->
    <div class="profile-feedback-btn-wrap">
        <button type="button" class="profile-feedback-trigger-btn {{ ($errors->has('kategori') || $errors->has('subjek') || $errors->has('pesan') || $errors->has('screenshot') || session('feedback_success')) ? 'active' : '' }}" id="btnToggleFeedback" onclick="toggleFeedbackSection()">
            <div class="trigger-btn-left">
                <span class="trigger-btn-icon">
                    <i class="fas fa-comment-dots"></i>
                </span>
                <div class="trigger-btn-text">
                    <span class="trigger-btn-title">Beri Masukan & Laporan</span>
                    <span class="trigger-btn-desc">Saran fitur, pertanyaan, atau laporan bug</span>
                </div>
            </div>
            <div class="trigger-btn-right">
                @if(isset($feedbacks) && $feedbacks->isNotEmpty())
                    <span class="badge badge-primary" style="font-size: 0.72rem; padding: 2px 7px;">{{ $feedbacks->count() }}</span>
                @endif
                <i class="fas fa-chevron-down trigger-chevron" id="feedbackChevron" style="{{ ($errors->has('kategori') || $errors->has('subjek') || $errors->has('pesan') || $errors->has('screenshot') || session('feedback_success')) ? 'transform: rotate(180deg); color: var(--blue-primary);' : '' }}"></i>
            </div>
        </button>
    </div>

    <!-- Kotak Menu Feedback (Tersembunyi, Muncul Saat Tombol Diklik) -->
    <div class="profile-collapse-card" id="feedbackCollapse" style="display: {{ ($errors->has('kategori') || $errors->has('subjek') || $errors->has('pesan') || $errors->has('screenshot') || session('feedback_success')) ? 'block' : 'none' }}; margin-bottom: 14px;">
        <div class="collapse-header">
            <h3>
                <i class="fas fa-comment-dots text-primary"></i> Beri Masukan & Laporan
            </h3>
            <button type="button" class="collapse-close-btn" onclick="toggleFeedbackSection('close')" title="Tutup Formulir">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 14px; line-height: 1.45;">
            Punya ide fitur baru, kritik, pertanyaan, atau menemukan error di aplikasi? Kirimkan masukan Anda beserta screenshot (bila ada) langsung ke Admin.
        </p>

        @if(session('feedback_success'))
            <div class="alert alert-success" style="margin-bottom: 14px; padding: 10px 14px; font-size: 0.84rem; border-radius: var(--radius-md); display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                <span>{{ session('feedback_success') }}</span>
            </div>
        @endif

        @if($errors->has('kategori') || $errors->has('subjek') || $errors->has('pesan') || $errors->has('screenshot'))
            <div class="alert alert-danger" style="margin-bottom: 14px; padding: 10px 14px; font-size: 0.82rem; border-radius: var(--radius-md);">
                <div style="font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-exclamation-circle"></i> Periksa kembali isian formulir:
                </div>
                <ul style="margin: 0 0 0 16px; padding: 0;">
                    @error('kategori') <li>{{ $message }}</li> @enderror
                    @error('subjek') <li>{{ $message }}</li> @enderror
                    @error('pesan') <li>{{ $message }}</li> @enderror
                    @error('screenshot') <li>{{ $message }}</li> @enderror
                </ul>
            </div>
        @endif

        <form action="{{ route('portal.feedback.store') }}" method="POST" enctype="multipart/form-data" id="feedbackForm">
            @csrf

            <!-- Pilihan Kategori -->
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                    Kategori Masukan <span class="text-danger">*</span>
                </label>
                <div class="feedback-category-pills">
                    <input type="radio" name="kategori" id="kat_bug" value="bug" class="feedback-pill-input" {{ old('kategori', 'bug') === 'bug' ? 'checked' : '' }}>
                    <label for="kat_bug" class="feedback-pill-label">
                        <i class="fas fa-bug text-danger"></i> Bug / Error
                    </label>

                    <input type="radio" name="kategori" id="kat_saran" value="saran" class="feedback-pill-input" {{ old('kategori') === 'saran' ? 'checked' : '' }}>
                    <label for="kat_saran" class="feedback-pill-label">
                        <i class="fas fa-lightbulb text-warning"></i> Saran Fitur
                    </label>

                    <input type="radio" name="kategori" id="kat_tanya" value="pertanyaan" class="feedback-pill-input" {{ old('kategori') === 'pertanyaan' ? 'checked' : '' }}>
                    <label for="kat_tanya" class="feedback-pill-label">
                        <i class="fas fa-question-circle text-info"></i> Pertanyaan
                    </label>

                    <input type="radio" name="kategori" id="kat_lain" value="lainnya" class="feedback-pill-input" {{ old('kategori') === 'lainnya' ? 'checked' : '' }}>
                    <label for="kat_lain" class="feedback-pill-label">
                        <i class="fas fa-comment text-secondary"></i> Lainnya
                    </label>
                </div>
            </div>

            <!-- Subjek -->
            <div class="form-group" style="margin-bottom: 12px;">
                <label for="feedbackSubjek" style="font-size: 0.8rem; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; display: block;">
                    Subjek / Judul <span class="text-danger">*</span>
                </label>
                <input type="text" name="subjek" id="feedbackSubjek" class="form-control" placeholder="Contoh: Tombol scan tidak muncul di HP saya" value="{{ old('subjek') }}" required maxlength="150" style="font-size: 0.86rem; padding: 9px 12px; border-radius: var(--radius-md);">
            </div>

            <!-- Pesan / Detail Masukan -->
            <div class="form-group" style="margin-bottom: 12px;">
                <label for="feedbackPesan" style="font-size: 0.8rem; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; display: block;">
                    Pesan & Rincian <span class="text-danger">*</span>
                </label>
                <textarea name="pesan" id="feedbackPesan" class="form-control" rows="3" placeholder="Tuliskan masukan, pertanyaan, atau kendala Anda secara lengkap..." required maxlength="3000" style="font-size: 0.86rem; padding: 10px 12px; border-radius: var(--radius-md); resize: vertical;">{{ old('pesan') }}</textarea>
            </div>

            <!-- Upload Screenshot (Opsional) -->
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                    Screenshot / Bukti Foto <span style="font-weight: 400; color: var(--text-muted); font-size: 0.76rem;">(Opsional)</span>
                </label>

                <!-- Hidden file input -->
                <input type="file" name="screenshot" id="feedbackScreenshot" accept="image/png,image/jpeg,image/jpg,image/webp" style="display: none;" onchange="previewFeedbackScreenshot(this)">

                <!-- Dropzone / Click Box -->
                <div class="feedback-upload-zone" id="feedbackUploadZone" onclick="document.getElementById('feedbackScreenshot').click()">
                    <div class="feedback-upload-icon">
                        <i class="fas fa-camera"></i>
                    </div>
                    <div class="feedback-upload-info">
                        <div class="feedback-upload-title">Lampirkan Screenshot Bukti</div>
                        <div class="feedback-upload-hint">Klik di sini untuk memilih gambar (JPG, PNG, WEBP maks 5MB)</div>
                    </div>
                    <span class="btn-chip btn-chip-blue" style="font-size: 0.72rem; padding: 4px 10px;">
                        <i class="fas fa-upload"></i> Pilih
                    </span>
                </div>

                <!-- Preview Box (Hidden saat belum pilih file) -->
                <div class="feedback-preview-box" id="feedbackPreviewBox" style="display: none;">
                    <img id="feedbackPreviewImg" src="" alt="Screenshot Preview" class="feedback-preview-thumb" onclick="openScreenshotModal(this.src, 'Preview Screenshot')">
                    <div class="feedback-preview-meta">
                        <div class="feedback-preview-name" id="feedbackPreviewName">-</div>
                        <div class="feedback-preview-size" id="feedbackPreviewSize">-</div>
                    </div>
                    <button type="button" class="feedback-btn-remove" onclick="clearFeedbackScreenshot()" title="Hapus Gambar">
                        <i class="fas fa-times"></i> Hapus
                    </button>
                </div>
            </div>

            <div style="display: flex; gap: 8px; align-items: center;">
                <button type="submit" class="btn btn-primary" style="flex: 1; padding: 10px 16px; font-weight: 700; border-radius: var(--radius-lg); font-size: 0.88rem;">
                    <i class="fas fa-paper-plane"></i> Kirim Feedback ke Admin
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleFeedbackSection('close')" style="padding: 10px 16px; font-weight: 600; border-radius: var(--radius-lg); font-size: 0.88rem;">
                    Batal
                </button>
            </div>
        </form>

        <!-- Riwayat Masukan Pengurus -->
        @if(isset($feedbacks) && $feedbacks->isNotEmpty())
            <div class="feedback-history-wrap">
                <div class="feedback-history-title">
                    <span><i class="fas fa-history text-primary"></i> Riwayat Masukan Saya ({{ $feedbacks->count() }})</span>
                </div>

                <div class="feedback-list">
                    @foreach($feedbacks as $fb)
                        <div class="feedback-item">
                            <div class="feedback-item-header">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    @if($fb->kategori === 'bug')
                                        <span class="badge badge-danger" style="font-size: 0.68rem; padding: 2px 7px;"><i class="fas fa-bug"></i> Bug</span>
                                    @elseif($fb->kategori === 'saran')
                                        <span class="badge badge-warning" style="font-size: 0.68rem; padding: 2px 7px;"><i class="fas fa-lightbulb"></i> Saran</span>
                                    @elseif($fb->kategori === 'pertanyaan')
                                        <span class="badge badge-info" style="font-size: 0.68rem; padding: 2px 7px;"><i class="fas fa-question-circle"></i> Tanya</span>
                                    @else
                                        <span class="badge badge-secondary" style="font-size: 0.68rem; padding: 2px 7px;"><i class="fas fa-comment"></i> Lainnya</span>
                                    @endif

                                    <span style="font-size: 0.72rem; color: var(--text-muted);">
                                        {{ $fb->created_at ? $fb->created_at->locale('id')->diffForHumans() : '-' }}
                                    </span>
                                </div>

                                <div>
                                    @if($fb->status === 'resolved')
                                        <span class="badge badge-success" style="font-size: 0.68rem; padding: 2px 8px;"><i class="fas fa-check-circle"></i> Selesai</span>
                                    @elseif($fb->status === 'read')
                                        <span class="badge badge-info" style="font-size: 0.68rem; padding: 2px 8px;"><i class="fas fa-eye"></i> Ditinjau</span>
                                    @else
                                        <span class="badge badge-warning" style="font-size: 0.68rem; padding: 2px 8px;"><i class="fas fa-clock"></i> Menunggu</span>
                                    @endif
                                </div>
                            </div>

                            <div class="feedback-item-subjek">{{ $fb->subjek }}</div>
                            <div class="feedback-item-pesan">{{ $fb->pesan }}</div>

                            @if($fb->screenshot_url)
                                <div style="margin-top: 6px;">
                                    <button type="button" class="btn-chip btn-chip-blue" style="font-size: 0.72rem; padding: 2px 8px; border: none; cursor: pointer;" onclick="openScreenshotModal('{{ $fb->screenshot_url }}', 'Screenshot: {{ addslashes($fb->subjek) }}')">
                                        <i class="fas fa-image"></i> Lihat Bukti Screenshot
                                    </button>
                                </div>
                            @endif

                            @if($fb->admin_notes)
                                <div class="feedback-item-reply">
                                    <div style="font-weight: 700; font-size: 0.75rem; margin-bottom: 2px; color: var(--blue-primary); display: flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-reply"></i> Tanggapan Admin:
                                    </div>
                                    <div>{{ $fb->admin_notes }}</div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- 6. Tombol Keluar (Logout) -->
    <div class="profile-logout-wrap">
        <button type="button" class="btn btn-danger btn-block" onclick="openPortalLogoutModal()" style="padding: 11px 16px; font-weight: 700; border-radius: var(--radius-lg); width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <i class="fas fa-sign-out-alt"></i> Keluar dari Akun (Logout)
        </button>
    </div>

</div>

<!-- Modal Preview Screenshot Feedback -->
<div class="modal-overlay" id="feedbackModal" onclick="closeScreenshotModal(event)" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
    <div class="modal-dialog" style="max-width: 600px; width: 100%; background: var(--bg-surface); border-radius: var(--radius-xl); overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.3); border: 1px solid var(--border-color);" onclick="event.stopPropagation()">
        <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; border-bottom: 1px solid var(--border-color);">
            <h3 class="modal-title" id="feedbackModalTitle" style="font-size: 0.95rem; font-weight: 700; margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-image text-primary"></i> Lampiran Screenshot
            </h3>
            <button type="button" class="btn-close" onclick="closeScreenshotModalDirect()" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 16px; text-align: center; background: #0b0f19;">
            <img id="feedbackModalImg" src="" alt="Screenshot" style="max-width: 100%; max-height: 68vh; object-fit: contain; border-radius: var(--radius-md);">
        </div>
        <div class="modal-footer" style="padding: 10px 18px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 8px;">
            <a id="feedbackModalDownload" href="#" target="_blank" class="btn btn-sm btn-primary" download>
                <i class="fas fa-download"></i> Unduh Gambar
            </a>
            <button type="button" class="btn btn-sm btn-secondary" onclick="closeScreenshotModalDirect()">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleEditSection(type) {
    const editCollapse = document.getElementById('editProfileCollapse');
    const pwdCollapse = document.getElementById('changePasswordCollapse');
    const btnEdit = document.getElementById('btnToggleEdit');
    const btnPwd = document.getElementById('btnTogglePwd');

    // Tutup feedback jika sedang dibuka
    toggleFeedbackSection('close');

    if (type === 'edit') {
        const isOpening = (editCollapse.style.display === 'none' || editCollapse.style.display === '');
        editCollapse.style.display = isOpening ? 'block' : 'none';
        pwdCollapse.style.display = 'none';

        btnEdit.classList.toggle('active', isOpening);
        btnPwd.classList.remove('active');

        if (isOpening) {
            editCollapse.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    } else if (type === 'password') {
        const isOpening = (pwdCollapse.style.display === 'none' || pwdCollapse.style.display === '');
        pwdCollapse.style.display = isOpening ? 'block' : 'none';
        editCollapse.style.display = 'none';

        btnPwd.classList.toggle('active', isOpening);
        btnEdit.classList.remove('active');

        if (isOpening) {
            pwdCollapse.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    } else if (type === 'close') {
        editCollapse.style.display = 'none';
        pwdCollapse.style.display = 'none';
        btnEdit.classList.remove('active');
        btnPwd.classList.remove('active');
    }
}

function toggleFeedbackSection(action) {
    const feedbackCollapse = document.getElementById('feedbackCollapse');
    const btnToggle = document.getElementById('btnToggleFeedback');
    const chevron = document.getElementById('feedbackChevron');

    if (!feedbackCollapse) return;

    const isCurrentlyOpen = (feedbackCollapse.style.display === 'block');

    if (action === 'close' || isCurrentlyOpen) {
        feedbackCollapse.style.display = 'none';
        if (btnToggle) btnToggle.classList.remove('active');
        if (chevron) {
            chevron.style.transform = 'rotate(0deg)';
            chevron.style.color = '';
        }
    } else {
        // Tutup edit profil & password jika sedang terbuka
        const editCollapse = document.getElementById('editProfileCollapse');
        const pwdCollapse = document.getElementById('changePasswordCollapse');
        const btnEdit = document.getElementById('btnToggleEdit');
        const btnPwd = document.getElementById('btnTogglePwd');
        if (editCollapse) editCollapse.style.display = 'none';
        if (pwdCollapse) pwdCollapse.style.display = 'none';
        if (btnEdit) btnEdit.classList.remove('active');
        if (btnPwd) btnPwd.classList.remove('active');

        feedbackCollapse.style.display = 'block';
        if (btnToggle) btnToggle.classList.add('active');
        if (chevron) {
            chevron.style.transform = 'rotate(180deg)';
            chevron.style.color = 'var(--blue-primary)';
        }
        feedbackCollapse.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function previewSelectedPhoto(input) {
    const previewWrap = document.getElementById('photoPreviewWrap');
    const previewImg = document.getElementById('photoPreviewImg');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewWrap.style.display = 'flex';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        previewWrap.style.display = 'none';
    }
}

/* Feedback Screenshot Preview Functions */
function previewFeedbackScreenshot(input) {
    const previewBox = document.getElementById('feedbackPreviewBox');
    const previewImg = document.getElementById('feedbackPreviewImg');
    const previewName = document.getElementById('feedbackPreviewName');
    const previewSize = document.getElementById('feedbackPreviewSize');
    const uploadZone = document.getElementById('feedbackUploadZone');

    if (input.files && input.files[0]) {
        const file = input.files[0];

        // Validasi tipe file
        if (!file.type.startsWith('image/')) {
            alert('File yang dipilih harus berupa gambar (JPG, PNG, WEBP).');
            input.value = '';
            return;
        }

        // Validasi ukuran maks 5MB
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file gambar maksimal 5MB.');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewName.textContent = file.name;
            const sizeKB = (file.size / 1024).toFixed(1);
            previewSize.textContent = sizeKB > 1024 ? (sizeKB / 1024).toFixed(2) + ' MB' : sizeKB + ' KB';

            previewBox.style.display = 'flex';
            uploadZone.style.display = 'none';
        };
        reader.readAsDataURL(file);
    } else {
        clearFeedbackScreenshot();
    }
}

function clearFeedbackScreenshot() {
    const fileInput = document.getElementById('feedbackScreenshot');
    const previewBox = document.getElementById('feedbackPreviewBox');
    const previewImg = document.getElementById('feedbackPreviewImg');
    const uploadZone = document.getElementById('feedbackUploadZone');

    if (fileInput) fileInput.value = '';
    if (previewImg) previewImg.src = '';
    if (previewBox) previewBox.style.display = 'none';
    if (uploadZone) uploadZone.style.display = 'flex';
}

/* Modal Screenshot Functions */
function openScreenshotModal(imgUrl, title) {
    const modal = document.getElementById('feedbackModal');
    const img = document.getElementById('feedbackModalImg');
    const titleEl = document.getElementById('feedbackModalTitle');
    const dlBtn = document.getElementById('feedbackModalDownload');

    if (!modal || !img) return;

    img.src = imgUrl;
    if (titleEl && title) {
        titleEl.innerHTML = '<i class="fas fa-image text-primary"></i> ' + title;
    }
    if (dlBtn) {
        dlBtn.href = imgUrl;
    }

    modal.style.display = 'flex';
}

function closeScreenshotModalDirect() {
    const modal = document.getElementById('feedbackModal');
    if (modal) modal.style.display = 'none';
}

function closeScreenshotModal(event) {
    if (event.target.id === 'feedbackModal') {
        closeScreenshotModalDirect();
    }
}
</script>
@endpush
