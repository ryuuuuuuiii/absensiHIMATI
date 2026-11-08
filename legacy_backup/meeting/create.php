<?php
/**
 * Create Meeting — Multi-Step Form
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__.'/../includes/auth_check.php';

$pageTitle = 'Buat Rapat Baru';
$pageIcon = 'fas fa-plus-circle';
$currentUser = getCurrentUser();
$db = getDB();

// Get all departments
$departemenList = json_decode(DEPARTEMEN_LIST, true);

// Get all users for participant selection
$stmt = $db->query('SELECT id, nama_lengkap, departemen, jabatan, is_bph FROM users ORDER BY departemen, nama_lengkap');
$allUsers = $stmt->fetchAll();

// Group users by department for the participant selector (masih dipakai untuk default/lintas)
$usersByDept = [];
foreach ($allUsers as $user) {
    $usersByDept[$user['departemen']][] = $user;
}

// Get active events for kepanitiaan
$stmtEvents = $db->query("SELECT id, nama_event FROM events WHERE status = 'active' ORDER BY nama_event");
$activeEvents = $stmtEvents->fetchAll();

$extraJS = [
    BASE_URL.'/assets/js/meeting-form.js',
];

include __DIR__.'/../includes/header.php';
?>

<div style="max-width: 700px; margin: 0 auto;">
    <div class="card">
        <!-- Progress Steps (dynamic, managed by JS) -->
        <div class="steps-progress" id="stepsProgress">
            <div class="step-item active" id="stepItem1">
                <div class="step-circle">1</div>
                <span class="step-label">Jenis Rapat</span>
            </div>
            <div class="step-connector" id="connector1"></div>
            <div class="step-item" id="stepItem2">
                <div class="step-circle">2</div>
                <span class="step-label">Detail</span>
            </div>
            <div class="step-connector" id="connector2"></div>
            <!-- Step Mode -->
            <div class="step-item" id="stepItem3">
                <div class="step-circle">3</div>
                <span class="step-label">Mode</span>
            </div>
            <div class="step-connector" id="connector3"></div>
            <!-- Step Konfirmasi -->
            <div class="step-item" id="stepItem4">
                <div class="step-circle">4</div>
                <span class="step-label">Konfirmasi</span>
            </div>
        </div>
        
        <form id="meetingForm" action="<?= BASE_URL ?>/meeting/process/create_meeting.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="jenis_rapat" id="jenisRapat" value="">
            <input type="hidden" name="mode_rapat" id="modeRapat" value="offline">
            
            <!-- ========== STEP 1: Pilih Jenis Rapat ========== -->
            <div class="step-content active" id="step1">
                <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-secondary);">
                    Pilih Jenis Rapat
                </h3>
                
                <div class="rapat-type-grid">
                    <!-- Internal Departemen -->
                    <div class="rapat-type-card" onclick="selectRapatType('internal_dept', this)">
                        <div class="type-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="type-name">Internal Departemen</div>
                        <div class="type-desc">Rapat per departemen</div>
                    </div>
                    
                    <!-- Rapat Perdana -->
                    <div class="rapat-type-card" onclick="selectRapatType('perdana', this)">
                        <div class="type-icon" style="background: linear-gradient(135deg, #27AE60, #1E8449);">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div class="type-name">Rapat Perdana</div>
                        <div class="type-desc">Rapat seluruh anggota</div>
                    </div>
                    
                    <!-- Kepanitiaan / Even -->
                    <div class="rapat-type-card" onclick="selectRapatType('kepanitiaan', this)">
                        <div class="type-icon" style="background: linear-gradient(135deg, #3498DB, #2471A3);">
                            <i class="fas fa-calendar-star"></i>
                        </div>
                        <div class="type-name">Kepanitiaan / Even</div>
                        <div class="type-desc">Rapat event & kepanitiaan</div>
                    </div>
                    
                    <!-- BPH / Inti -->
                    <div class="rapat-type-card" onclick="selectRapatType('bph', this)">
                        <div class="type-icon" style="background: linear-gradient(135deg, #9B59B6, #7D3C98);">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="type-name">BPH / Inti</div>
                        <div class="type-desc">Badan Pengurus Harian</div>
                    </div>
                    
                    <!-- Lintas Departemen -->
                    <div class="rapat-type-card" onclick="selectRapatType('lintas_dept', this)">
                        <div class="type-icon" style="background: linear-gradient(135deg, #E74C3C, #C0392B);">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="type-name">Lintas Departemen</div>
                        <div class="type-desc">Kolaborasi antar dept</div>
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
                
                <!-- Fields for Internal Departemen -->
                <div id="fields_internal_dept" class="dynamic-fields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Departemen <span class="required">*</span></label>
                        <select name="departemen" class="form-control" id="selectDepartemen">
                            <option value="">— Pilih Departemen —</option>
                            <?php foreach ($departemenList as $key => $label) { ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fokus Pembahasan <span class="required">*</span></label>
                        <textarea name="fokus_bahasan_internal" class="form-control" placeholder="Tuliskan fokus pembahasan rapat..." rows="3"></textarea>
                    </div>
                </div>
                
                <!-- Fields for Rapat Perdana -->
                <div id="fields_perdana" class="dynamic-fields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Nama / Agenda Rapat <span class="required">*</span></label>
                        <input type="text" name="nama_event_perdana" class="form-control" placeholder="Contoh: Rapat Perdana Kabinet 2026">
                    </div>
                </div>
                
                <!-- Fields for Kepanitiaan / Even -->
                <div id="fields_kepanitiaan" class="dynamic-fields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Pilih Event <span class="required">*</span></label>
                        <select name="event_id" id="selectEvent" class="form-control" onchange="fetchEventDivisions(this.value)">
                            <option value="">— Pilih Event Aktif —</option>
                            <?php foreach ($activeEvents as $evt) { ?>
                                <option value="<?= $evt['id'] ?>"><?= htmlspecialchars($evt['nama_event']) ?></option>
                            <?php } ?>
                        </select>
                        <small style="color: var(--text-muted); display: block; margin-top: 4px;" id="eventMemberCount">
                            Pilih event untuk melihat jumlah panitia.
                        </small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe Rapat <span class="required">*</span></label>
                        <div style="display: flex; gap: var(--space-4);">
                            <label class="form-check">
                                <input type="radio" name="tipe_divisi" value="semua" onchange="toggleDivisiField()" checked>
                                <span class="form-check-label">Semua Divisi</span>
                            </label>
                            <label class="form-check">
                                <input type="radio" name="tipe_divisi" value="perdivisi" onchange="toggleDivisiField()">
                                <span class="form-check-label">Per Divisi</span>
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
                
                <!-- Fields for BPH / Inti -->
                <div id="fields_bph" class="dynamic-fields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Fokus Bahasan <span class="required">*</span></label>
                        <textarea name="fokus_bahasan_bph" class="form-control" placeholder="Tuliskan fokus bahasan rapat BPH..." rows="3"></textarea>
                    </div>
                </div>
                
                <!-- Fields for Lintas Departemen -->
                <div id="fields_lintas_dept" class="dynamic-fields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Departemen yang Berkolaborasi <span class="required">*</span></label>
                        <div class="checkbox-group">
                            <?php foreach ($departemenList as $key => $label) { ?>
                                <label class="form-check">
                                    <input type="checkbox" name="departemen_collab[]" value="<?= $key ?>">
                                    <span class="form-check-label"><?= $label ?></span>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Agenda / Bahasan Rapat <span class="required">*</span></label>
                        <textarea name="fokus_bahasan_lintas" class="form-control" placeholder="Tuliskan agenda atau bahasan rapat lintas departemen..." rows="3"></textarea>
                    </div>
                </div>
                
                <!-- Common: Tanggal & Waktu -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); margin-top: var(--space-4);">
                    <div class="form-group">
                        <label class="form-label">Tanggal <span class="required">*</span></label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu Mulai <span class="required">*</span></label>
                        <input type="time" name="waktu_mulai" class="form-control" value="<?= date('H:i') ?>" required>
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
                    Mode Rapat
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div class="rapat-type-card selected" onclick="selectMode('offline', this)">
                        <div class="type-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div class="type-name">Offline</div>
                        <div class="type-desc">Scan QR Code peserta secara langsung</div>
                    </div>
                    
                    <div class="rapat-type-card" onclick="selectMode('online', this)">
                        <div class="type-icon" style="background: linear-gradient(135deg, #3498DB, #2471A3);">
                            <i class="fas fa-link"></i>
                        </div>
                        <div class="type-name">Online</div>
                        <div class="type-desc">Bagikan link absensi ke peserta</div>
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
                <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-secondary);">
                    Konfirmasi Rapat
                </h3>
                
                <div class="card" style="background: var(--dark-surface-2); margin-bottom: var(--space-6);">
                    <div class="meeting-info-grid">
                        <div class="meeting-info-item">
                            <label>Jenis Rapat</label>
                            <p id="confirmJenis">-</p>
                        </div>
                        <div class="meeting-info-item">
                            <label>Mode</label>
                            <p id="confirmMode">Offline</p>
                        </div>
                        <div class="meeting-info-item">
                            <label>Tanggal</label>
                            <p id="confirmTanggal">-</p>
                        </div>
                        <div class="meeting-info-item">
                            <label>Waktu</label>
                            <p id="confirmWaktu">-</p>
                        </div>
                    </div>
                    
                    <div id="confirmDetails" style="margin-top: var(--space-4);">
                        <!-- Dynamic details will be inserted here -->
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(4)">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </button>
                    <button type="submit" class="btn btn-success btn-lg" id="btnSubmit">
                        <i class="fas fa-rocket"></i> Mulai Absensi
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Pass data to JS -->
<script>
    const BASE_URL = '<?= BASE_URL ?>';
    const JENIS_RAPAT_LABELS = <?= JENIS_RAPAT ?>;
    const DEPARTEMEN_LABELS = <?= DEPARTEMEN_LIST ?>;
    const TOTAL_USERS = <?= count($allUsers) ?>;
    
    function formatDate(dateStr) {
        const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        const d = new Date(dateStr);
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>

