@extends(auth('member')->check() ? 'layouts.portal' : 'layouts.public')

@section('title', 'Buat Rapat Baru')
@section('page_icon')
    <i class="fas fa-calendar-plus"></i>
@endsection
@section('page_heading', config('himati.app_name'))
@section('page_subheading', 'Buat Jadwal & Absensi Rapat HIMA TI')
@section('container_width', '760px')

@section('content')
<style>
.creator-top-bar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-bottom: var(--space-4);
    gap: var(--space-3);
}
.creator-top-badge {
    font-size: 0.82rem;
    color: var(--text-muted);
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-weight: 500;
}
.creator-top-badge .status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10B981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
    display: inline-block;
}
.creator-header-card {
    background: var(--bg-surface-subtle);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 12px 16px;
    margin-bottom: var(--space-6);
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all var(--transition-fast);
}
[data-theme="dark"] .creator-header-card {
    background: #111827 !important;
    border-color: rgba(59, 130, 246, 0.25) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
}
.creator-avatar-wrapper {
    position: relative;
    flex-shrink: 0;
}
.creator-avatar-bubble {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
    color: #FFFFFF;
    font-weight: 700;
    font-size: 1.05rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
}
.creator-verified-badge {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 17px;
    height: 17px;
    border-radius: 50%;
    background: #10B981;
    color: #FFFFFF;
    border: 2px solid var(--bg-surface);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}
[data-theme="dark"] .creator-verified-badge {
    border-color: #111827 !important;
}
.creator-header-info {
    flex: 1;
    min-width: 0;
}
.creator-name-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.25;
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.creator-chips-row {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.creator-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 500;
    background: rgba(37, 99, 235, 0.08);
    color: var(--text-secondary);
    border: 1px solid rgba(37, 99, 235, 0.16);
    line-height: 1.2;
}
.creator-chip i {
    font-size: 0.72rem;
    color: #3B82F6;
}
[data-theme="dark"] .creator-chip {
    background: rgba(30, 41, 59, 0.75);
    color: #94A3B8;
    border-color: rgba(255, 255, 255, 0.08);
}
[data-theme="dark"] .creator-chip i {
    color: #60A5FA;
}
@media (max-width: 480px) {
    .creator-header-card {
        padding: 10px 12px;
        gap: 12px;
    }
    .creator-avatar-bubble {
        width: 40px;
        height: 40px;
        font-size: 0.95rem;
    }
    .creator-name-title {
        font-size: 0.92rem;
    }
    .creator-chip {
        font-size: 0.7rem;
        padding: 2px 7px;
    }
}
.step-nav-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: var(--space-6);
    padding-top: var(--space-4);
    border-top: 1px solid var(--border-color);
}
.step-nav-actions.right-only {
    justify-content: flex-end;
}
.step-nav-actions .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 22px;
    font-size: 0.95rem;
    font-weight: 600;
    border-radius: var(--radius-lg);
    transition: all 0.2s ease;
}
@media (max-width: 640px) {
    .step-nav-actions {
        gap: 10px;
    }
    .step-nav-actions .btn {
        padding: 11px 16px;
        font-size: 0.9rem;
    }
}
.meeting-datetime-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
    margin-top: var(--space-4);
    width: 100%;
    box-sizing: border-box;
}
.meeting-datetime-grid .form-group {
    min-width: 0;
    margin-bottom: 0;
    width: 100%;
    box-sizing: border-box;
}
.meeting-datetime-grid .form-control {
    width: 100% !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
}
@media (max-width: 576px) {
    .meeting-datetime-grid {
        grid-template-columns: 1fr;
        gap: var(--space-4);
    }
}
</style>

