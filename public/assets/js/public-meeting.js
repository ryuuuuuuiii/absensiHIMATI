/**
 * Meeting Form — Multi-Step Controller
 * Absensi HIMA TI — Khusus Pengurus & Admin
 */

var currentStep = 1;
var selectedJenis = '';
var selectedMode = 'offline';
var eventMembersCount = 0;
var isRestoringDraft = false;
var isSubmittingForm = false;
var totalSteps = 4;

// ============================================
// STEP NAVIGATION
// ============================================
function goToStep(targetStep) {
    const stepNum = Math.min(Math.max(parseInt(targetStep, 10) || 1, 1), totalSteps);

    for (let i = 1; i <= totalSteps; i++) {
        const stepContent = document.getElementById('step' + i);
        const stepItem = document.getElementById('stepItem' + i);
        const connector = document.getElementById('connector' + i);

        if (stepContent) {
            stepContent.classList.toggle('active', i === stepNum);
        }

        if (stepItem) {
            stepItem.classList.remove('active', 'completed');
            if (i < stepNum) {
                stepItem.classList.add('completed');
            } else if (i === stepNum) {
                stepItem.classList.add('active');
            }
        }

        if (connector) {
            connector.classList.toggle('completed', i < stepNum);
        }
    }

    currentStep = stepNum;
    if (currentStep === totalSteps) {
        populateConfirmation();
    }
}

