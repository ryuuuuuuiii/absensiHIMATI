{{-- Gamification EXP & Level Up Celebration Modal --}}
@php
    $expPopupData = $expPopupData ?? session('exp_popup');
    $hasExpPopup = !empty($expPopupData);
    $expAmount = (int) ($expPopupData['exp'] ?? 0);
    $expReason = $expPopupData['reason'] ?? 'Presensi Kehadiran';
    $isLevelUp = !empty($expPopupData['is_level_up']);
    $levelData = $expPopupData['level_data'] ?? null;
    $totalExp = (int) ($expPopupData['total_exp'] ?? ($levelData['exp'] ?? 0));
    $levelNum = (int) ($levelData['level'] ?? 1);
    $tierTitle = $levelData['title'] ?? 'Kayu';
    $subRank = $levelData['sub_rank'] ?? 'I';
    $tierIconUrl = $levelData['tier_icon_url'] ?? asset('assets/img/tiers/wood.png');
    $badgeColor = $levelData['badge_color'] ?? '#B45309';
    $badgeBg = $levelData['badge_bg'] ?? 'rgba(180, 83, 9, 0.15)';
    $progress = (int) ($levelData['progress'] ?? 0);
    $needed = (int) ($levelData['needed'] ?? 300);
    $percentage = (float) ($levelData['percentage'] ?? 0);
@endphp

<div class="exp-modal-overlay {{ $hasExpPopup ? 'active' : '' }}" id="globalExpModal" onclick="closeExpModal(event)" aria-modal="true" role="dialog">
    <div class="exp-modal-dialog" onclick="event.stopPropagation()">
        <!-- Confetti Canvas -->
        <canvas id="expModalCanvas" class="exp-modal-canvas"></canvas>

        <!-- Ambient Glow Circles -->
        <div class="exp-ambient-glow glow-1"></div>
        <div class="exp-ambient-glow glow-2"></div>

        <!-- Close Button -->
        <button type="button" class="exp-modal-close" onclick="closeExpModal()" title="Tutup">
            <i class="fas fa-times"></i>
        </button>

        <!-- Badge Icon with Radiating Waves -->
        <div class="exp-badge-container">
            <div class="exp-pulse-ring ring-1"></div>
            <div class="exp-pulse-ring ring-2"></div>
            <div class="exp-badge-icon-box" id="expModalIconWrapper" style="background: {{ $badgeBg }}; border-color: {{ $badgeColor }};">
                <img id="expModalTierIcon" src="{{ $tierIconUrl }}" alt="Tier Icon" class="exp-tier-img">
            </div>
            <div class="exp-badge-mini-sparkle">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        <!-- Tag Label -->
        <div class="exp-modal-tag {{ $isLevelUp ? 'tag-levelup' : 'tag-exp' }}" id="expModalTag">
            <i class="fas {{ $isLevelUp ? 'fa-crown' : 'fa-bolt' }}"></i>
            <span id="expModalTagText">{{ $isLevelUp ? 'SELAMAT, NAIK LEVEL!' : 'EXP BERTAMBAH!' }}</span>
        </div>

        <!-- Huge EXP Display -->
        <div class="exp-amount-display">
            <span class="exp-plus">+</span><span class="exp-number" id="expModalAmount">{{ number_format($expAmount, 0, ',', '.') }}</span>
            <span class="exp-unit">EXP</span>
        </div>

        <!-- Reason / Context -->
        <div class="exp-reason-badge">
            <i class="fas fa-award"></i>
            <span id="expModalReasonText">{{ $expReason }}</span>
        </div>

        <!-- Level & Tier Card -->
        <div class="exp-level-card">
            <div class="exp-level-top">
                <div class="exp-level-info">
                    <span class="exp-level-badge" id="expModalLevelTitle">
                        Lv. {{ $levelNum }} • {{ $tierTitle }} {{ $subRank }}
                    </span>
                </div>
                <div class="exp-total-pill" id="expModalTotal">
                    Total: {{ number_format($totalExp, 0, ',', '.') }} EXP
                </div>
            </div>

            <!-- Animated Progress Bar -->
            <div class="exp-bar-track">
                <div class="exp-bar-fill" id="expModalProgressBar" style="width: {{ $percentage }}%;"></div>
            </div>

            <div class="exp-bar-footer">
                <span id="expModalProgressText">{{ $progress }} / {{ $needed }} EXP menuju Lv. {{ $levelNum + 1 }}</span>
                <span id="expModalPercentageText">{{ $percentage }}%</span>
            </div>
        </div>

        <!-- Action Button -->
        <div class="exp-modal-actions">
            <button type="button" class="btn-exp-claim" onclick="closeExpModal()">
                <span>Mantap, Lanjutkan!</span> <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
