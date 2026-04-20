const focusState = {
    mode: 'pomodoro',
    totalSeconds: 25 * 60,
    remainingSeconds: 25 * 60,
    running: false,
    startedAt: null,
    carriedSeconds: 0,
    frameId: null,
    targetCycles: 1,
    completedCycles: 0
};

/* Track last mouse movement time for idle detection */
let focusLastMouseMove = Date.now();

document.addEventListener('DOMContentLoaded', async () => {
    bindFocusControls();
    await Promise.all([loadFocusTasks(), loadFocusHeatmap(), loadSessionLog()]);
    
    const saved = window.TimerSync?.getState();
    if (saved && typeof saved.remainingSeconds === 'number') {
        focusState.totalSeconds = saved.totalSeconds;
        focusState.remainingSeconds = saved.remainingSeconds;
        focusState.running = saved.running;
        focusState.startedAt = saved.startedAt;
        focusState.carriedSeconds = saved.carriedSeconds;
        if (saved.mode) focusState.mode = saved.mode;
        if (saved.targetCycles !== undefined) focusState.targetCycles = saved.targetCycles;
        if (saved.completedCycles !== undefined) focusState.completedCycles = saved.completedCycles;
        updateUIForMode(false); // updates buttons, but don't reset timer
        
        if (saved.running) {
            focusState.running = false;
            startFocusTimer(true);
        }
    }
    
    renderFocusTimer();
    updateCycleTrackerUI();
});

window.addEventListener('storage', (e) => {
    if (e.key === 'crimson_global_timer' && e.newValue) {
        const saved = JSON.parse(e.newValue);
        if (saved.source === 'focus') return;
        focusState.totalSeconds = saved.totalSeconds;
        focusState.remainingSeconds = saved.remainingSeconds;
        focusState.running = saved.running;
        focusState.startedAt = saved.startedAt;
        focusState.carriedSeconds = saved.carriedSeconds;
        if (saved.mode) focusState.mode = saved.mode;
        if (saved.targetCycles !== undefined) focusState.targetCycles = saved.targetCycles;
        if (saved.completedCycles !== undefined) focusState.completedCycles = saved.completedCycles;
        
        updateUIForMode(false);
        updateCycleTrackerUI();
        
        if (saved.running) {
            if (!focusState.frameId) { focusState.running = false; startFocusTimer(true); }
        } else {
            cancelAnimationFrame(focusState.frameId);
            focusState.frameId = null;
            document.getElementById('btnPause')?.classList.toggle('is-paused', focusState.remainingSeconds > 0 && focusState.remainingSeconds < focusState.totalSeconds);
        }
        renderFocusTimer();
    }
});

function updateUIForMode(resetTimer = true) {
    document.querySelectorAll('[data-mode]').forEach((item) => item.classList.toggle('active', item.dataset.mode === focusState.mode));
    if (focusState.mode === 'custom') {
        document.querySelectorAll('[data-mode]').forEach((item) => item.classList.remove('active'));
        document.getElementById('btnOpenCustomTimer')?.classList.add('active');
    } else {
        document.getElementById('btnOpenCustomTimer')?.classList.remove('active');
    }
    if (resetTimer) {
        focusState.remainingSeconds = focusState.totalSeconds;
        focusState.carriedSeconds = 0;
    }
}

function bindFocusControls() {
    document.querySelectorAll('[data-mode]').forEach((button) => {
        button.addEventListener('click', () => {
            if (focusState.running) {
                showToast('Pause or stop the current timer before switching modes.', 'warning');
                return;
            }

            document.querySelectorAll('[data-mode]').forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            document.getElementById('btnOpenCustomTimer')?.classList.remove('active');
            focusState.mode = button.dataset.mode;
            focusState.totalSeconds = Number(button.dataset.minutes) * 60;
            updateUIForMode(true);
            saveFocusTimer();
            renderFocusTimer();
        });
    });

    document.getElementById('btnOpenCustomTimer')?.addEventListener('click', openCustomTimer);
    document.getElementById('btnApplyCustomTime')?.addEventListener('click', applyCustomTime);
    document.getElementById('btnStart')?.addEventListener('click', startFocusTimer);
    document.getElementById('btnPause')?.addEventListener('click', pauseFocusTimer);
    document.getElementById('btnStop')?.addEventListener('click', stopFocusTimer);
    document.getElementById('btnReset')?.addEventListener('click', resetFocusTimer);

    setupSpinner('spinMinUp', 'spinMinDown', 'spinMin', 99);
    setupSpinner('spinSecUp', 'spinSecDown', 'spinSec', 59);

    document.getElementById('focusTaskSelect')?.addEventListener('change', (event) => {
        const label = event.currentTarget.options[event.currentTarget.selectedIndex]?.text || 'Select a task to tie this session to a real outcome.';
        document.getElementById('activeFocusTask').textContent = event.currentTarget.value ? label : 'Select a task to tie this session to a real outcome.';
    });

    document.getElementById('focusCycleSelect')?.addEventListener('change', (event) => {
        focusState.targetCycles = Number(event.currentTarget.value);
        focusState.completedCycles = 0;
        updateCycleTrackerUI();
        saveFocusTimer();
    });
}

