/**
 * Meeting Form — Multi-Step Controller
 * Absensi HIMA TI Politeknik Hasnur
 */

let currentStep = 1;
let selectedJenis = '';
let selectedMode = 'offline';
let eventMembersCount = 0; // Total members from selected event

const stepFlow = ['step1', 'step2', 'step3', 'step4'];

/**
 * Get current step index (0-based) in the flow.
 */
function getCurrentStepIndex() {
    return stepFlow.indexOf('step' + currentStep);
}

// ============================================
// STEP NAVIGATION
// ============================================
function nextStep(from) {
    if (!validateStep(from)) return;
    
    const fromId = 'step' + from;
    const fromIndex = stepFlow.indexOf(fromId);
    if (fromIndex === -1 || fromIndex >= stepFlow.length - 1) return;
    
    const nextId = stepFlow[fromIndex + 1];
    
    // Hide current step content, show next
    document.getElementById(fromId).classList.remove('active');
    document.getElementById(nextId).classList.add('active');
    
    // Update progress bar
    document.getElementById('stepItem' + from).classList.remove('active');
    document.getElementById('stepItem' + from).classList.add('completed');
    if (document.getElementById('connector' + from)) {
        document.getElementById('connector' + from).classList.add('completed');
    }
    document.getElementById('stepItem' + (from + 1)).classList.add('active');
    
    // Update currentStep
    currentStep = from + 1;
    
    // If going to confirmation (last step), populate summary
    if (nextId === 'step4') {
        populateConfirmation();
    }
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prevStep(from) {
    const fromId = 'step' + from;
    const fromIndex = stepFlow.indexOf(fromId);
    if (fromIndex <= 0) return;
    
    const prevId = stepFlow[fromIndex - 1];
    
    // Hide current step content, show previous
    document.getElementById(fromId).classList.remove('active');
    document.getElementById(prevId).classList.add('active');
    
    // Update progress bar
    document.getElementById('stepItem' + from).classList.remove('active');
    document.getElementById('stepItem' + (from - 1)).classList.remove('completed');
    document.getElementById('stepItem' + (from - 1)).classList.add('active');
    if (document.getElementById('connector' + (from - 1))) {
        document.getElementById('connector' + (from - 1)).classList.remove('completed');
    }
    
    // Update currentStep
    currentStep = from - 1;
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============================================
// VALIDATION
// ============================================
function validateStep(step) {
    switch(step) {
        case 1:
            if (!selectedJenis) {
                document.getElementById('step1Error').style.display = 'block';
                return false;
            }
            document.getElementById('step1Error').style.display = 'none';
            return true;
            
        case 2:
            return validateStep2();
            
        case 3:
            return true;
            
        default:
            return true;
    }
}

function validateStep2() {
    const jenis = selectedJenis;
    
    switch(jenis) {
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
            if (!document.querySelector('[name="event_id"]').value) {
                showToast('warning', 'Peringatan', 'Silakan pilih event.');
                return false;
            }
            const tipeDivisi = document.querySelector('[name="tipe_divisi"]:checked');
            if (tipeDivisi.value === 'perdivisi' && !document.querySelector('[name="divisi_id"]').value) {
                showToast('warning', 'Peringatan', 'Silakan pilih divisi.');
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
                showToast('warning', 'Peringatan', 'Pilih minimal 2 departemen untuk kolaborasi.');
                return false;
            }
            if (!document.querySelector('[name="fokus_bahasan_lintas"]').value.trim()) {
                showToast('warning', 'Peringatan', 'Silakan isi agenda/bahasan rapat.');
                return false;
            }
            break;
    }
    
    const tanggal = document.querySelector('[name="tanggal"]').value;
    const waktu = document.querySelector('[name="waktu_mulai"]').value;
    
    if (!tanggal) {
        showToast('warning', 'Peringatan', 'Silakan pilih tanggal rapat.');
        return false;
    }
    if (!waktu) {
        showToast('warning', 'Peringatan', 'Silakan isi waktu mulai.');
        return false;
    }
    
    return true;
}

// ============================================
// TYPE & MODE SELECTION
// ============================================
function selectRapatType(jenis, card) {
    document.querySelectorAll('.rapat-type-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    
    selectedJenis = jenis;
    document.getElementById('jenisRapat').value = jenis;
    document.getElementById('step1Error').style.display = 'none';
    
    document.querySelectorAll('.dynamic-fields').forEach(f => f.style.display = 'none');
    const fieldGroup = document.getElementById('fields_' + jenis);
    if (fieldGroup) {
        fieldGroup.style.display = 'block';
    }
}

function selectMode(mode, card) {
    const step3Cards = document.querySelectorAll('#step3 .rapat-type-card');
    step3Cards.forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    
    selectedMode = mode;
    document.getElementById('modeRapat').value = mode;
}

// ============================================
// KEPANITIAAN DYNAMIC FIELDS
// ============================================
function toggleDivisiField() {
    const tipeDivisi = document.querySelector('[name="tipe_divisi"]:checked');
    const fieldDivisi = document.getElementById('fieldDivisiName');
    const selectDivisi = document.getElementById('selectDivisi');
    
    if (tipeDivisi && tipeDivisi.value === 'perdivisi') {
        fieldDivisi.style.display = 'block';
        selectDivisi.disabled = false;
        
        // Ensure event is selected before showing divisions
        const eventId = document.querySelector('[name="event_id"]').value;
        if (eventId && selectDivisi.options.length <= 1) {
            fetchEventDivisions(eventId);
        }
    } else {
        fieldDivisi.style.display = 'none';
        selectDivisi.disabled = true;
    }
}

function fetchEventDivisions(eventId) {
    const selectDivisi = document.getElementById('selectDivisi');
    const countLabel = document.getElementById('eventMemberCount');
    
    // Reset
    selectDivisi.innerHTML = '<option value="">— Loading... —</option>';
    countLabel.innerHTML = 'Memuat data...';
    eventMembersCount = 0;
    
    if (!eventId) {
        selectDivisi.innerHTML = '<option value="">— Pilih Event Dulu —</option>';
        countLabel.innerHTML = 'Pilih event untuk melihat jumlah panitia.';
        return;
    }
    
    fetch(`${BASE_URL}/meeting/process/get_event_data.php?event_id=${eventId}`)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                // Update total count
                eventMembersCount = res.data.event.total_members;
                countLabel.innerHTML = `<i class="fas fa-users text-primary"></i> Total panitia terdaftar: <b>${eventMembersCount} orang</b>`;
                
                // Populate divisions
                selectDivisi.innerHTML = '<option value="">— Pilih Divisi —</option>';
                res.data.divisions.forEach(div => {
                    selectDivisi.innerHTML += `<option value="${div.id}" data-count="${div.total_member}">${div.nama_divisi} (${div.total_member} panitia)</option>`;
                });
            } else {
                showToast('danger', 'Error', res.message || 'Gagal mengambil data event');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('danger', 'Error', 'Koneksi ke server bermasalah');
            selectDivisi.innerHTML = '<option value="">— Error Fetching Data —</option>';
            countLabel.innerHTML = 'Gagal memuat data.';
        });
}

// ============================================
// CONFIRMATION POPULATION
// ============================================
function populateConfirmation() {
    document.getElementById('confirmJenis').textContent = JENIS_RAPAT_LABELS[selectedJenis] || selectedJenis;
    document.getElementById('confirmMode').textContent = selectedMode === 'offline' ? '🏢 Offline (Scan QR)' : '🌐 Online (Link)';
    
    const tanggal = document.querySelector('[name="tanggal"]').value;
    document.getElementById('confirmTanggal').textContent = formatDate(tanggal);
    
    const waktu = document.querySelector('[name="waktu_mulai"]').value;
    document.getElementById('confirmWaktu').textContent = waktu + ' WITA';
    
    let detailsHTML = '';
    
    switch(selectedJenis) {
        case 'internal_dept':
            const dept = document.getElementById('selectDepartemen');
            const deptLabel = dept.options[dept.selectedIndex].text;
            const fokusInt = document.querySelector('[name="fokus_bahasan_internal"]').value;
            detailsHTML = `
                <div class="meeting-info-item">
                    <label>Departemen</label>
                    <p>${deptLabel}</p>
                </div>
                <div class="meeting-info-item" style="margin-top: var(--space-3);">
                    <label>Fokus Pembahasan</label>
                    <p>${escapeHtml(fokusInt)}</p>
                </div>`;
            break;
            
        case 'perdana':
            const agendaPerdana = document.querySelector('[name="nama_event_perdana"]').value;
            detailsHTML = `
                <div class="meeting-info-item">
                    <label>Nama / Agenda</label>
                    <p>${escapeHtml(agendaPerdana)}</p>
                </div>
                <div class="meeting-info-item" style="margin-top: var(--space-3);">
                    <label>Peserta</label>
                    <p>Seluruh Anggota (${TOTAL_USERS} orang)</p>
                </div>`;
            break;
            
        case 'kepanitiaan':
            const eventSelect = document.getElementById('selectEvent');
            const namaEvent = eventSelect.options[eventSelect.selectedIndex].text;
            
            const tipeDivisi = document.querySelector('[name="tipe_divisi"]:checked').value;
            
            let targetDivisi = 'Semua Divisi';
            let targetCount = eventMembersCount;
            
            if (tipeDivisi === 'perdivisi') {
                const selectDiv = document.getElementById('selectDivisi');
                if (selectDiv.selectedIndex > 0) {
                    targetDivisi = selectDiv.options[selectDiv.selectedIndex].text.split(' (')[0];
                    targetCount = selectDiv.options[selectDiv.selectedIndex].dataset.count || 0;
                }
            }
            
            const fokusKep = document.querySelector('[name="fokus_bahasan_kepanitiaan"]').value;
            
            detailsHTML = `
                <div class="meeting-info-item">
                    <label>Event / Kepanitiaan</label>
                    <p>${escapeHtml(namaEvent)}</p>
                </div>
                <div class="meeting-info-item" style="margin-top: var(--space-3);">
                    <label>Tipe Rapat</label>
                    <p>${tipeDivisi === 'perdivisi' ? 'Per Divisi: ' + escapeHtml(targetDivisi) : 'Seluruh Panitia Event'}</p>
                </div>
                <div class="meeting-info-item" style="margin-top: var(--space-3);">
                    <label>Agenda</label>
                    <p>${escapeHtml(fokusKep)}</p>
                </div>
                <div class="meeting-info-item" style="margin-top: var(--space-3);">
                    <label>Peserta Rapat (Otomatis)</label>
                    <p style="color: var(--primary); font-weight: 600;">
                        <i class="fas fa-users"></i> ${targetCount} orang
                    </p>
                    <small style="color: var(--text-muted); display: block; margin-top: 4px;">+ Semua BPH (Opsional)</small>
                </div>`;
            break;
            
        case 'bph':
            const fokusBph = document.querySelector('[name="fokus_bahasan_bph"]').value;
            detailsHTML = `
                <div class="meeting-info-item">
                    <label>Fokus Bahasan</label>
                    <p>${escapeHtml(fokusBph)}</p>
                </div>
                <div class="meeting-info-item" style="margin-top: var(--space-3);">
                    <label>Peserta</label>
                    <p>Seluruh BPH (Inti + Kepala Departemen)</p>
                </div>`;
            break;
            
        case 'lintas_dept':
            const checkedDepts = document.querySelectorAll('[name="departemen_collab[]"]:checked');
            const deptNames = Array.from(checkedDepts).map(cb => DEPARTEMEN_LABELS[cb.value] || cb.value);
            const fokusLintas = document.querySelector('[name="fokus_bahasan_lintas"]').value;
            detailsHTML = `
                <div class="meeting-info-item">
                    <label>Departemen Kolaborasi</label>
                    <p>${deptNames.join(', ')}</p>
                </div>
                <div class="meeting-info-item" style="margin-top: var(--space-3);">
                    <label>Agenda / Bahasan</label>
                    <p>${escapeHtml(fokusLintas)}</p>
                </div>`;
            break;
    }
    
    document.getElementById('confirmDetails').innerHTML = detailsHTML;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

document.getElementById('meetingForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Memproses...';
});