/* =========================================================
   GAMIFICATION EXP POP-UP MODAL STYLES
   ========================================================= */
.exp-modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    background: rgba(3, 8, 18, 0.85) !important;
    backdrop-filter: blur(10px) !important;
    -webkit-backdrop-filter: blur(10px) !important;
    z-index: 10090 !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 16px !important;
    opacity: 0;
    transition: opacity 0.25s ease-out;
}

.exp-modal-overlay.active {
    display: flex !important;
    opacity: 1 !important;
}

.exp-modal-dialog {
    position: relative;
    width: 100%;
    max-width: 440px;
    background: #0B192C;
    border: 2.5px solid #30AFFF;
    border-radius: 26px;
    padding: 32px 24px 26px;
    text-align: center;
    box-shadow: 0 16px 0 #040A14, 0 0 50px rgba(48, 175, 255, 0.35);
    overflow: hidden;
    transform: scale(0.85);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.exp-modal-overlay.active .exp-modal-dialog {
    transform: scale(1);
    animation: expPopBounce 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

@keyframes expPopBounce {
    0% { transform: scale(0.65); opacity: 0; }
    70% { transform: scale(1.04); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}

.exp-modal-canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

/* Ambient glow balls */
.exp-ambient-glow {
    position: absolute;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    filter: blur(60px);
    pointer-events: none;
    opacity: 0.35;
    z-index: 0;
}
.exp-ambient-glow.glow-1 {
    top: -40px;
    left: -40px;
    background: #30AFFF;
}
.exp-ambient-glow.glow-2 {
    bottom: -40px;
    right: -40px;
    background: #F59E0B;
}

/* Close button */
.exp-modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #94A3B8;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 15px;
    transition: all 0.2s ease;
    z-index: 10;
}
.exp-modal-close:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: #EF4444;
    color: #F87171;
    transform: rotate(90deg);
}

/* Central Badge with Pulsing Waves */
.exp-badge-container {
    position: relative;
    width: 96px;
    height: 96px;
    margin: 0 auto 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
}

.exp-pulse-ring {
    position: absolute;
    inset: -6px;
    border-radius: 50%;
    border: 2px solid rgba(245, 158, 11, 0.5);
    animation: expPulseRingAnim 2s infinite cubic-bezier(0.215, 0.61, 0.355, 1);
}
.exp-pulse-ring.ring-2 {
    animation-delay: 0.6s;
    border-color: rgba(48, 175, 255, 0.5);
}

@keyframes expPulseRingAnim {
    0% { transform: scale(0.85); opacity: 1; }
    100% { transform: scale(1.4); opacity: 0; }
}

.exp-badge-icon-box {
    width: 86px;
    height: 86px;
    border-radius: 24px;
    border: 3px solid #F59E0B;
    background: rgba(245, 158, 11, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 25px rgba(245, 158, 11, 0.35);
    position: relative;
    z-index: 2;
}

.exp-tier-img {
    width: 58px;
    height: 58px;
    object-fit: contain;
    image-rendering: pixelated;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));
    animation: expFloat 2.6s ease-in-out infinite;
}

@keyframes expFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

.exp-badge-mini-sparkle {
    position: absolute;
    bottom: -4px;
    right: -4px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #F59E0B;
    border: 2px solid #0B192C;
    color: #061A2E;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.6);
    z-index: 3;
}

/* Tag Label */
.exp-modal-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 30px;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 10px;
    position: relative;
    z-index: 2;
}
.exp-modal-tag.tag-exp {
    background: rgba(48, 175, 255, 0.15);
    border: 1.5px solid #30AFFF;
    color: #30AFFF;
}
.exp-modal-tag.tag-levelup {
    background: rgba(245, 158, 11, 0.2);
    border: 1.5px solid #F59E0B;
    color: #FBBF24;
    animation: expGlowPulse 1.6s infinite alternate;
}

@keyframes expGlowPulse {
    from { box-shadow: 0 0 10px rgba(245, 158, 11, 0.3); }
    to { box-shadow: 0 0 22px rgba(245, 158, 11, 0.7); }
}

/* Big EXP Number Display */
.exp-amount-display {
    font-size: 2.85rem;
    font-weight: 900;
    line-height: 1.1;
    margin-bottom: 8px;
    background: linear-gradient(135deg, #F59E0B 0%, #FDE047 50%, #30AFFF 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 4px 14px rgba(245, 158, 11, 0.45));
    letter-spacing: -0.02em;
    position: relative;
    z-index: 2;
}
.exp-amount-display .exp-plus {
    font-size: 2.2rem;
    margin-right: 2px;
}
.exp-amount-display .exp-unit {
    font-size: 1.45rem;
    font-weight: 800;
    margin-left: 4px;
}

/* Reason Subtitle */
.exp-reason-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #94A3B8;
    margin-bottom: 20px;
    position: relative;
    z-index: 2;
}
.exp-reason-badge i {
    color: #10B981;
}

