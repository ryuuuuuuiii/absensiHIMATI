@extends('layouts.app')

@section('title', 'Buat Rapat Baru')

@section('page_icon')
    <i class="fas fa-plus-circle"></i>
@endsection

@section('content')
<div style="max-width: 700px; margin: 0 auto;">
    <div class="card">
        <!-- Progress Steps -->
        <div class="steps-progress" id="stepsProgress">
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
        
        <form id="meetingForm" action="{{ route('meetings.store') }}" method="POST">
            @csrf
            <input type="hidden" name="jenis_rapat" id="jenisRapat" value="">
            <input type="hidden" name="mode_rapat" id="modeRapat" value="offline">
            
            <!-- Creator Selection -->
            <div class="form-group mb-6" style="background: var(--dark-surface-2); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--dark-border);">
                <label class="form-label" for="created_by" style="font-weight: 600;">
                    <i class="fas fa-user-tie text-primary"></i> Penanggung Jawab / Pembuat Rapat <span style="color: var(--danger);">*</span>
                </label>
                <select name="created_by" id="created_by" class="form-control" required>
                    <option value="">— Pilih Pengurus Pembuat Rapat —</option>
                    @foreach($membersByDept as $deptName => $membersList)
                        <optgroup label="Departemen {{ $deptName }}">
                            @foreach($membersList as $u)
                                <option value="{{ $u->id }}" {{ old('created_by') == $u->id ? 'selected' : '' }}>
                                    {{ $u->nama_lengkap }} ({{ $u->jabatan }})
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <!-- ========== STEP 1: Pilih Jenis Rapat ========== -->
            <div class="step-content active" id="step1">
                <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-secondary);">
                    Pilih Jenis Rapat
                </h3>
                
                <div class="rapat-type-grid">
                    <div class="rapat-type-card" onclick="selectRapatType('internal_dept', this)">
                        <div class="type-icon icon-dept"><i class="fas fa-building"></i></div>
                        <div>
                            <div class="type-name">Internal Departemen & BPH</div>
                            <div class="type-desc">Rapat per departemen / BPH</div>
                        </div>
                    </div>
                    
                    <div class="rapat-type-card" onclick="selectRapatType('perdana', this)">
                        <div class="type-icon icon-perdana"><i class="fas fa-flag"></i></div>
                        <div>
                            <div class="type-name">Rapat Perdana</div>
                            <div class="type-desc">Rapat seluruh anggota</div>
                        </div>
                    </div>
                    
                    <div class="rapat-type-card" onclick="selectRapatType('kepanitiaan', this)">
                        <div class="type-icon icon-event"><i class="fas fa-calendar-check"></i></div>
                        <div>
                            <div class="type-name">Kepanitiaan / Even</div>
                            <div class="type-desc">Rapat event & kepanitiaan</div>
                        </div>
                    </div>
                    
                    <div class="rapat-type-card" onclick="selectRapatType('lintas_dept', this)">
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
                
                <div style="text-align: right; margin-top: var(--space-6);">
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
                        <input type="text" name="nama_event_perdana" class="form-control" placeholder="Contoh: Rapat Perdana Semester Genap">
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
                            Pilih event untuk melihat jumlah panitia.
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
                        <label class="form-label">Departemen yang Berkolaborasi (Pilih min. 2) <span class="required">*</span></label>
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
                        <label class="form-label">Agenda / Bahasan Rapat <span class="required">*</span></label>
                        <textarea name="fokus_bahasan_lintas" class="form-control" placeholder="Tuliskan agenda pembahasan kolaborasi..." rows="3"></textarea>
                    </div>
                </div>
                
                <!-- Tanggal & Waktu -->
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
                
                <div style="display: flex; justify-content: space-between; margin-top: var(--space-6);">
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
                <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-secondary);">
                    Pilih Mode Rapat
                </h3>
                
                <div class="mode-selection">
                    <div class="mode-card selected" onclick="selectMode('offline', this)">
                        <div class="mode-icon"><i class="fas fa-users"></i></div>
                        <div class="mode-title">Rapat Offline</div>
                        <div class="mode-desc">Absensi tatap muka langsung menggunakan Scanner Kamera QR Code</div>
                    </div>
                    <div class="mode-card" onclick="selectMode('online', this)">
                        <div class="mode-icon"><i class="fas fa-laptop"></i></div>
                        <div class="mode-title">Rapat Online</div>
                        <div class="mode-desc">Absensi jarak jauh via link khusus yang bisa diakses anggota</div>
                    </div>
                </div>

                <!-- Online Meeting Expiry Setting (Fleksibel Sesuai Pembuat Rapat) -->
                <div id="onlineExpiryCard" style="display: none; margin-top: var(--space-5); padding: var(--space-4); border: 1.5px solid rgba(59, 130, 246, 0.35); background: rgba(59, 130, 246, 0.05); border-radius: var(--radius-lg);">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="jamBerakhirOnline" style="font-weight: 700; color: var(--text-primary); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                            <span><i class="fas fa-clock text-info"></i> Jam Berakhir Absensi Online <span class="required">*</span></span>
                            <span class="badge badge-info" id="expiryDurationBadge" style="font-size: 0.75rem;"><i class="fas fa-stopwatch"></i> Durasi: Fleksibel</span>
                        </label>
                        <div style="display: flex; gap: var(--space-3); align-items: center; flex-wrap: wrap; margin-top: var(--space-2);">
                            <input type="time" name="waktu_selesai" id="jamBerakhirOnline" class="form-control" style="max-width: 180px;" onchange="updateExpiryBadge()">
                            <div class="btn-group" style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setExpiryOffset(1)">+1 Jam</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setExpiryOffset(2)">+2 Jam</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setExpiryOffset(3)">+3 Jam (Standar)</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setExpiryOffset(5)">+5 Jam</button>
                            </div>
                        </div>
                        <small style="color: var(--text-muted); display: block; margin-top: 6px;">
                            Link absensi online akan aktif sejak waktu mulai dan otomatis ditutup pada jam berakhir ini. Anda bebas menentukan durasi sesuai kebutuhan rapat.
                        </small>
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
                        Periksa kembali data rapat sebelum disimpan dan dimulai
                    </p>
                </div>
                
                <div id="confirmGrid" style="margin-bottom: var(--space-6);">
                    <!-- Populated dynamically by meeting-form.js -->
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(4)">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button type="submit" class="btn btn-success btn-lg" id="btnSubmitMeeting">
                        <i class="fas fa-check-circle"></i> Mulai Absensi Sekarang
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/meeting-form.js') }}?v={{ filemtime(public_path('assets/js/meeting-form.js')) }}"></script>
@endpush