<div class="public-card" style="max-width: 760px; margin: 0 auto;">
    @php
        $creatorName = $loggedMember ? $loggedMember->nama_lengkap : ($loggedAdmin ? $loggedAdmin->nama : 'Administrator');
        $creatorRole = $loggedMember ? $loggedMember->jabatan : 'Administrator Sistem';
        $creatorDept = $loggedMember ? $loggedMember->departemen : 'Admin';
        $creatorEmail = $loggedMember ? $loggedMember->email : ($loggedAdmin ? $loggedAdmin->email : 'admin@himati.id');
        $creatorInitials = $loggedMember ? $loggedMember->initials : 'AD';
        $creatorId = $loggedMember ? $loggedMember->id : ($allMembers->first()->id ?? 1);
    @endphp

    <!-- Info Status Login Terverifikasi -->
    <div class="creator-top-bar">
        @if ($loggedMember)
            <a href="{{ route('portal.dashboard') }}" class="btn btn-sm btn-secondary" style="margin-right: auto; gap: 6px; font-size: 0.82rem; padding: 6px 14px; border-radius: 9999px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Portal
            </a>
        @elseif ($loggedAdmin)
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-secondary" style="margin-right: auto; gap: 6px; font-size: 0.82rem; padding: 6px 14px; border-radius: 9999px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        @endif
        <div class="creator-top-badge">
            <span class="status-dot"></span>
            <span>Akun Login Terverifikasi</span>
        </div>
    </div>

    <!-- Creator Header Banner (Sleek Compact Profile) -->
    <div class="creator-header-card">
        <div class="creator-avatar-wrapper">
            <div class="creator-avatar-bubble">
                {{ $creatorInitials }}
            </div>
            <span class="creator-verified-badge" title="Akun Terverifikasi">
                <i class="fas fa-check"></i>
            </span>
        </div>
        <div class="creator-header-info">
            <div class="creator-name-title" title="{{ $creatorName }}">{{ $creatorName }}</div>
            <div class="creator-chips-row">
                @if ($loggedMember)
                    <span class="creator-chip"><i class="fas fa-id-badge"></i> {{ $creatorRole }}</span>
                    <span class="creator-chip"><i class="fas fa-building"></i> {{ $creatorDept }}</span>
                    <span class="creator-chip"><i class="fas fa-envelope"></i> {{ $creatorEmail }}</span>
                @else
                    <span class="creator-chip"><i class="fas fa-shield-alt"></i> Administrator</span>
                    <span class="creator-chip"><i class="fas fa-envelope"></i> {{ $creatorEmail }}</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Progress Steps (4 Langkah Langsung ke Inti Rapat) -->
    <div class="steps-progress">
        <div class="step-item active" id="stepItem1">
            <div class="step-circle">1</div>
            <span class="step-label">Jenis</span>
        </div>
        <div class="step-connector" id="connector1"></div>
        <div class="step-item" id="stepItem2">
            <div class="step-circle">2</div>
            <span class="step-label">Detail</span>
        </div>
        <div class="step-connector" id="connector2"></div>
        <div class="step-item" id="stepItem3">
            <div class="step-circle">3</div>
            <span class="step-label">Mode</span>
        </div>
        <div class="step-connector" id="connector3"></div>
        <div class="step-item" id="stepItem4">
            <div class="step-circle">4</div>
            <span class="step-label">Konfirmasi</span>
        </div>
    </div>

    <form id="meetingForm" action="{{ route('public.meeting.store') }}" method="POST">
        @csrf
        <input type="hidden" name="jenis_rapat" id="jenisRapat" value="">
        <input type="hidden" name="mode_rapat" id="modeRapat" value="offline">

        @if ($loggedMember)
            <input type="hidden" name="created_by" id="selectCreator" value="{{ $loggedMember->id }}" data-name="{{ $loggedMember->nama_lengkap }}" data-dept="{{ $loggedMember->departemen }}" data-jabatan="{{ $loggedMember->jabatan }}">
        @elseif ($loggedAdmin)
            <!-- Admin dapat menentukan penanggung jawab spesifik jika diperlukan -->
            <div class="form-group mb-4" style="background: var(--bg-surface-subtle); padding: 12px 16px; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                <label class="form-label" for="selectCreator" style="font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">
                    <i class="fas fa-user-tie text-primary"></i> Pengurus Penanggung Jawab Rapat <span style="color: var(--danger);">*</span>
                </label>
                <select name="created_by" class="form-control" id="selectCreator" required style="font-size: 0.9rem;">
                    @foreach ($usersByDept as $dept => $users)
                        <optgroup label="Departemen {{ $dept }}">
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" data-dept="{{ $u->departemen }}" data-jabatan="{{ $u->jabatan }}">
                                    {{ $u->nama_lengkap }} — {{ $u->jabatan }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
        @else
            <input type="hidden" name="created_by" id="selectCreator" value="{{ $creatorId }}">
        @endif

        <!-- ========== STEP 1: Pilih Jenis Rapat ========== -->
        <div class="step-content active" id="step1">
            <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-secondary);">
                Pilih Jenis Rapat
            </h3>
            
            <div class="rapat-type-grid">
                <div class="rapat-type-card" data-jenis="internal_dept" onclick="selectRapatType('internal_dept', this)">
                    <div class="type-icon icon-dept"><i class="fas fa-building"></i></div>
                    <div>
                        <div class="type-name">Internal Departemen & BPH</div>
                        <div class="type-desc">Rapat per departemen / BPH</div>
                    </div>
                </div>
                <div class="rapat-type-card" data-jenis="perdana" onclick="selectRapatType('perdana', this)">
                    <div class="type-icon icon-perdana"><i class="fas fa-flag"></i></div>
                    <div>
                        <div class="type-name">Rapat Perdana</div>
                        <div class="type-desc">Rapat seluruh anggota</div>
                    </div>
                </div>
                <div class="rapat-type-card" data-jenis="kepanitiaan" onclick="selectRapatType('kepanitiaan', this)">
                    <div class="type-icon icon-event"><i class="fas fa-calendar-check"></i></div>
                    <div>
                        <div class="type-name">Kepanitiaan / Event</div>
                        <div class="type-desc">Rapat event & kepanitiaan</div>
                    </div>
                </div>
                <div class="rapat-type-card" data-jenis="lintas_dept" onclick="selectRapatType('lintas_dept', this)">
                    <div class="type-icon icon-lintas"><i class="fas fa-people-arrows"></i></div>
                    <div>
                        <div class="type-name">Lintas Departemen</div>
                        <div class="type-desc">Kolaborasi antar departemen</div>
                    </div>
                </div>
            </div>
            
            <div id="step1Error" class="form-error text-center mt-4" style="display: none;">
                Silakan pilih jenis rapat terlebih dahulu
            </div>
            
            <div class="step-nav-actions right-only">
                <button type="button" class="btn btn-primary" onclick="nextStep(1)">
                    Lanjut <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ========== STEP 2: Detail Rapat ========== -->
        <div class="step-content" id="step2">
            <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-secondary);">
                Detail Rapat
            </h3>
            
            <!-- Internal Departemen -->
            <div id="fields_internal_dept" class="dynamic-fields" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Departemen <span class="required">*</span></label>
                    <select name="departemen" class="form-control" id="selectDepartemen">
                        <option value="">— Pilih Departemen —</option>
                        @foreach ($departments as $key => $label)
                            <option value="{{ $key }}" {{ ($loggedMember && strtolower($loggedMember->departemen) === strtolower($key)) ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fokus Pembahasan <span class="required">*</span></label>
                    <textarea name="fokus_bahasan_internal" class="form-control" placeholder="Tuliskan fokus pembahasan rapat..." rows="3"></textarea>
                </div>
            </div>
            
            <!-- Rapat Perdana -->
            <div id="fields_perdana" class="dynamic-fields" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Nama / Agenda Rapat <span class="required">*</span></label>
                    <input type="text" name="nama_event_perdana" class="form-control" placeholder="Contoh: Rapat Perdana Kabinet">
                </div>
            </div>
            
            <!-- Kepanitiaan -->
            <div id="fields_kepanitiaan" class="dynamic-fields" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Pilih Event <span class="required">*</span></label>
                    <select name="event_id" id="selectEvent" class="form-control" onchange="fetchEventDivisions(this.value)">
                        <option value="">— Pilih Event Aktif —</option>
                        @foreach ($events as $evt)
                            <option value="{{ $evt->id }}">{{ $evt->nama_event }}</option>
                        @endforeach
                    </select>
                    <small style="color: var(--text-muted); display: block; margin-top: 4px;" id="eventMemberCount">
                        Pilih event untuk melihat divisi dan panitia.
                    </small>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe Rapat <span class="required">*</span></label>
                    <div class="segmented-control">
                        <label class="segmented-option">
                            <input type="radio" name="tipe_divisi" value="semua" onchange="toggleDivisiField()" checked>
                            <span class="segmented-btn">
                                <i class="fas fa-users"></i>
                                <span>Semua Divisi</span>
                            </span>
                        </label>
                        <label class="segmented-option">
                            <input type="radio" name="tipe_divisi" value="perdivisi" onchange="toggleDivisiField()">
                            <span class="segmented-btn">
                                <i class="fas fa-layer-group"></i>
                                <span>Per Divisi</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="form-group" id="fieldDivisiName" style="display: none;">
                    <label class="form-label">Pilih Divisi <span class="required">*</span></label>
                    <select name="divisi_id" id="selectDivisi" class="form-control" disabled>
                        <option value="">— Pilih Event Dulu —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Agenda Rapat <span class="required">*</span></label>
                    <textarea name="fokus_bahasan_kepanitiaan" class="form-control" placeholder="Tuliskan agenda rapat..." rows="3"></textarea>
                </div>
            </div>
            
            <!-- Lintas Departemen -->
            <div id="fields_lintas_dept" class="dynamic-fields" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Departemen yang Berkolaborasi <span class="required">*</span></label>
                    <div class="checkbox-group">
                        @foreach ($departments as $key => $label)
                            <label class="form-check">
                                <input type="checkbox" name="departemen_collab[]" value="{{ $key }}">
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Agenda / Bahasan <span class="required">*</span></label>
                    <textarea name="fokus_bahasan_lintas" class="form-control" placeholder="Tuliskan agenda rapat lintas departemen..." rows="3"></textarea>
                </div>
            </div>
            
            <!-- Common: Tanggal & Waktu -->
            <div class="meeting-datetime-grid">
                <div class="form-group">
                    <label class="form-label">Tanggal <span class="required">*</span></label>
                    <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Waktu Mulai <span class="required">*</span></label>
                    <input type="time" name="waktu_mulai" class="form-control" value="{{ date('H:i') }}" required>
                </div>
            </div>
            
            <div class="step-nav-actions">
                <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                    Lanjut <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ========== STEP 3: Mode Rapat ========== -->
        <div class="step-content" id="step3">
            <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-primary); font-weight: 700;">
                <i class="fas fa-broadcast-tower text-primary" style="margin-right: 8px;"></i>Pilih Mode Rapat
            </h3>
            
            <div class="mode-selection">
                <div class="mode-card selected" data-mode="offline" onclick="selectMode('offline', this)">
                    <div class="mode-icon"><i class="fas fa-qrcode"></i></div>
                    <div class="mode-title">Rapat Offline</div>
                    <div class="mode-desc">Presensi tatap muka langsung menggunakan Scanner Kamera QR Code</div>
                </div>
                <div class="mode-card" data-mode="online" onclick="selectMode('online', this)">
                    <div class="mode-icon"><i class="fas fa-globe"></i></div>
                    <div class="mode-title">Rapat Online</div>
                    <div class="mode-desc">Presensi mandiri jarak jauh via tautan absensi khusus anggota</div>
                </div>
            </div>
            
            <div class="step-nav-actions">
                <button type="button" class="btn btn-secondary" onclick="prevStep(3)">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                    Lanjut <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ========== STEP 4: Konfirmasi ========== -->
        <div class="step-content" id="step4">
            <div style="text-align: center; margin-bottom: var(--space-6);">
                <div style="width: 48px; height: 48px; background: rgba(37,99,235,0.08); color: var(--blue-primary); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.35rem; margin-bottom: 10px;">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h3 style="margin-bottom: 4px; color: var(--text-primary); font-weight: 700; font-size: 1.25rem;">
                    Konfirmasi Data Rapat
                </h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                    Periksa kembali data rapat sebelum disimpan dan disebarkan ke peserta
                </p>
            </div>
            
            <div id="confirmGrid" style="margin-bottom: var(--space-6);">
                <!-- Populated dynamically by public-meeting.js -->
            </div>
            
            <div class="step-nav-actions">
                <button type="button" class="btn btn-secondary" onclick="prevStep(4)">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button type="submit" class="btn btn-primary" id="btnSubmit">
                    <i class="fas fa-check-circle"></i> Buat Rapat Sekarang
                </button>
            </div>
        </div>
    </form>
    <script>
        window.JENIS_RAPAT_LABELS = {!! json_encode(config('himati.meeting_types', [])) !!};
        window.DEPARTEMEN_LABELS = {!! json_encode(config('himati.departments', [])) !!};
    </script>
    <script src="{{ asset('assets/js/public-meeting.js') }}?v={{ file_exists(public_path('assets/js/public-meeting.js')) ? filemtime(public_path('assets/js/public-meeting.js')) : time() }}"></script>
</div>
@endsection
