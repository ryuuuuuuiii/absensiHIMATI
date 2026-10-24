/**
 * Public Meeting Form — Multi-Step Controller
 * Absensi HIMA TI — Halaman Publik (tanpa login)
 */

let currentStep = 1;
let selectedJenis = '';
let selectedMode = 'offline';
const totalSteps = 5;

// ============================================
// CREATOR SELECTION
// ============================================
document.getElementById('selectCreator')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const infoDiv = document.getElementById('creatorInfo');
    
    if (this.value) {
        document.getElementById('creatorName').textContent = opt.text.split(' — ')[0];
        document.getElementById('creatorDetail').textContent = opt.dataset.dept + ' • ' + opt.dataset.jabatan;
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
});

// ============================================
// STEP NAVIGATION
// ============================================
function nextStep(from) {
    if (!validateStep(from)) return;
    
    const next = from + 1;
    if (next > totalSteps) return;
    
    document.getElementById('step' + from).classList.remove('active');
    document.getElementById('step' + next).classList.add('active');
    
    document.getElementById('stepItem' + from).classList.remove('active');
    document.getElementById('stepItem' + from).classList.add('completed');
    document.getElementById('stepItem' + next).classList.add('active');
    
    if (document.getElementById('connector' + from)) {
        document.getElementById('connector' + from).classList.add('completed');
    }
    
    if (next === totalSteps) populateConfirmation();
    
    currentStep = next;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prevStep(from) {
    const prev = from - 1;
    if (prev < 1) return;
    
    document.getElementById('step' + from).classList.remove('active');
    document.getElementById('step' + prev).classList.add('active');
    
    document.getElementById('stepItem' + from).classList.remove('active');
    document.getElementById('stepItem' + prev).classList.remove('completed');
    document.getElementById('stepItem' + prev).classList.add('active');
    
    if (document.getElementById('connector' + prev)) {
        document.getElementById('connector' + prev).classList.remove('completed');
    }
    
    currentStep = prev;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============================================
// VALIDATION
// ============================================
function validateStep(step) {
    switch(step) {
        case 1:
            if (!document.getElementById('selectCreator').value) {
                document.getElementById('step1Error').style.display = 'block';
                return false;
            }
            document.getElementById('step1Error').style.display = 'none';
            return true;
            
        case 2:
            if (!selectedJenis) {
                document.getElementById('step2Error').style.display = 'block';
                return false;
            }
            document.getElementById('step2Error').style.display = 'none';
            return true;
            
        case 3:
            return validateStep3();
            
        case 4:
            return true;
            
        default:
            return true;
    }
}

function validateStep3() {
    switch(selectedJenis) {
        case 'internal_dept':
            if (!document.getElementById('selectDepartemen').value) {
                showToast('warning', 'Peringatan', 'Silakan pilih departemen.');
                return false;
            }
            if (!document.querySelector('[name="fokus_bahasan_internal"]').value.trim()) {
                showToast('warning', 'Peringatan', 'Silakan isi fokus pembahasan.');
                return false;
            }
            break;
        case 'perdana':
            if (!document.querySelector('[name="nama_event_perdana"]').value.trim()) {
                showToast('warning', 'Peringatan', 'Silakan isi nama/agenda rapat.');
                return false;
            }
            break;
        case 'kepanitiaan':
            if (!document.querySelector('[name="nama_event"]').value.trim()) {
                showToast('warning', 'Peringatan', 'Silakan isi nama event.');
                return false;
            }
            const tipeDivisi = document.querySelector('[name="tipe_divisi"]:checked');
            if (!tipeDivisi) {
                showToast('warning', 'Peringatan', 'Pilih tipe rapat (Per Divisi / Semua).');
                return false;
            }
            if (tipeDivisi.value === 'perdivisi' && !document.querySelector('[name="divisi_rapat"]').value.trim()) {
                showToast('warning', 'Peringatan', 'Silakan isi nama divisi.');
                return false;
            }
            if (!document.querySelector('[name="fokus_bahasan_kepanitiaan"]').value.trim()) {
                showToast('warning', 'Peringatan', 'Silakan isi agenda rapat.');
                return false;
            }
            break;
        case 'bph':
            if (!document.querySelector('[name="fokus_bahasan_bph"]').value.trim()) {
                showToast('warning', 'Peringatan', 'Silakan isi fokus bahasan.');
                return false;
            }
            break;
        case 'lintas_dept':
            const checked = document.querySelectorAll('[name="departemen_collab[]"]:checked');
            if (checked.length < 2) {
                showToast('warning', 'Peringatan', 'Pilih minimal 2 departemen.');
                return false;
            }
            if (!document.querySelector('[name="fokus_bahasan_lintas"]').value.trim()) {
                showToast('warning', 'Peringatan', 'Silakan isi agenda.');
                return false;
            }
            break;
    }
    
    if (!document.querySelector('[name="tanggal"]').value || !document.querySelector('[name="waktu_mulai"]').value) {
        showToast('warning', 'Peringatan', 'Tanggal dan waktu harus diisi.');
        return false;
    }
    return true;
}

// ============================================
// TYPE & MODE SELECTION
// ============================================
function selectRapatType(jenis, card) {
    document.querySelectorAll('#step2 .rapat-type-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    selectedJenis = jenis;
    document.getElementById('jenisRapat').value = jenis;
    document.getElementById('step2Error').style.display = 'none';
    
    document.querySelectorAll('.dynamic-fields').forEach(f => f.style.display = 'none');
    const fieldGroup = document.getElementById('fields_' + jenis);
    if (fieldGroup) fieldGroup.style.display = 'block';
}

function selectMode(mode, card) {
    document.querySelectorAll('#step4 .rapat-type-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    selectedMode = mode;
    document.getElementById('modeRapat').value = mode;
}

function toggleDivisiField() {
    const tipeDivisi = document.querySelector('[name="tipe_divisi"]:checked');
    const fieldDivisi = document.getElementById('fieldDivisiName');
    fieldDivisi.style.display = (tipeDivisi && tipeDivisi.value === 'perdivisi') ? 'block' : 'none';
}

// ============================================
// CONFIRMATION
// ============================================
function populateConfirmation() {
    const creatorSelect = document.getElementById('selectCreator');
    const creatorOpt = creatorSelect.options[creatorSelect.selectedIndex];
    document.getElementById('confirmCreator').textContent = creatorOpt.text;
    
    document.getElementById('confirmJenis').textContent = JENIS_RAPAT_LABELS[selectedJenis] || selectedJenis;
    document.getElementById('confirmMode').textContent = selectedMode === 'offline' ? '🏢 Offline (Scan QR)' : '🌐 Online (Link)';
    
    const tanggal = document.querySelector('[name="tanggal"]').value;
    const waktu = document.querySelector('[name="waktu_mulai"]').value;
    document.getElementById('confirmTanggal').textContent = formatDate(tanggal) + ' • ' + waktu + ' WITA';
    
    let detailsHTML = '';
    switch(selectedJenis) {
        case 'internal_dept':
            const dept = document.getElementById('selectDepartemen');
            detailsHTML = `<div class="meeting-info-item"><label>Departemen</label><p>${dept.options[dept.selectedIndex].text}</p></div>
                <div class="meeting-info-item" style="margin-top:var(--space-3)"><label>Fokus</label><p>${esc(document.querySelector('[name="fokus_bahasan_internal"]').value)}</p></div>`;
            break;
        case 'perdana':
            detailsHTML = `<div class="meeting-info-item"><label>Agenda</label><p>${esc(document.querySelector('[name="nama_event_perdana"]').value)}</p></div>`;
            break;
        case 'kepanitiaan':
            const td = document.querySelector('[name="tipe_divisi"]:checked')?.value || '';
            detailsHTML = `<div class="meeting-info-item"><label>Event</label><p>${esc(document.querySelector('[name="nama_event"]').value)}</p></div>
                <div class="meeting-info-item" style="margin-top:var(--space-3)"><label>Tipe</label><p>${td === 'perdivisi' ? 'Divisi: ' + esc(document.querySelector('[name="divisi_rapat"]').value) : 'Semua'}</p></div>`;
            break;
        case 'bph':
            detailsHTML = `<div class="meeting-info-item"><label>Fokus</label><p>${esc(document.querySelector('[name="fokus_bahasan_bph"]').value)}</p></div>`;
            break;
        case 'lintas_dept':
            const depts = Array.from(document.querySelectorAll('[name="departemen_collab[]"]:checked')).map(cb => DEPARTEMEN_LABELS[cb.value] || cb.value);
            detailsHTML = `<div class="meeting-info-item"><label>Departemen</label><p>${depts.join(', ')}</p></div>
                <div class="meeting-info-item" style="margin-top:var(--space-3)"><label>Agenda</label><p>${esc(document.querySelector('[name="fokus_bahasan_lintas"]').value)}</p></div>`;
            break;
    }
    document.getElementById('confirmDetails').innerHTML = detailsHTML;
}

function esc(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Submit handler
document.getElementById('meetingForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Memproses...';
});