function updateCycleTrackerUI() {
    const tracker = document.getElementById('focusCycleTracker');
    if (tracker) {
        if (focusState.targetCycles > 1) {
            tracker.textContent = `(${focusState.completedCycles}/${focusState.targetCycles} done)`;
        } else {
            tracker.textContent = '';
        }
    }
}

function saveFocusTimer() {
    if (window.TimerSync) {
        window.TimerSync.setState({
            source: 'focus',
            mode: focusState.mode,
            totalSeconds: focusState.totalSeconds,
            remainingSeconds: focusState.remainingSeconds,
            running: focusState.running,
            startedAt: focusState.startedAt,
            carriedSeconds: focusState.carriedSeconds,
            targetCycles: focusState.targetCycles,
            completedCycles: focusState.completedCycles
        });
    }
}

function setupSpinner(upId, downId, valueId, max) {
    document.getElementById(upId)?.addEventListener('click', () => changeSpinner(valueId, 1, max));
    document.getElementById(downId)?.addEventListener('click', () => changeSpinner(valueId, -1, max));
}

function changeSpinner(id, delta, max) {
    const node = document.getElementById(id);
    let value = Number(node.textContent);
    value += delta;

    if (value > max) {
        value = 0;
    }
    if (value < 0) {
        value = max;
    }

    node.textContent = String(value).padStart(2, '0');
}

function openCustomTimer() {
    if (focusState.running) {
        showToast('Pause or stop the current timer before setting a custom duration.', 'warning');
        return;
    }

    document.getElementById('spinMin').textContent = String(Math.floor(focusState.totalSeconds / 60)).padStart(2, '0');
    document.getElementById('spinSec').textContent = String(focusState.totalSeconds % 60).padStart(2, '0');
    openModal('customTimerModal');
}

function applyCustomTime() {
    const minutes = Number(document.getElementById('spinMin').textContent);
    const seconds = Number(document.getElementById('spinSec').textContent);
    if (minutes === 0 && seconds === 0) {
        showToast('Set a duration greater than zero.', 'warning');
        return;
    }

    document.querySelectorAll('[data-mode]').forEach((item) => item.classList.remove('active'));
    document.getElementById('btnOpenCustomTimer')?.classList.add('active');
    focusState.mode = 'custom';
    focusState.totalSeconds = minutes * 60 + seconds;
    updateUIForMode(true);
    closeModal('customTimerModal');
    saveFocusTimer();
    renderFocusTimer();
}

function startFocusTimer(skipSave = false) {
    if (focusState.running) {
        return;
    }

    focusState.running = true;
    if (skipSave !== true) {
        focusState.startedAt = Date.now();
        saveFocusTimer();
    }
    tickFocusTimer();
}

function tickFocusTimer() {
    if (!focusState.running) {
        return;
    }

    const elapsedSeconds = focusState.carriedSeconds + Math.floor((Date.now() - focusState.startedAt) / 1000);
    focusState.remainingSeconds = Math.max(0, focusState.totalSeconds - elapsedSeconds);
    renderFocusTimer();

    if (focusState.remainingSeconds === 0) {
        completeFocusTimer();
        return;
    }

    focusState.frameId = requestAnimationFrame(tickFocusTimer);
}

function pauseFocusTimer() {
    const btn = document.getElementById('btnPause');

    if (focusState.running) {
        /* === PAUSE === */
        focusState.running = false;
        focusState.carriedSeconds += Math.floor((Date.now() - focusState.startedAt) / 1000);
        cancelAnimationFrame(focusState.frameId);
        focusState.frameId = null;
        btn?.classList.add('is-paused');
        saveFocusTimer();
    } else if (focusState.carriedSeconds > 0 || focusState.remainingSeconds < focusState.totalSeconds) {
        /* === RESUME (mid-session) === */
        btn?.classList.remove('is-paused');
        focusState.startedAt = Date.now();
        startFocusTimer(true);
        saveFocusTimer();
    }
}

function stopFocusTimer() {
    focusState.running = false;
    focusState.remainingSeconds = focusState.totalSeconds;
    focusState.carriedSeconds = 0;
    cancelAnimationFrame(focusState.frameId);
    focusState.frameId = null;
    document.getElementById('btnPause')?.classList.remove('is-paused');
    saveFocusTimer();
    renderFocusTimer();
}