function nextStep(from) {
    if (!validateStep(from)) return;
    
    const next = from + 1;
    if (next > totalSteps) return;
    
    goToStep(next);
    saveMeetingDraft();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prevStep(from) {
    const prev = from - 1;
    if (prev < 1) return;
    
    goToStep(prev);
    saveMeetingDraft();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============================================
// VALIDATION
// ============================================
function validateStep(step) {
    switch(step) {
        case 1: {
            const err = document.getElementById('step1Error');
            if (!selectedJenis) {
                if (err) err.style.display = 'block';
                return false;
            }
            if (err) err.style.display = 'none';
            return true;
        }
            
        case 2:
            return validateStep2();
            
        case 3:
            return true;
            
        default:
            return true;
    }
}

function validateStep2() {
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
            if (!document.getElementById('selectEvent')?.value) {
                showToast('warning', 'Peringatan', 'Silakan pilih event aktif.');
                return false;
            }
            const tipeDivisi = document.querySelector('[name="tipe_divisi"]:checked');
            if (!tipeDivisi) {
                showToast('warning', 'Peringatan', 'Pilih tipe rapat (Per Divisi / Semua Divisi).');
                return false;
            }
            if (tipeDivisi.value === 'perdivisi') {
                const selectDiv = document.getElementById('selectDivisi');
                if (!selectDiv || !selectDiv.value) {
                    showToast('warning', 'Peringatan', 'Silakan pilih divisi.');
                    return false;
                }
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
    document.querySelectorAll('.rapat-type-card').forEach(c => c.classList.remove('selected'));
    if (card) card.classList.add('selected');
    selectedJenis = jenis;
    const jenisInput = document.getElementById('jenisRapat');
    if (jenisInput) jenisInput.value = jenis;
    const err = document.getElementById('step1Error');
    if (err) err.style.display = 'none';
    
    document.querySelectorAll('.dynamic-fields').forEach(f => f.style.display = 'none');
    const fieldGroup = document.getElementById('fields_' + jenis);
    if (fieldGroup) fieldGroup.style.display = 'block';

    if (jenis === 'kepanitiaan' && !isRestoringDraft) {
        const eventId = document.getElementById('selectEvent')?.value;
        const selectDivisi = document.getElementById('selectDivisi');
        if (eventId && selectDivisi && selectDivisi.options.length <= 1) {
            fetchEventDivisions(eventId);
        }
    }

    saveMeetingDraft();
}

function selectMode(mode, card) {
    document.querySelectorAll('.mode-selection .mode-card').forEach(c => {
        c.classList.remove('selected');
        c.classList.remove('active');
    });
    if (card) {
        card.classList.add('selected');
        card.classList.add('active');
    }
    selectedMode = mode;
    const modeInput = document.getElementById('modeRapat');
    if (modeInput) modeInput.value = mode;

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

    saveMeetingDraft();
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

function toggleDivisiField() {
    const tipeDivisi = document.querySelector('[name="tipe_divisi"]:checked');
    const fieldDivisi = document.getElementById('fieldDivisiName');
    const selectDivisi = document.getElementById('selectDivisi');
    
    if (tipeDivisi && tipeDivisi.value === 'perdivisi') {
        if (fieldDivisi) fieldDivisi.style.display = 'block';
        if (selectDivisi) selectDivisi.disabled = false;

        if (!isRestoringDraft) {
            const eventId = document.getElementById('selectEvent')?.value;
            if (eventId && selectDivisi && selectDivisi.options.length <= 1) {
                fetchEventDivisions(eventId);
            }
        }
    } else {
        if (fieldDivisi) fieldDivisi.style.display = 'none';
        if (selectDivisi) selectDivisi.disabled = true;
    }

    saveMeetingDraft();
}

function fetchEventDivisions(eventId, preselectDivisiId = null) {
    const selectDivisi = document.getElementById('selectDivisi');
    const countLabel = document.getElementById('eventMemberCount');
    if (!selectDivisi) return;

    selectDivisi.innerHTML = '<option value="">— Loading divisi... —</option>';
    if (countLabel) countLabel.innerHTML = 'Memuat data panitia...';
    eventMembersCount = 0;

    if (!eventId) {
        selectDivisi.innerHTML = '<option value="">— Pilih Event Dulu —</option>';
        if (countLabel) countLabel.innerHTML = 'Pilih event untuk melihat divisi dan panitia.';
        saveMeetingDraft();
        return;
    }

    const rawBaseUrl = window.BASE_URL || '';
    const baseUrl = rawBaseUrl.replace(/\/+$/, '');
    const apiUrl = baseUrl ? `${baseUrl}/api/events/${eventId}/data` : `/api/events/${eventId}/data`;
    const fallbackUrl = baseUrl ? `${baseUrl}/meeting/process/get_event_data.php?event_id=${eventId}` : `/meeting/process/get_event_data.php?event_id=${eventId}`;

    function renderDivisions(data) {
        eventMembersCount = data.event?.total_members || 0;
        if (countLabel) {
            countLabel.innerHTML = `<i class="fas fa-users text-primary"></i> Total panitia terdaftar: <b>${eventMembersCount} orang</b>`;
        }

        selectDivisi.innerHTML = '<option value="">— Pilih Divisi —</option>';
        if (!data.divisions || data.divisions.length === 0) {
            selectDivisi.innerHTML += '<option value="" disabled>Belum ada divisi pada event ini</option>';
        } else {
            data.divisions.forEach(div => {
                selectDivisi.innerHTML += `<option value="${div.id}" data-count="${div.total_member}">${escapeHtml(div.nama_divisi)} (${div.total_member} panitia)</option>`;
            });
        }

        const tipeDivisi = document.querySelector('[name="tipe_divisi"]:checked');
        if (tipeDivisi && tipeDivisi.value === 'perdivisi') {
            selectDivisi.disabled = false;
        }

        if (preselectDivisiId) {
            selectDivisi.value = String(preselectDivisiId);
        }

        if (currentStep === totalSteps) {
            populateConfirmation();
        }
    }

    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                return fetch(fallbackUrl).then(res => res.json());
            }
            return response.json();
        })
        .then(res => {
            if (res.success) {
                renderDivisions(res.data);
            } else {
                showToast('danger', 'Error', res.message || 'Gagal mengambil data event');
                selectDivisi.innerHTML = '<option value="">— Gagal Memuat Divisi —</option>';
            }
        })
        .catch(err => {
            console.warn('API fetch failed, trying relative path...', err);
            fetch(`/api/events/${eventId}/data`)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        renderDivisions(res.data);
                    } else {
                        showToast('danger', 'Error', res.message || 'Gagal mengambil data event');
                        selectDivisi.innerHTML = '<option value="">— Gagal Memuat Divisi —</option>';
                    }
                })
                .catch(err2 => {
                    console.error('All division fetch attempts failed:', err2);
                    showToast('danger', 'Error', 'Gagal memuat divisi event.');
                    selectDivisi.innerHTML = '<option value="">— Gagal Memuat Data —</option>';
                    if (countLabel) countLabel.innerHTML = 'Gagal memuat data event.';
                });
        });
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ============================================
// CONFIRMATION
// ============================================
function parseCreatorInfo(creatorOpt) {
    if (!creatorOpt || !creatorOpt.value) {
        return { name: '-', role: '', dept: '', initials: 'SA' };
    }
    let name = creatorOpt.dataset?.name || '';
    let role = creatorOpt.dataset?.jabatan || '';
    let dept = creatorOpt.dataset?.dept || '';
    const text = creatorOpt.text || '';

    if (!name && text) {
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
        } else {
            name = text;
        }
    }

    if (!dept && creatorOpt.parentElement && creatorOpt.parentElement.label) {
        dept = creatorOpt.parentElement.label;
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

    // 1. Pembuat Rapat
    const creatorEl = document.getElementById('selectCreator');
    const creatorOpt = (creatorEl && creatorEl.tagName === 'SELECT' && creatorEl.selectedIndex >= 0)
        ? creatorEl.options[creatorEl.selectedIndex]
        : creatorEl;
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
                        <div class="confirm-target-desc">Wajib dihadiri seluruh pengurus & anggota aktif</div>
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
            if (td === 'perdivisi') {
                const selectDiv = document.getElementById('selectDivisi');
                if (selectDiv && selectDiv.selectedIndex > 0) {
                    divisiLabel = 'Divisi ' + selectDiv.options[selectDiv.selectedIndex].text.split(' (')[0];
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
                        <div class="confirm-target-desc">Event: ${escapeHtml(namaEvent)} (+ Pengurus Inti hadir opsional)</div>
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

function esc(str) {
    return escapeHtml(str);
}

// ============================================
// DRAFT PERSISTENCE (Auto-Save & Restore)
// ============================================
function getDraftStorageKey() {
    const creatorEl = document.getElementById('selectCreator');
    const creatorId = (creatorEl && creatorEl.tagName !== 'SELECT' && creatorEl.value) ? creatorEl.value : 'default';
    return 'himati_meeting_draft_' + creatorId;
}

function saveMeetingDraft() {
    if (isRestoringDraft || isSubmittingForm) return;
    const form = document.getElementById('meetingForm');
    if (!form) return;

    try {
        const collabChecked = Array.from(form.querySelectorAll('[name="departemen_collab[]"]:checked')).map(cb => cb.value);
        const tipeDivisiEl = form.querySelector('[name="tipe_divisi"]:checked');
        const creatorEl = document.getElementById('selectCreator');

        const draft = {
            currentStep: currentStep,
            selectedJenis: selectedJenis,
            selectedMode: selectedMode,
            created_by: (creatorEl && creatorEl.tagName === 'SELECT') ? creatorEl.value : undefined,
            departemen: document.getElementById('selectDepartemen')?.value || '',
            fokus_bahasan_internal: form.querySelector('[name="fokus_bahasan_internal"]')?.value || '',
            nama_event_perdana: form.querySelector('[name="nama_event_perdana"]')?.value || '',
            event_id: document.getElementById('selectEvent')?.value || '',
            tipe_divisi: tipeDivisiEl ? tipeDivisiEl.value : 'semua',
            divisi_id: document.getElementById('selectDivisi')?.value || '',
            fokus_bahasan_kepanitiaan: form.querySelector('[name="fokus_bahasan_kepanitiaan"]')?.value || '',
            departemen_collab: collabChecked,
            fokus_bahasan_lintas: form.querySelector('[name="fokus_bahasan_lintas"]')?.value || '',
            tanggal: form.querySelector('[name="tanggal"]')?.value || '',
            waktu_mulai: form.querySelector('[name="waktu_mulai"]')?.value || '',
            updatedAt: Date.now()
        };

        localStorage.setItem(getDraftStorageKey(), JSON.stringify(draft));
    } catch (e) {
        console.warn('Gagal menyimpan draft rapat:', e);
    }
}

function clearMeetingDraft() {
    try {
        localStorage.removeItem(getDraftStorageKey());
    } catch (e) {}
}

function restoreMeetingDraft() {
    const form = document.getElementById('meetingForm');
    if (!form) return;

    let draft = null;
    try {
        const raw = localStorage.getItem(getDraftStorageKey());
        if (!raw) return;
        draft = JSON.parse(raw);
    } catch (e) {
        return;
    }

    if (!draft || typeof draft !== 'object') return;

    // Batas waktu kadaluarsa draft rapat: 15 menit (15 * 60 * 1000 ms)
    const DRAFT_MAX_AGE_MS = 15 * 60 * 1000;
    if (!draft.updatedAt || (Date.now() - draft.updatedAt > DRAFT_MAX_AGE_MS)) {
        clearMeetingDraft();
        return;
    }

    isRestoringDraft = true;
    try {
        const creatorEl = document.getElementById('selectCreator');
        if (creatorEl && creatorEl.tagName === 'SELECT' && draft.created_by) {
            creatorEl.value = draft.created_by;
        }

        if (draft.departemen !== undefined && draft.departemen !== '') {
            const deptSelect = document.getElementById('selectDepartemen');
            if (deptSelect) deptSelect.value = draft.departemen;
        }

        if (draft.fokus_bahasan_internal !== undefined) {
            const el = form.querySelector('[name="fokus_bahasan_internal"]');
            if (el) el.value = draft.fokus_bahasan_internal;
        }

        if (draft.nama_event_perdana !== undefined) {
            const el = form.querySelector('[name="nama_event_perdana"]');
            if (el) el.value = draft.nama_event_perdana;
        }

        if (draft.tipe_divisi) {
            const radio = form.querySelector(`[name="tipe_divisi"][value="${draft.tipe_divisi}"]`);
            if (radio) radio.checked = true;
        }

        if (draft.event_id !== undefined && draft.event_id !== '') {
            const evtSelect = document.getElementById('selectEvent');
            if (evtSelect) evtSelect.value = draft.event_id;
        }

        toggleDivisiField();
        if (draft.event_id) {
            fetchEventDivisions(draft.event_id, draft.divisi_id || null);
        }

        if (draft.fokus_bahasan_kepanitiaan !== undefined) {
            const el = form.querySelector('[name="fokus_bahasan_kepanitiaan"]');
            if (el) el.value = draft.fokus_bahasan_kepanitiaan;
        }

        if (Array.isArray(draft.departemen_collab)) {
            form.querySelectorAll('[name="departemen_collab[]"]').forEach(cb => {
                cb.checked = draft.departemen_collab.includes(cb.value);
            });
        }

        if (draft.fokus_bahasan_lintas !== undefined) {
            const el = form.querySelector('[name="fokus_bahasan_lintas"]');
            if (el) el.value = draft.fokus_bahasan_lintas;
        }

        // Cek dan pulihkan tanggal: hanya gunakan tanggal draft jika tanggalnya hari ini atau di masa depan
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const todayStr = `${year}-${month}-${day}`;
        const currentHours = String(now.getHours()).padStart(2, '0');
        const currentMinutes = String(now.getMinutes()).padStart(2, '0');
        const currentTimeStr = `${currentHours}:${currentMinutes}`;

        const tanggalEl = form.querySelector('[name="tanggal"]');
        if (tanggalEl) {
            if (draft.tanggal && draft.tanggal >= todayStr) {
                tanggalEl.value = draft.tanggal;
            } else {
                tanggalEl.value = todayStr;
            }
        }

        // Cek dan pulihkan waktu_mulai:
        // Jika tanggal rapat di masa depan, gunakan waktu draft.
        // Jika tanggal rapat hari ini, jangan biarkan waktu mulai sudah lewat lama (> 60 menit) agar rapat baru tidak langsung expired.
        const waktuEl = form.querySelector('[name="waktu_mulai"]');
        if (waktuEl) {
            let useCurrentTime = true;
            if (draft.tanggal && draft.tanggal > todayStr && draft.waktu_mulai) {
                waktuEl.value = draft.waktu_mulai;
                useCurrentTime = false;
            } else if ((draft.tanggal === todayStr || !draft.tanggal) && draft.waktu_mulai) {
                const parts = draft.waktu_mulai.split(':');
                if (parts.length >= 2) {
                    const draftMinutes = (parseInt(parts[0], 10) * 60) + parseInt(parts[1], 10);
                    const nowMinutes = (now.getHours() * 60) + now.getMinutes();
                    // Jika waktu draft masih dalam 60 menit terakhir atau jam mendatang hari ini
                    if (nowMinutes - draftMinutes <= 60) {
                        waktuEl.value = draft.waktu_mulai;
                        useCurrentTime = false;
                    }
                }
            }
            if (useCurrentTime) {
                waktuEl.value = currentTimeStr;
            }
        }

        if (draft.selectedJenis) {
            const jenisCard = document.querySelector(`.rapat-type-card[data-jenis="${draft.selectedJenis}"]`)
                || document.querySelector(`.rapat-type-card[onclick*="'${draft.selectedJenis}'"]`);
            selectRapatType(draft.selectedJenis, jenisCard);
        }

        if (draft.selectedMode) {
            const modeCard = document.querySelector(`.mode-card[data-mode="${draft.selectedMode}"]`)
                || document.querySelector(`.mode-card[onclick*="'${draft.selectedMode}'"]`);
            selectMode(draft.selectedMode, modeCard);
        }

        if (draft.currentStep && draft.currentStep > 1 && draft.selectedJenis) {
            goToStep(draft.currentStep);
        } else {
            goToStep(1);
        }
    } finally {
        isRestoringDraft = false;
    }
}

function initMeetingForm() {
    const form = document.getElementById('meetingForm');
    if (!form) return;

    currentStep = 1;
    restoreMeetingDraft();

    form.removeEventListener('input', saveMeetingDraft);
    form.removeEventListener('change', saveMeetingDraft);
    form.addEventListener('input', saveMeetingDraft);
    form.addEventListener('change', saveMeetingDraft);

    form.onsubmit = function() {
        isSubmittingForm = true;
        clearMeetingDraft();
        const btn = document.getElementById('btnSubmit');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Memproses...';
        }
    };
}

window.initMeetingForm = initMeetingForm;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMeetingForm);
} else {
    initMeetingForm();
}
document.addEventListener('portal:page-loaded', initMeetingForm);

window.addEventListener('pagehide', function() {
    saveMeetingDraft();
});

window.addEventListener('pageshow', function(e) {
    if (e.persisted) {
        restoreMeetingDraft();
    }
});

document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        const raw = localStorage.getItem(getDraftStorageKey());
        if (raw) {
            try {
                const d = JSON.parse(raw);
                const DRAFT_MAX_AGE_MS = 15 * 60 * 1000;
                if (!d || !d.updatedAt || (Date.now() - d.updatedAt > DRAFT_MAX_AGE_MS)) {
                    clearMeetingDraft();
                    const form = document.getElementById('meetingForm');
                    if (form) {
                        form.reset();
                        goToStep(1);
                    }
                }
            } catch(e) {}
        }
    }
});