/* Level & Progress Card */
.exp-level-card {
    background: rgba(15, 23, 42, 0.7);
    border: 1.5px solid rgba(48, 175, 255, 0.25);
    border-radius: 16px;
    padding: 14px 16px;
    margin-bottom: 22px;
    text-align: left;
    position: relative;
    z-index: 2;
}

.exp-level-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    gap: 8px;
}

.exp-level-name {
    font-size: 0.92rem;
    font-weight: 800;
    color: #F8FAFC;
    letter-spacing: 0.02em;
}

.exp-total-pill {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 12px;
    background: rgba(48, 175, 255, 0.15);
    color: #30AFFF;
    border: 1px solid rgba(48, 175, 255, 0.3);
}

.exp-bar-track {
    width: 100%;
    height: 10px;
    border-radius: 8px;
    background: #061220;
    border: 1px solid rgba(255, 255, 255, 0.08);
    overflow: hidden;
    margin-bottom: 6px;
    position: relative;
}

.exp-bar-fill {
    height: 100%;
    border-radius: 8px;
    background: linear-gradient(90deg, #30AFFF 0%, #10B981 70%, #F59E0B 100%);
    box-shadow: 0 0 10px rgba(48, 175, 255, 0.5);
    transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
}

.exp-bar-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.74rem;
    color: #64748B;
    font-weight: 600;
}

/* Action Button */
.exp-modal-actions {
    position: relative;
    z-index: 2;
}

.btn-exp-claim {
    width: 100%;
    padding: 13px 20px;
    border-radius: 14px;
    font-size: 0.95rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    background: linear-gradient(135deg, #30AFFF 0%, #0088FF 100%);
    color: #FFFFFF;
    border: 2px solid #30AFFF;
    box-shadow: 0 6px 0 #0055AA, 0 0 25px rgba(48, 175, 255, 0.4);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.15s ease;
}
.btn-exp-claim:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 0 #0055AA, 0 0 35px rgba(48, 175, 255, 0.6);
}
.btn-exp-claim:active {
    transform: translateY(4px);
    box-shadow: 0 2px 0 #0055AA;
}

@media (max-width: 480px) {
    .exp-modal-dialog {
        padding: 26px 18px 22px;
        border-radius: 20px;
    }
    .exp-amount-display {
        font-size: 2.35rem;
    }
    .exp-badge-container {
        width: 82px;
        height: 82px;
    }
    .exp-badge-icon-box {
        width: 74px;
        height: 74px;
    }
    .exp-tier-img {
        width: 48px;
        height: 48px;
    }
}
</style>