function resetFocusTimer() {
    stopFocusTimer();
    showToast('Timer reset.', 'success');
}

async function setModeAndStart(mode, minutes) {
    focusState.mode = mode;
    focusState.totalSeconds = minutes * 60;
    updateUIForMode(true);
    saveFocusTimer();
    renderFocusTimer();
    // Tiny delay to let UI render before auto-starting
    setTimeout(() => {
        startFocusTimer();
    }, 500);
}

async function processAutopilotCycle() {
    if (focusState.targetCycles <= 1) return;

    if (focusState.mode === 'pomodoro') {
        focusState.completedCycles++;
        updateCycleTrackerUI();

        if (focusState.completedCycles >= focusState.targetCycles) {
            showToast(`Autopilot complete! All ${focusState.targetCycles} cycles done.`, 'success');
            focusState.completedCycles = 0;
            updateCycleTrackerUI();
            saveFocusTimer();
            return;
        }

        const isHalfway = focusState.completedCycles === Math.floor(focusState.targetCycles / 2);
        if (isHalfway) {
            showToast('Halfway there! Take a long break.', 'success');
            setModeAndStart('long-break', 15);
        } else {
            showToast('Cycle complete! Take a short break.', 'success');
            setModeAndStart('short-break', 5);
        }
    } else if (focusState.mode === 'short-break' || focusState.mode === 'long-break') {
        showToast('Break over! Auto-starting next focus cycle.', 'success');
        setModeAndStart('pomodoro', 25);
    }
}

async function completeFocusTimer() {
    focusState.running = false;
    focusState.carriedSeconds = 0;
    cancelAnimationFrame(focusState.frameId);
    focusState.frameId = null;
    
    // Save state once here so it registers as "ended" before moving on
    saveFocusTimer();
    renderFocusTimer();

    try {
        await API.logSession({
            duration_minutes: focusState.totalSeconds / 60,
            session_type: (() => {
                if (focusState.mode === 'pomodoro') return 'pomodoro';
                if (focusState.mode === 'short-break' || focusState.mode === 'long-break') return 'break';
                return 'study'; // custom
            })(),
        });
    } catch (error) {
        // Non-blocking.
    }

    const taskSelect = document.getElementById('focusTaskSelect');
    if (taskSelect?.value) {
        try {
            await API.completeTask(Number(taskSelect.value), 1);
        } catch (error) {
            // Non-blocking.
        }
    }

    await Promise.all([loadFocusTasks(), loadFocusHeatmap(), loadSessionLog()]);
    
    if (focusState.targetCycles <= 1) {
        showToast('Focus session complete.', 'success');
    } else {
        processAutopilotCycle();
    }
}

function renderFocusTimer() {
    const minutes = String(Math.floor(focusState.remainingSeconds / 60)).padStart(2, '0');
    const seconds = String(focusState.remainingSeconds % 60).padStart(2, '0');

    // Update text display
    const display = document.getElementById('mainTimerDisplay');
    if (display) display.textContent = `${minutes}:${seconds}`;

    // Update SVG ring (r=104, circumference = 2 * PI * 104 ≈ 653.45)
    const arc = document.getElementById('focusRingArc');
    if (arc) {
        const circumference = 653.45;
        const progress = focusState.totalSeconds > 0
            ? (focusState.remainingSeconds / focusState.totalSeconds)
            : 0;
        arc.style.strokeDashoffset = circumference * (1 - progress);
        arc.classList.toggle('running', focusState.running);
    }

    // When running: after 5s idle → eyes follow the ring tip (seconds hand)
    if (focusState.running && (Date.now() - focusLastMouseMove) >= 5000) {
        const svg = document.querySelector('.focus-ring-svg');
        if (svg) {
            const r = svg.getBoundingClientRect();
            const cx = r.left + r.width / 2;
            const cy = r.top  + r.height / 2;
            const radius = (r.width / 240) * 104;
            const progress = focusState.totalSeconds > 0
                ? focusState.remainingSeconds / focusState.totalSeconds : 0;
            const angle = -Math.PI / 2 + progress * 2 * Math.PI;
            updateTimerEyes(cx + radius * Math.cos(angle), cy + radius * Math.sin(angle));
        }
    }

    // Canvas (hidden, kept for backward-compat only)
    const canvas = document.getElementById('focusTimerCanvas');
    if (!canvas || canvas.style.display === 'none') return;
    const context = canvas.getContext('2d');
    const center = canvas.width / 2;
    const radius = 176;
    const progressRatio = 1 - (focusState.remainingSeconds / focusState.totalSeconds);
    const startAngle = -Math.PI / 2;
    const endAngle = startAngle + progressRatio * Math.PI * 2;

    context.clearRect(0, 0, canvas.width, canvas.height);
    context.beginPath();
    context.arc(center, center, radius, 0, Math.PI * 2);
    context.strokeStyle = 'rgba(255,255,255,0.12)';
    context.lineWidth = 14;
    context.stroke();
    context.beginPath();
    context.arc(center, center, radius, startAngle, endAngle);
    context.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
    context.lineWidth = 14;
    context.lineCap = 'round';
    context.stroke();
}

