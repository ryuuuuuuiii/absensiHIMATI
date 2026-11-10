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
    const step3Cards = document.querySelectorAll('#step3 .mode-card');
    step3Cards.forEach(c => {
        c.classList.remove('selected');
        c.classList.remove('active');
    });
    card.classList.add('selected');
    card.classList.add('active');
    
    selectedMode = mode;
    document.getElementById('modeRapat').value = mode;

    const expiryCard = document.getElementById('onlineExpiryCard');
    const inputEnd = document.getElementById('jamBerakhirOnline');
    if (mode === 'online') {
        if (expiryCard) expiryCard.style.display = 'block';
        if (inputEnd && !inputEnd.value) {
            setExpiryOffset(3);
        } else {
            updateExpiryBadge();
        }
    } else {
        if (expiryCard) expiryCard.style.display = 'none';
    }
}

function setExpiryOffset(hours) {
    const startTimeInput = document.querySelector('[name="waktu_mulai"]');
    const endTimeInput = document.getElementById('jamBerakhirOnline');
    if (!startTimeInput || !endTimeInput) return;

    const startVal = startTimeInput.value || '09:00';
    const [h, m] = startVal.split(':').map(Number);
    const endH = (h + hours) % 24;
    const endHStr = String(endH).padStart(2, '0');
    const endMStr = String(m || 0).padStart(2, '0');
    endTimeInput.value = `${endHStr}:${endMStr}`;
    updateExpiryBadge();
}