<script>
    if (!window.__EXP_POPUP_INIT) {
        window.__EXP_POPUP_INIT = true;

        // Sound Synthesizer via Web Audio API
        window.playExpChime = function(isLevelUp = false) {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }

                const now = ctx.currentTime;
                const notes = isLevelUp 
                    ? [523.25, 659.25, 783.99, 1046.50] // C5 -> E5 -> G5 -> C6 (Major Arpeggio)
                    : [659.25, 830.61, 987.77];         // E5 -> G#5 -> B5 (Bright chord)

                notes.forEach((freq, idx) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = isLevelUp ? 'triangle' : 'sine';
                    osc.frequency.setValueAtTime(freq, now + (idx * 0.1));

                    const startTime = now + (idx * 0.1);
                    const duration = idx === notes.length - 1 ? 0.6 : 0.28;

                    gain.gain.setValueAtTime(0.001, startTime);
                    gain.gain.exponentialRampToValueAtTime(0.18, startTime + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, startTime + duration);

                    osc.connect(gain);
                    gain.connect(ctx.destination);

                    osc.start(startTime);
                    osc.stop(startTime + duration);
                });
            } catch (err) {
                // Audio not allowed or unsupported, fail silently
            }
        };

        // Confetti Particle Burst on Canvas
        window.triggerExpCanvasParticles = function() {
            const canvas = document.getElementById('expModalCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width || 440;
            canvas.height = rect.height || 460;

            const particles = [];
            const colors = ['#F59E0B', '#FBBF24', '#30AFFF', '#10B981', '#38BDF8', '#FFFFFF'];

            for (let i = 0; i < 48; i++) {
                particles.push({
                    x: canvas.width / 2,
                    y: canvas.height * 0.35,
                    vx: (Math.random() - 0.5) * 12,
                    vy: (Math.random() - 0.8) * 11,
                    size: Math.random() * 6 + 3,
                    color: colors[Math.floor(Math.random() * colors.length)],
                    alpha: 1,
                    rotation: Math.random() * 360,
                    rotationSpeed: (Math.random() - 0.5) * 8
                });
            }

            let animFrame;
            const startTime = performance.now();

            function draw() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                const elapsed = performance.now() - startTime;
                let activeCount = 0;

                particles.forEach(p => {
                    p.x += p.vx;
                    p.y += p.vy;
                    p.vy += 0.22; // gravity
                    p.vx *= 0.98; // drag
                    p.rotation += p.rotationSpeed;
                    p.alpha = Math.max(0, 1 - (elapsed / 2200));

                    if (p.alpha > 0.01) {
                        activeCount++;
                        ctx.save();
                        ctx.translate(p.x, p.y);
                        ctx.rotate((p.rotation * Math.PI) / 180);
                        ctx.globalAlpha = p.alpha;
                        ctx.fillStyle = p.color;
                        ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
                        ctx.restore();
                    }
                });

                if (activeCount > 0 && elapsed < 2300) {
                    animFrame = requestAnimationFrame(draw);
                } else {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }
            }

            draw();
        };

        // Close Modal
        window.closeExpModal = function(e) {
            if (e && e.target && e.target.id !== 'globalExpModal') {
                return;
            }
            const modal = document.getElementById('globalExpModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        };

        // Trigger Pop-up Dynamically via JavaScript
        window.showExpPopup = function(data) {
            if (!data || !data.exp) return;

            const modal = document.getElementById('globalExpModal');
            if (!modal) return;

            const amountEl = document.getElementById('expModalAmount');
            const reasonEl = document.getElementById('expModalReasonText');
            const tagEl = document.getElementById('expModalTag');
            const tagTextEl = document.getElementById('expModalTagText');
            const levelTitleEl = document.getElementById('expModalLevelTitle');
            const totalEl = document.getElementById('expModalTotal');
            const barEl = document.getElementById('expModalProgressBar');
            const progressTextEl = document.getElementById('expModalProgressText');
            const percentageTextEl = document.getElementById('expModalPercentageText');
            const tierIconEl = document.getElementById('expModalTierIcon');
            const iconWrapperEl = document.getElementById('expModalIconWrapper');

            const isLevelUp = Boolean(data.is_level_up);
            const levelData = data.level_data || {};
            const expVal = Number(data.exp || 0);

            if (amountEl) amountEl.textContent = new Intl.NumberFormat('id-ID').format(expVal);
            if (reasonEl) reasonEl.textContent = data.reason || 'Presensi Kehadiran';

            if (tagEl && tagTextEl) {
                if (isLevelUp) {
                    tagEl.className = 'exp-modal-tag tag-levelup';
                    tagTextEl.textContent = 'SELAMAT, NAIK LEVEL!';
                    tagEl.querySelector('i')?.setAttribute('class', 'fas fa-crown');
                } else {
                    tagEl.className = 'exp-modal-tag tag-exp';
                    tagTextEl.textContent = 'EXP BERTAMBAH!';
                    tagEl.querySelector('i')?.setAttribute('class', 'fas fa-bolt');
                }
            }

            if (levelTitleEl && levelData.level) {
                levelTitleEl.textContent = `Lv. ${levelData.level} • ${levelData.title || 'Kayu'} ${levelData.sub_rank || 'I'}`;
            }

            if (totalEl) {
                const total = data.total_exp || levelData.exp || 0;
                totalEl.textContent = `Total: ${new Intl.NumberFormat('id-ID').format(total)} EXP`;
            }

            if (tierIconEl && levelData.tier_icon_url) {
                tierIconEl.src = levelData.tier_icon_url;
            }

            if (iconWrapperEl) {
                if (levelData.badge_bg) iconWrapperEl.style.background = levelData.badge_bg;
                if (levelData.badge_color) iconWrapperEl.style.borderColor = levelData.badge_color;
            }

            if (barEl) {
                barEl.style.width = '0%';
                setTimeout(() => {
                    barEl.style.width = `${levelData.percentage || 0}%`;
                }, 150);
            }

            if (progressTextEl && levelData.needed) {
                progressTextEl.textContent = `${levelData.progress || 0} / ${levelData.needed || 300} EXP menuju Lv. ${(levelData.level || 1) + 1}`;
            }

            if (percentageTextEl) {
                percentageTextEl.textContent = `${levelData.percentage || 0}%`;
            }

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            window.playExpChime(isLevelUp);
            setTimeout(() => {
                window.triggerExpCanvasParticles();
            }, 80);
        };

        // Listen for ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.closeExpModal();
            }
        });
    }

    @if($hasExpPopup)
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                window.playExpChime({{ $isLevelUp ? 'true' : 'false' }});
                window.triggerExpCanvasParticles();
            }, 300);
        });
        document.body.style.overflow = 'hidden';
    @endif
</script>