/* ═══════════════════════════════════════════════════════════
   TIMER EYE TRACKING
   ═══════════════════════════════════════════════════════════ */
function updateTimerEyes(targetX, targetY) {
    document.querySelectorAll('.timer-eye').forEach(eye => {
        const rect = eye.getBoundingClientRect();
        const eyeX = rect.left + rect.width / 2;
        const eyeY = rect.top  + rect.height / 2;
        const dx   = targetX - eyeX;
        const dy   = targetY - eyeY;
        const angle = Math.atan2(dy, dx);
        const maxDist = (rect.width / 2) - 5;
        const dist = Math.min(maxDist, Math.hypot(dx, dy) * 0.15);
        const pupil = eye.querySelector('.timer-pupil');
        if (pupil) {
            pupil.style.transform = `translate(calc(-50% + ${Math.cos(angle) * dist}px), calc(-50% + ${Math.sin(angle) * dist}px))`;
        }
    });
}

/* Cursor tracking — always update eyes and reset idle timer */
document.addEventListener('mousemove', e => {
    focusLastMouseMove = Date.now();
    updateTimerEyes(e.clientX, e.clientY);
});

async function loadFocusTasks() {
    const select = document.getElementById('focusTaskSelect');
    try {
        const response = await API.getTasks('all');
        const tasks = (response.data || []).filter((task) => !Number(task.is_completed));
        select.innerHTML = ['<option value="">Select an incomplete task</option>'].concat(
            tasks.map((task) => `<option value="${task.id}">${escapeHTML(task.title)}</option>`)
        ).join('');
    } catch (error) {
        select.innerHTML = '<option value="">Unable to load tasks</option>';
    }
}

async function loadFocusHeatmap() {
    const grid = document.getElementById('focusHeatmap');
    grid.innerHTML = '<div class="skeleton" style="height:120px;"></div>';

    try {
        const response = await API.getHeatmap(new Date().getFullYear());
        const minutesByDate = Object.fromEntries((response.data || []).map((row) => [row.date, Number(row.minutes || 0)]));

        const cells = [];
        const today = new Date();
        const start = new Date();
        start.setDate(today.getDate() - 104);

        for (let cursor = new Date(start); cursor <= today; cursor.setDate(cursor.getDate() + 1)) {
            const key = cursor.toISOString().slice(0, 10);
            const value = minutesByDate[key] || 0;
            let background = 'rgba(var(--color-primary-rgb), 0.06)';
            if (value > 0) background = 'rgba(var(--color-primary-rgb), 0.16)';
            if (value > 30) background = 'rgba(var(--color-primary-rgb), 0.3)';
            if (value > 60) background = 'rgba(var(--color-primary-rgb), 0.48)';
            if (value > 120) background = 'rgba(var(--color-primary-rgb), 0.72)';

            cells.push(`<div class="heatmap-cell" style="background:${background};height:16px;border-radius:4px;" title="${key}: ${value} minutes"></div>`);
        }

        grid.innerHTML = cells.join('');
    } catch (error) {
        grid.innerHTML = '<div class="empty-state" style="min-height:120px;"><h3>Heatmap unavailable</h3><p>Focus history will appear here after you log sessions.</p></div>';
    }
}

async function loadSessionLog() {
    const log = document.getElementById('sessionLogContent');
    log.innerHTML = '<div class="skeleton" style="height:72px;"></div>';

    try {
        const response = await API.getSessions(8);
        const sessions = response.data || [];

        if (!sessions.length) {
            log.innerHTML = '<div class="empty-state" style="min-height:180px;"><h3>No sessions yet</h3><p>Start the timer to log your first focus block.</p></div>';
            return;
        }

        log.innerHTML = sessions.map((session) => `
            <article class="task-item">
                <div class="task-item__content">
                    <p class="task-item__title">${escapeHTML(session.session_type)} session</p>
                    <p class="task-item__meta">
                        <span>${session.duration_minutes} minutes</span>
                        <span>${new Date(session.created_at || session.session_date).toLocaleDateString()}</span>
                    </p>
                </div>
            </article>
        `).join('');
    } catch (error) {
        log.innerHTML = '<div class="empty-state" style="min-height:180px;"><h3>Sessions unavailable</h3><p>We could not load your recent timer history.</p></div>';
    }
}