function updateExpiryBadge() {
    const startTimeInput = document.querySelector('[name="waktu_mulai"]');
    const endTimeInput = document.getElementById('jamBerakhirOnline');
    const badge = document.getElementById('expiryDurationBadge');
    if (!startTimeInput || !endTimeInput || !badge) return;

    const startVal = startTimeInput.value;
    const endVal = endTimeInput.value;
    if (!startVal || !endVal) {
        badge.innerHTML = '<i class="fas fa-stopwatch"></i> Durasi: Fleksibel';
        return;
    }

    const [sh, sm] = startVal.split(':').map(Number);
    const [eh, em] = endVal.split(':').map(Number);
    let diffMinutes = (eh * 60 + em) - (sh * 60 + sm);
    if (diffMinutes <= 0) {
        diffMinutes += 24 * 60; // melintasi tengah malam
    }

    const hours = Math.floor(diffMinutes / 60);
    const mins = diffMinutes % 60;
    let durText = '';
    if (hours > 0) durText += `${hours} Jam `;
    if (mins > 0) durText += `${mins} Menit`;
    badge.innerHTML = `<i class="fas fa-stopwatch"></i> Durasi: ${durText.trim()}`;
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
    
    const baseUrl = window.BASE_URL || '';
    fetch(`${baseUrl}/api/events/${eventId}/data`)
        .then(response => {
            if (!response.ok) {
                return fetch(`${baseUrl}/meeting/process/get_event_data.php?event_id=${eventId}`).then(r => r.json());
            }
            return response.json();
        })
        .then(res => {
            if (res.success) {
                // Update total count
                eventMembersCount = res.data.event?.total_members || 0;
                countLabel.innerHTML = `<i class="fas fa-users text-primary"></i> Total panitia terdaftar: <b>${eventMembersCount} orang</b>`;
                
                // Populate divisions
                selectDivisi.innerHTML = '<option value="">— Pilih Divisi —</option>';
                if (!res.data.divisions || res.data.divisions.length === 0) {
                    selectDivisi.innerHTML += '<option value="" disabled>Belum ada divisi pada event ini</option>';
                } else {
                    res.data.divisions.forEach(div => {
                        selectDivisi.innerHTML += `<option value="${div.id}" data-count="${div.total_member}">${div.nama_divisi} (${div.total_member} panitia)</option>`;
                    });
                }
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
function parseCreatorInfo(creatorOpt) {
    if (!creatorOpt || !creatorOpt.value) {
        return { name: '-', role: '', dept: '', initials: 'SA' };
    }
    const text = creatorOpt.text || '';
    let name = text;
    let role = creatorOpt.dataset?.jabatan || '';
    let dept = creatorOpt.dataset?.dept || '';

    if (!dept && creatorOpt.parentElement && creatorOpt.parentElement.label) {
        dept = creatorOpt.parentElement.label;
    }

    if (text.includes('(') && text.includes(')')) {
        const match = text.match(/^(.*?)\s*\((.*?)\)$/);
        if (match) {
            name = match[1].trim();
            if (!role) role = match[2].trim();
        }
    } else if (text.includes('—') || text.includes(' - ')) {
        const parts = text.split(/ — | - /);
        name = parts[0]?.trim() || text;
        if (!role) role = parts[1]?.trim() || '';
    }

    let initials = 'SA';
    if (name && name !== '-') {
        const cleanName = name.replace(/^(kak|bang|pak|bu|dr|ir)\.?\s+/i, '');
        const words = cleanName.trim().split(/\s+/).filter(Boolean);
        if (words.length >= 2) {
            initials = (words[0][0] + words[1][0]).toUpperCase();
        } else if (words.length === 1 && words[0].length >= 1) {
            initials = words[0].slice(0, 2).toUpperCase();
        }
    }

    return { name, role, dept, initials };
}

function populateConfirmation() {
    const confirmGrid = document.getElementById('confirmGrid');
    if (!confirmGrid) return;

    // 1. Pembuat Rapat (Penanggung Jawab)
    const creatorSelect = document.getElementById('created_by') || document.getElementById('selectCreator');
    const creatorOpt = creatorSelect && creatorSelect.selectedIndex >= 0 ? creatorSelect.options[creatorSelect.selectedIndex] : null;
    const { name: creatorName, role: creatorJabatan, dept: creatorDept, initials } = parseCreatorInfo(creatorOpt);

    // 2. Jenis Rapat & Mode
    const jenisLabel = (typeof JENIS_RAPAT_LABELS !== 'undefined' && JENIS_RAPAT_LABELS[selectedJenis]) ? JENIS_RAPAT_LABELS[selectedJenis] : selectedJenis;

    const jenisIcons = {
        'internal_dept': 'fa-building',
        'perdana': 'fa-flag',
        'kepanitiaan': 'fa-calendar-check',
        'bph': 'fa-crown',
        'lintas_dept': 'fa-people-arrows'
    };
    const jenisIcon = jenisIcons[selectedJenis] || 'fa-layer-group';

    const modeHtml = selectedMode === 'offline'
        ? `<span class="confirm-hero-mode offline"><span class="confirm-mode-dot"></span><i class="fas fa-qrcode"></i> Offline (Scan QR)</span>`
        : `<span class="confirm-hero-mode online"><span class="confirm-mode-dot"></span><i class="fas fa-globe"></i> Online (Link Presensi)</span>`;

    // 3. Tanggal & Waktu
    const tanggal = document.querySelector('[name="tanggal"]')?.value || '';
    const waktu = document.querySelector('[name="waktu_mulai"]')?.value || '';
    let formattedDate = '-';
    if (tanggal) {
        if (typeof formatDate === 'function') {
            formattedDate = formatDate(tanggal);
        } else {
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const d = new Date(tanggal);
            formattedDate = !isNaN(d.getTime()) ? `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}` : tanggal;
        }
    }
    const waktuSelesai = document.getElementById('jamBerakhirOnline')?.value || '';
    let formattedTime = waktu ? `${escapeHtml(waktu)} WITA` : '-';
    if (selectedMode === 'online' && waktu && waktuSelesai) {
        formattedTime = `${escapeHtml(waktu)} - ${escapeHtml(waktuSelesai)} WITA (Batas Absen)`;
    }

    // 4. Spesifik per jenis rapat
    let heroTitle = '';
    let heroSubtitle = '';
    let targetHtml = '';
    let fokusText = '';

    switch (selectedJenis) {
        case 'internal_dept': {
            const dept = document.getElementById('selectDepartemen');
            const deptText = (dept && dept.selectedIndex >= 0 && dept.value) ? dept.options[dept.selectedIndex].text : '-';
            const deptVal = (dept && dept.value) ? dept.value.toLowerCase() : '';
            fokusText = document.querySelector('[name="fokus_bahasan_internal"]')?.value.trim() || '';
            heroTitle = deptText;

            let iconClass = 'fa-building';
            let subtitle = 'Rapat Internal Anggota Departemen';
            let targetDesc = 'Anggota & koor departemen wajib hadir (+ Pengurus Inti hadir opsional)';

            if (deptVal === 'inti') {
                iconClass = 'fa-user-tie';
                subtitle = 'Rapat Khusus Pengurus Inti';
                targetDesc = 'Khusus Pengurus Inti (Wajib hadir, tidak hadir = alpha)';
            } else if (deptVal === 'bph') {
                iconClass = 'fa-crown';
                subtitle = 'Rapat Koordinasi BPH';
                targetDesc = 'Pengurus Inti & seluruh Kepala Departemen (Wajib hadir, tidak hadir = alpha)';
            }

            heroSubtitle = subtitle;
            targetHtml = `
                <div class="confirm-target-box">
                    <i class="fas ${iconClass} text-primary" style="font-size: 1.15rem;"></i>
                    <div>
                        <strong class="confirm-target-title">${escapeHtml(deptText)}</strong>
                        <div class="confirm-target-desc">${targetDesc}</div>
                    </div>
                </div>
            `;
            break;
        }
        case 'perdana': {
            const agendaPerdana = document.querySelector('[name="nama_event_perdana"]')?.value.trim() || '';
            fokusText = agendaPerdana;
            heroTitle = agendaPerdana || 'Rapat Perdana HIMA TI';
            heroSubtitle = 'Rapat Pleno Seluruh Anggota HIMA TI';
            targetHtml = `
                <div class="confirm-target-box">
                    <i class="fas fa-users text-primary" style="font-size: 1.15rem;"></i>
                    <div>
                        <strong class="confirm-target-title">Seluruh Anggota HIMA TI</strong>
                        <div class="confirm-target-desc">Wajib dihadiri seluruh pengurus & anggota aktif (${typeof TOTAL_USERS !== 'undefined' ? TOTAL_USERS : 'semua'} orang)</div>
                    </div>
                </div>
            `;
            break;
        }
        case 'kepanitiaan': {
            const eventSelect = document.getElementById('selectEvent');
            const namaEvent = (eventSelect && eventSelect.selectedIndex >= 0 && eventSelect.value) ? eventSelect.options[eventSelect.selectedIndex].text : '-';
            const td = document.querySelector('[name="tipe_divisi"]:checked')?.value || 'semua';
            let divisiLabel = 'Semua Divisi (Seluruh Panitia)';
            let countLabel = eventMembersCount ? `${eventMembersCount} orang panitia` : 'Seluruh panitia terdaftar';
            if (td === 'perdivisi') {
                const selectDiv = document.getElementById('selectDivisi');
                if (selectDiv && selectDiv.selectedIndex > 0) {
                    divisiLabel = 'Divisi ' + selectDiv.options[selectDiv.selectedIndex].text.split(' (')[0];
                    const divCount = selectDiv.options[selectDiv.selectedIndex].dataset.count;
                    if (divCount) countLabel = `${divCount} orang panitia divisi`;
                }
            }
            fokusText = document.querySelector('[name="fokus_bahasan_kepanitiaan"]')?.value.trim() || '';
            heroTitle = namaEvent;
            heroSubtitle = `Kepanitiaan • ${divisiLabel}`;
            targetHtml = `
                <div class="confirm-target-box">
                    <i class="fas fa-sitemap text-primary" style="font-size: 1.15rem;"></i>
                    <div>
                        <strong class="confirm-target-title">${escapeHtml(divisiLabel)}</strong>
                        <div class="confirm-target-desc">Event: ${escapeHtml(namaEvent)} (${countLabel} + Pengurus Inti opsional)</div>
                    </div>
                </div>
            `;
            break;
        }
        case 'bph': {
            fokusText = document.querySelector('[name="fokus_bahasan_bph"]')?.value.trim() || '';
            heroTitle = 'Rapat Koordinasi BPH';
            heroSubtitle = 'Badan Pengurus Harian & Kepala Departemen';
            targetHtml = `
                <div class="confirm-target-box">
                    <i class="fas fa-crown text-primary" style="font-size: 1.15rem;"></i>
                    <div>
                        <strong class="confirm-target-title">BPH Inti & Ketua Departemen</strong>
                        <div class="confirm-target-desc">Badan Pengurus Harian HIMA TI</div>
                    </div>
                </div>
            `;
            break;
        }
        case 'lintas_dept': {
            const checkedBoxes = Array.from(document.querySelectorAll('[name="departemen_collab[]"]:checked'));
            const depts = checkedBoxes.map(cb => (typeof DEPARTEMEN_LABELS !== 'undefined' && DEPARTEMEN_LABELS[cb.value]) ? DEPARTEMEN_LABELS[cb.value] : cb.value);
            const deptsText = depts.length > 0 ? depts.join(', ') : '-';
            fokusText = document.querySelector('[name="fokus_bahasan_lintas"]')?.value.trim() || '';
            heroTitle = 'Kolaborasi Lintas Departemen';
            heroSubtitle = deptsText;
            targetHtml = `
                <div class="confirm-target-box">
                    <i class="fas fa-people-arrows text-primary" style="font-size: 1.15rem;"></i>
                    <div>
                        <strong class="confirm-target-title">${escapeHtml(deptsText)}</strong>
                        <div class="confirm-target-desc">Anggota dari ${checkedBoxes.length} departemen yang berkolaborasi</div>
                    </div>
                </div>
            `;
            break;
        }
    }

    const html = `
        <div class="confirm-summary-container">
            <!-- Hero Header Card -->
            <div class="confirm-hero-card">
                <div class="confirm-hero-header">
                    <span class="confirm-hero-type"><i class="fas ${jenisIcon}"></i> ${escapeHtml(jenisLabel)}</span>
                    ${modeHtml}
                </div>
                <h4 class="confirm-hero-title">${escapeHtml(heroTitle)}</h4>
                <div class="confirm-target-desc" style="font-size: 0.85rem; font-weight: 500;">
                    ${escapeHtml(heroSubtitle)}
                </div>
            </div>

            <!-- Detail Cards Grid -->
            <div class="confirm-cards-grid">
                <!-- Pembuat Rapat -->
                <div class="confirm-card">
                    <div class="confirm-card-label">
                        <i class="fas fa-user-circle"></i> PEMBUAT RAPAT
                    </div>
                    <div class="confirm-profile-row">
                        <div class="confirm-profile-avatar">${escapeHtml(initials)}</div>
                        <div class="confirm-profile-info">
                            <span class="confirm-profile-name">${escapeHtml(creatorName)}</span>
                            <div class="confirm-profile-sub">
                                ${creatorJabatan ? `<span class="confirm-badge-role"><i class="fas fa-id-badge"></i> ${escapeHtml(creatorJabatan)}</span>` : ''}
                                ${creatorDept ? `<span class="confirm-badge-dept"><i class="fas fa-building"></i> ${escapeHtml(creatorDept)}</span>` : ''}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jadwal Tanggal & Waktu -->
                <div class="confirm-card">
                    <div class="confirm-card-label">
                        <i class="fas fa-calendar-alt"></i> TANGGAL & WAKTU
                    </div>
                    <div class="confirm-schedule-box">
                        <div class="confirm-date-display">
                            <i class="far fa-calendar-check text-primary"></i>
                            <span>${escapeHtml(formattedDate)}</span>
                        </div>
                        <span class="confirm-time-pill">
                            <i class="far fa-clock"></i> ${escapeHtml(formattedTime)}
                        </span>
                    </div>
                </div>

                <!-- Target Peserta (Full Width) -->
                <div class="confirm-card full-width">
                    <div class="confirm-card-label">
                        <i class="fas fa-users"></i> TARGET PESERTA
                    </div>
                    ${targetHtml}
                </div>

                <!-- Fokus Bahasan / Agenda (Full Width) -->
                <div class="confirm-card full-width">
                    <div class="confirm-card-label">
                        <i class="fas fa-bookmark"></i> FOKUS BAHASAN & AGENDA RAPAT
                    </div>
                    <div class="confirm-agenda-container">
                        <p class="confirm-agenda-text">${fokusText ? escapeHtml(fokusText) : '<span style="color: #94a3b8; font-style: italic;">Tidak ada agenda bahasan khusus yang dicantumkan.</span>'}</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    confirmGrid.innerHTML = html;
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
