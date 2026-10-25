@extends('layouts.public')

@section('title', 'Buat Rapat Baru')
@section('page_heading', config('himati.app_name'))
@section('page_subheading', 'Form Publik — Buat Jadwal & Absensi Rapat')
@section('container_width', '700px')

@section('content')
<div class="public-card">
    <!-- Progress Steps -->
    <div class="steps-progress">
        <div class="step-item active" id="stepItem1">
            <div class="step-circle">1</div>
            <span class="step-label">Pembuat</span>
        </div>
        <div class="step-connector" id="connector1"></div>
        <div class="step-item" id="stepItem2">
            <div class="step-circle">2</div>
            <span class="step-label">Jenis</span>
        </div>
        <div class="step-connector" id="connector2"></div>
        <div class="step-item" id="stepItem3">
            <div class="step-circle">3</div>
            <span class="step-label">Detail</span>
        </div>
        <div class="step-connector" id="connector3"></div>
        <div class="step-item" id="stepItem4">
            <div class="step-circle">4</div>
            <span class="step-label">Mode</span>
        </div>
        <div class="step-connector" id="connector4"></div>
        <div class="step-item" id="stepItem5">
            <div class="step-circle">5</div>
            <span class="step-label">Konfirmasi</span>
        </div>
    </div>

    <form id="meetingForm" action="{{ route('public.meeting.store') }}" method="POST">
        @csrf
        <input type="hidden" name="jenis_rapat" id="jenisRapat" value="">
        <input type="hidden" name="mode_rapat" id="modeRapat" value="offline">

        <!-- ========== STEP 1: Siapa Pembuat ========== -->
        <div class="step-content active" id="step1">
            <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-secondary);">
                <i class="fas fa-user"></i> Siapa yang membuat rapat?
            </h3>
            
            <div class="form-group">
                <label class="form-label" for="selectCreator">Pilih Nama Anda <span class="required">*</span></label>
                <select name="created_by" class="form-control" id="selectCreator" required>
                    <option value="">— Pilih Nama Anda —</option>
                    @foreach ($usersByDept as $dept => $users)
                        <optgroup label="{{ $dept }}">
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" data-dept="{{ $u->departemen }}" data-jabatan="{{ $u->jabatan }}">
                                    {{ $u->nama_lengkap }} — {{ $u->jabatan }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            
            <div id="creatorInfo" style="display: none; margin-top: var(--space-4);">
                <div class="alert alert-info">
                    <i class="fas fa-user-check"></i>
                    <div>
                        <strong id="creatorName"></strong><br>
                        <small id="creatorDetail"></small>
                    </div>
                </div>
            </div>
            
            <div id="step1Error" class="form-error text-center mt-4" style="display: none;">
                Silakan pilih nama Anda terlebih dahulu
            </div>
            
            <div style="text-align: right; margin-top: var(--space-6);">
                <button type="button" class="btn btn-primary" onclick="nextStep(1)">
                    Lanjut <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ========== STEP 2: Pilih Jenis Rapat ========== -->
        <div class="step-content" id="step2">
            <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-secondary);">
                Pilih Jenis Rapat
            </h3>
            
            <div class="rapat-type-grid">
                <div class="rapat-type-card" onclick="selectRapatType('internal_dept', this)">
                    <div class="type-icon"><i class="fas fa-building"></i></div>
                    <div class="type-name">Internal Departemen</div>
                    <div class="type-desc">Rapat per departemen</div>
                </div>
                <div class="rapat-type-card" onclick="selectRapatType('perdana', this)">
                    <div class="type-icon" style="background: linear-gradient(135deg, #27AE60, #1E8449);"><i class="fas fa-flag"></i></div>
                    <div class="type-name">Rapat Perdana</div>
                    <div class="type-desc">Rapat seluruh anggota</div>
                </div>
                <div class="rapat-type-card" onclick="selectRapatType('kepanitiaan', this)">
                    <div class="type-icon" style="background: linear-gradient(135deg, #3498DB, #2471A3);"><i class="fas fa-calendar-star"></i></div>
                    <div class="type-name">Kepanitiaan / Even</div>
                    <div class="type-desc">Rapat event & kepanitiaan</div>
                </div>
                <div class="rapat-type-card" onclick="selectRapatType('bph', this)">
                    <div class="type-icon" style="background: linear-gradient(135deg, #9B59B6, #7D3C98);"><i class="fas fa-crown"></i></div>
                    <div class="type-name">BPH / Inti</div>
                    <div class="type-desc">Badan Pengurus Harian</div>
                </div>
                <div class="rapat-type-card" onclick="selectRapatType('lintas_dept', this)">
                    <div class="type-icon" style="background: linear-gradient(135deg, #E74C3C, #C0392B);"><i class="fas fa-exchange-alt"></i></div>
                    <div class="type-name">Lintas Departemen</div>
                    <div class="type-desc">Kolaborasi antar dept</div>
                </div>
            </div>
            
            <div id="step2Error" class="form-error text-center mt-4" style="display: none;">
                Silakan pilih jenis rapat terlebih dahulu
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-top: var(--space-6);">
                <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                    Lanjut <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ========== STEP 3: Detail Rapat ========== -->
        <div class="step-content" id="step3">
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
                            <option value="{{ $key }}">{{ $label }}</option>
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
                    <label class="form-label">Nama Event <span class="required">*</span></label>
                    <input type="text" name="nama_event" class="form-control" placeholder="Contoh: Seminar Nasional IT">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe Rapat <span class="required">*</span></label>
                    <div style="display: flex; gap: var(--space-4);">
                        <label class="form-check">
                            <input type="radio" name="tipe_divisi" value="perdivisi" onchange="toggleDivisiField()">
                            <span class="form-check-label">Per Divisi</span>
                        </label>
                        <label class="form-check">
                            <input type="radio" name="tipe_divisi" value="semua" onchange="toggleDivisiField()" checked>
                            <span class="form-check-label">Semua Divisi</span>
                        </label>
                    </div>
                </div>
                <div class="form-group" id="fieldDivisiName" style="display: none;">
                    <label class="form-label">Nama Divisi <span class="required">*</span></label>
                    <input type="text" name="divisi_rapat" class="form-control" placeholder="Contoh: Divisi Acara">
                </div>
                <div class="form-group">
                    <label class="form-label">Agenda Rapat <span class="required">*</span></label>
                    <textarea name="fokus_bahasan_kepanitiaan" class="form-control" placeholder="Tuliskan agenda rapat..." rows="3"></textarea>
                </div>
            </div>
            
            <!-- BPH / Inti -->
            <div id="fields_bph" class="dynamic-fields" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Fokus Bahasan <span class="required">*</span></label>
                    <textarea name="fokus_bahasan_bph" class="form-control" placeholder="Tuliskan fokus bahasan rapat BPH..." rows="3"></textarea>
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
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); margin-top: var(--space-4);">
                <div class="form-group">
                    <label class="form-label">Tanggal <span class="required">*</span></label>
                    <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Waktu Mulai <span class="required">*</span></label>
                    <input type="time" name="waktu_mulai" class="form-control" value="{{ date('H:i') }}" required>
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-top: var(--space-6);">
                <button type="button" class="btn btn-secondary" onclick="prevStep(3)">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                    Lanjut <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ========== STEP 4: Mode Rapat ========== -->
        <div class="step-content" id="step4">
            <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-primary); font-weight: 700;">
                <i class="fas fa-broadcast-tower" style="color: var(--blue-primary); margin-right: 8px;"></i>Pilih Mode Rapat
            </h3>
            
            <div class="mode-selection">
                <div class="mode-card rapat-type-card selected active" onclick="selectMode('offline', this)">
                    <div class="mode-icon"><i class="fas fa-qrcode"></i></div>
                    <div class="mode-title">Rapat Offline</div>
                    <div class="mode-desc">Presensi tatap muka langsung menggunakan Scanner Kamera QR Code</div>
                </div>
                <div class="mode-card rapat-type-card" onclick="selectMode('online', this)">
                    <div class="mode-icon"><i class="fas fa-globe"></i></div>
                    <div class="mode-title">Rapat Online</div>
                    <div class="mode-desc">Presensi mandiri jarak jauh via tautan absensi khusus anggota</div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-top: var(--space-6);">
                <button type="button" class="btn btn-secondary" onclick="prevStep(4)">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button type="button" class="btn btn-primary" onclick="nextStep(4)">
                    Lanjut <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ========== STEP 5: Konfirmasi ========== -->
        <div class="step-content" id="step5">
            <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-primary); font-weight: 700;">
                <i class="fas fa-clipboard-check" style="color: var(--blue-primary); margin-right: 8px;"></i>Konfirmasi Data Rapat
            </h3>
            
            <div class="card" style="background: var(--bg-surface-subtle); border: 1.5px solid var(--border-color); margin-bottom: var(--space-6); padding: var(--space-5);" id="summaryCard">
                <div class="meeting-info-grid">
                    <div class="meeting-info-item">
                        <label><i class="fas fa-user"></i> Pembuat Rapat</label>
                        <p id="confirmCreator">-</p>
                    </div>
                    <div class="meeting-info-item">
                        <label><i class="fas fa-tag"></i> Jenis Rapat</label>
                        <p id="confirmJenis">-</p>
                    </div>
                    <div class="meeting-info-item">
                        <label><i class="fas fa-broadcast-tower"></i> Mode Rapat</label>
                        <p id="confirmMode">Offline</p>
                    </div>
                    <div class="meeting-info-item">
                        <label><i class="fas fa-clock"></i> Tanggal & Waktu</label>
                        <p id="confirmTanggal">-</p>
                    </div>
                </div>
                <div id="confirmDetails" style="margin-top: var(--space-4);"></div>
            </div>
            
            <div style="display: flex; justify-content: space-between;">
                <button type="button" class="btn btn-secondary" onclick="prevStep(5)">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit">
                    <i class="fas fa-check-circle"></i> Buat Rapat Sekarang
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const JENIS_RAPAT_LABELS = {!! json_encode(config('himati.meeting_types', [])) !!};
    const DEPARTEMEN_LABELS = {!! json_encode(config('himati.departments', [])) !!};
</script>
<script src="{{ asset('assets/js/public-meeting.js') }}"></script>
@endpush
