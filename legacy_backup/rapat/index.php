<?php
/**
 * Rapat — Public Landing / Form Buat Rapat
 * Halaman ini bisa diakses siapapun tanpa login
 * URL: /Absensi-HIMATI-new/rapat/
 */

require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/functions.php';

$db = getDB();

// Get logos
$logo1 = getSetting('logo_1', '');
$logo2 = getSetting('logo_2', '');
$logo3 = getSetting('logo_3', '');

// Get all departments
$departemenList = json_decode(DEPARTEMEN_LIST, true);

// Get all users for creator selection
$stmt = $db->query('SELECT id, nama_lengkap, departemen, jabatan, is_bph FROM users ORDER BY departemen, nama_lengkap');
$allUsers = $stmt->fetchAll();

// Group users by departemen
$usersByDept = [];
foreach ($allUsers as $u) {
    $usersByDept[$u['departemen']][] = $u;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Buat Rapat — Sistem Absensi HIMA TI Politeknik Hasnur">
    <title>Buat Rapat — <?= APP_FULL_NAME ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="bg-animated"></div>
    
    <div class="public-page">
        <div class="public-container" style="max-width: 700px;">
            
            <!-- Header with Logos -->
            <div class="public-card" style="margin-bottom: var(--space-6);">
                <div class="login-logos" style="margin-bottom: var(--space-4);">
                    <?php if ($logo1) { ?>
                        <img src="<?= BASE_URL ?>/uploads/logos/<?= $logo1 ?>" alt="Logo 1">
                    <?php } else { ?>
                        <span class="logo-placeholder"><i class="fas fa-university"></i></span>
                    <?php } ?>
                    <?php if ($logo2) { ?>
                        <img src="<?= BASE_URL ?>/uploads/logos/<?= $logo2 ?>" alt="Logo 2">
                    <?php } else { ?>
                        <span class="logo-placeholder"><i class="fas fa-laptop-code"></i></span>
                    <?php } ?>
                    <?php if ($logo3) { ?>
                        <img src="<?= BASE_URL ?>/uploads/logos/<?= $logo3 ?>" alt="Logo 3">
                    <?php } else { ?>
                        <span class="logo-placeholder"><i class="fas fa-users"></i></span>
                    <?php } ?>
                </div>
                <div class="login-title" style="margin-bottom: 0;">
                    <h1 style="font-size: var(--font-xl);"><?= APP_NAME ?></h1>
                    <p>Politeknik Hasnur — Buat Rapat Baru</p>
                </div>
            </div>

            <!-- Multi-Step Form -->
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

                <form id="meetingForm" action="<?= BASE_URL ?>/rapat/proses.php" method="POST">
                    <input type="hidden" name="jenis_rapat" id="jenisRapat" value="">
                    <input type="hidden" name="mode_rapat" id="modeRapat" value="offline">

                    <!-- ========== STEP 1: Siapa Pembuat ========== -->
                    <div class="step-content active" id="step1">
                        <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-user"></i> Siapa yang membuat rapat?
                        </h3>
                        
                        <div class="form-group">
                            <label class="form-label">Pilih Nama Anda <span class="required">*</span></label>
                            <select name="created_by" class="form-control" id="selectCreator" required>
                                <option value="">— Pilih Nama Anda —</option>
                                <?php foreach ($usersByDept as $dept => $users) { ?>
                                    <optgroup label="<?= $dept ?>">
                                        <?php foreach ($users as $u) { ?>
                                            <option value="<?= $u['id'] ?>" data-dept="<?= htmlspecialchars($u['departemen']) ?>" data-jabatan="<?= htmlspecialchars($u['jabatan']) ?>">
                                                <?= htmlspecialchars($u['nama_lengkap']) ?> — <?= $u['jabatan'] ?>
                                            </option>
                                        <?php } ?>
                                    </optgroup>
                                <?php } ?>
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
                                        <input type="radio" name="tipe_divisi" value="semua" onchange="toggleDivisiField()">
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
                                <label class="form-label">Agenda / Bahasan <span class="required">*</span></label>
                                <textarea name="fokus_bahasan_lintas" class="form-control" placeholder="Tuliskan agenda rapat lintas departemen..." rows="3"></textarea>
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
                            <button type="button" class="btn btn-secondary" onclick="prevStep(3)"><i class="fas fa-arrow-left"></i> Kembali</button>
                            <button type="button" class="btn btn-primary" onclick="nextStep(3)">Lanjut <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- ========== STEP 4: Mode Rapat ========== -->
                    <div class="step-content" id="step4">
                        <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-secondary);">
                            Mode Rapat
                        </h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                            <div class="rapat-type-card selected" onclick="selectMode('offline', this)">
                                <div class="type-icon"><i class="fas fa-qrcode"></i></div>
                                <div class="type-name">Offline</div>
                                <div class="type-desc">Scan QR Code peserta secara langsung</div>
                            </div>
                            <div class="rapat-type-card" onclick="selectMode('online', this)">
                                <div class="type-icon" style="background: linear-gradient(135deg, #3498DB, #2471A3);"><i class="fas fa-link"></i></div>
                                <div class="type-name">Online</div>
                                <div class="type-desc">Bagikan link absensi ke peserta</div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: var(--space-6);">
                            <button type="button" class="btn btn-secondary" onclick="prevStep(4)"><i class="fas fa-arrow-left"></i> Kembali</button>
                            <button type="button" class="btn btn-primary" onclick="nextStep(4)">Lanjut <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- ========== STEP 5: Konfirmasi ========== -->
                    <div class="step-content" id="step5">
                        <h3 style="margin-bottom: var(--space-6); text-align: center; color: var(--text-secondary);">
                            Konfirmasi Rapat
                        </h3>
                        <div class="card" style="background: var(--dark-surface-2); margin-bottom: var(--space-6);">
                            <div class="meeting-info-grid">
                                <div class="meeting-info-item">
                                    <label>Pembuat</label>
                                    <p id="confirmCreator">-</p>
                                </div>
                                <div class="meeting-info-item">
                                    <label>Jenis Rapat</label>
                                    <p id="confirmJenis">-</p>
                                </div>
                                <div class="meeting-info-item">
                                    <label>Mode</label>
                                    <p id="confirmMode">Offline</p>
                                </div>
                                <div class="meeting-info-item">
                                    <label>Tanggal & Waktu</label>
                                    <p id="confirmTanggal">-</p>
                                </div>
                            </div>
                            <div id="confirmDetails" style="margin-top: var(--space-4);"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <button type="button" class="btn btn-secondary" onclick="prevStep(5)"><i class="fas fa-arrow-left"></i> Kembali</button>
                            <button type="submit" class="btn btn-success btn-lg" id="btnSubmit">
                                <i class="fas fa-rocket"></i> Mulai Absensi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Footer -->
            <div style="text-align: center; margin-top: var(--space-6);">
                <a href="<?= BASE_URL ?>/auth/login.php" style="color: var(--text-muted); font-size: var(--font-xs); text-decoration: none;">
                    <i class="fas fa-lock"></i> Login Admin
                </a>
                <p style="font-size: var(--font-xs); color: var(--text-muted); margin-top: var(--space-2);">
                    &copy; <?= date('Y') ?> <?= APP_FULL_NAME ?>
                </p>
            </div>
        </div>
    </div>
    
    <div id="toastContainer" class="toast-container"></div>

    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    <script>
        const JENIS_RAPAT_LABELS = <?= JENIS_RAPAT ?>;
        const DEPARTEMEN_LABELS = <?= DEPARTEMEN_LIST ?>;
    </script>
    <script src="<?= BASE_URL ?>/assets/js/public-meeting.js"></script>
</body>
</html>
