<?php
require_once __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/helpers/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Focus | Chronos</title>
    <meta name="description" content="Focus timer — stay on task with Pomodoro intervals.">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);if(t==='dark')document.documentElement.classList.add('dark');}());</script>
    <link rel="stylesheet" href="assets/css/theme-light.css">
    <link rel="stylesheet" href="assets/css/theme-dark.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
        /* ── SVG Ring Timer ── */
        .focus-ring-shell {
            position: relative;
            width: 280px;
            height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
            flex-shrink: 0;
        }
        .focus-ring-svg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
            overflow: visible;
        }
        .focus-ring-track {
            fill: none;
            stroke: rgba(var(--color-primary-rgb), 0.1);
            stroke-width: 10;
        }
        .focus-ring-arc {
            fill: none;
            stroke: var(--color-primary);
            stroke-width: 10;
            stroke-linecap: round;
            stroke-dasharray: 653.45;
            stroke-dashoffset: 0;
            transition: stroke-dashoffset 0.6s cubic-bezier(0.4,0,0.2,1);
            filter: drop-shadow(0 0 10px rgba(var(--color-primary-rgb), 0.7));
        }
        .focus-ring-center {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        .focus-time-num {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 3.5rem;
            font-weight: 900;
            letter-spacing: -0.06em;
            color: var(--color-primary);
            line-height: 1;
            filter: drop-shadow(0 0 20px rgba(var(--color-primary-rgb), 0.45));
        }
        .focus-time-sub {
            font-size: 10px;
            font-weight: 700;
            color: var(--color-primary);
            opacity: 0.65;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.18rem;
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* ── Glow pulse when running ── */
        .focus-ring-arc.running {
            animation: ring-pulse 2s ease-in-out infinite;
        }
        @keyframes ring-pulse {
            0%, 100% { filter: drop-shadow(0 0 10px rgba(var(--color-primary-rgb), 0.7)); }
            50%       { filter: drop-shadow(0 0 22px rgba(var(--color-primary-rgb), 1)); }
        }
        /* ── Timer Eyes (inside the ring) ── */
        .timer-eyes-row {
            position: relative;
            z-index: 3;
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: center;
        }
        .timer-eye {
            width: 36px;
            height: 36px;
            background: #ffffff;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 3px 12px rgba(0,0,0,0.4), inset 0 1px 4px rgba(0,0,0,0.12);
        }
        .timer-pupil {
            width: 14px;
            height: 14px;
            background: #1a0f14;
            border-radius: 50%;
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            transition: transform 0.07s linear;
        }
        .timer-pupil::after {
            content: '';
            width: 5px; height: 5px;
            background: rgba(255,255,255,0.8);
            border-radius: 50%;
            position: absolute;
            top: 2px; left: 3px;
        }
        /* ── Pause button highlighted state ── */
        #btnPause.is-paused {
            background: rgba(var(--color-primary-rgb), 0.15) !important;
            border-color: var(--color-primary) !important;
            color: var(--color-primary) !important;
            box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb), 0.2), 0 0 20px rgba(var(--color-primary-rgb), 0.3) !important;
        }
        /* ── Timer text (outside the ring) ── */
        .focus-time-num {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 3.5rem;
            font-weight: 900;
            letter-spacing: -0.06em;
            color: var(--color-primary);
            line-height: 1;
            filter: drop-shadow(0 0 20px rgba(var(--color-primary-rgb), 0.45));
            margin-top: 24px;
            text-align: center;
        }
        .focus-time-sub {
            font-size: 10px;
            font-weight: 700;
            color: var(--color-primary);
            opacity: 0.65;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.18rem;
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
        }
        /* ── Custom task select ── */
        .focus-task-select-wrap {
            position: relative;
            width: 100%;
        }
        .focus-task-select-wrap svg.chev {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-secondary);
            pointer-events: none;
        }
        #focusTaskSelect {
            width: 100%;
            background: rgba(var(--color-primary-rgb), 0.07);
            border: 1px solid rgba(var(--color-primary-rgb), 0.3);
            border-radius: 0.75rem;
            color: var(--color-text-primary);
            padding: 0.875rem 2.5rem 0.875rem 1.25rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            text-align: center;
        }
        #focusTaskSelect:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb), 0.15);
        }
        #focusTaskSelect option {
            background: #120c10;
            color: #e5e5e5;
            padding: 8px;
        }
    </style>
</head>
<body>
<div class="bg-mesh" aria-hidden="true"></div>
<div class="app-container">
    <?php include 'partials/sidebar.php'; ?>

    <main id="mainContentWrapper" class="content-wrapper" role="main">
        <div class="page-shell page-enter">
            <header class="page-header">
                <div>
                    <p class="page-header__eyebrow">Deep Focus</p>
                    <h1 class="page-header__title">Focus Mode.</h1>
                    <p class="page-header__subtitle">Select a task, set your timer, and stay in the zone.</p>
                </div>
            </header>

            <div class="focus-layout">
                <!-- Ritual Engine Zone -->
                <section class="panel-container sanctuary-surface" style="align-items:center; justify-content:center; padding: 40px;">
                    
                    <!-- Frequency Selector -->
                    <div class="pill-group" style="margin-bottom: 20px;">
                        <button class="pill-button active" data-mode="pomodoro" data-minutes="25">Focus (25m)</button>
                        <button class="pill-button" data-mode="short-break" data-minutes="5">Short Break</button>
                        <button class="pill-button" data-mode="long-break" data-minutes="15">Long Break</button>
                        <button id="btnOpenCustomTimer" class="btn btn-icon" title="Custom Duration">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>
                    </div>

                    <div style="margin-bottom: 40px; display: flex; align-items: center; gap: 10px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-primary);">
                        <span>Autopilot Cycles:</span>
                        <select id="focusCycleSelect" style="background: rgba(var(--color-primary-rgb),0.1); color: var(--color-primary); border: 1px solid rgba(var(--color-primary-rgb),0.3); border-radius: 6px; padding: 4px 8px; font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 800; outline: none; cursor: pointer;">
                            <option value="1">Off</option>
                            <option value="2">2 Cycles</option>
                            <option value="4">4 Cycles</option>
                            <option value="6">6 Cycles</option>
                            <option value="8">8 Cycles</option>
                        </select>
                        <span id="focusCycleTracker" style="opacity: 0.6; margin-left: 5px;"></span>
                    </div>

                    <!-- SVG Ring + timer display wrapped in column -->
                    <div style="display:flex;flex-direction:column;align-items:center;">
                        <div class="focus-ring-shell">
                            <svg class="focus-ring-svg" viewBox="0 0 240 240" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle class="focus-ring-track" cx="120" cy="120" r="104"/>
                                <circle class="focus-ring-arc" id="focusRingArc" cx="120" cy="120" r="104"/>
                            </svg>
                            <!-- Monster Eyes inside the ring -->
                            <div class="timer-eyes-row" id="focusTimerEyes">
                                <div class="timer-eye"><div class="timer-pupil" id="focusPupilL"></div></div>
                                <div class="timer-eye"><div class="timer-pupil" id="focusPupilR"></div></div>
                            </div>
                            <!-- Canvas kept hidden for JS backward compatibility -->
                            <canvas id="focusTimerCanvas" width="440" height="440" style="display:none;"></canvas>
                            <input type="text" id="mainTimerInput" value="25:00" autocomplete="off"
                                style="position:absolute;opacity:0;pointer-events:none;width:1px;height:1px;">
                        </div>
                        <!-- Timer text OUTSIDE the ring -->
                        <div id="mainTimerDisplay" class="focus-time-num">25:00</div>
                        <div id="activeFocusTask" class="focus-time-sub">Select a Task</div>
                    </div>

                    <!-- Manifestation Controls -->
                    <div style="display:flex; gap: 32px; align-items:center; margin-top: 40px;">
                        <button id="btnStop" class="btn btn-icon" style="width: 64px; height: 64px; border-radius: 50%;"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="6" width="12" height="12"/></svg></button>
                        <button id="btnStart" class="btn btn-primary" style="width: 88px; height: 88px; border-radius: 50%;"><svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg></button>
                        <button id="btnPause" class="btn btn-icon" style="width: 64px; height: 64px; border-radius: 50%;"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg></button>
                    </div>

                    <div style="margin-top: 36px; width: 100%; max-width: 400px;">
                        <div class="focus-task-select-wrap">
                            <select id="focusTaskSelect">
                                <option value="">— Select a Task to Focus On —</option>
                            </select>
                            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </div>
                    </div>
                </section>

                <!-- Engine Status -->
                <aside class="panel-container">
                    <section class="sanctuary-surface section-card">
                        <p class="section-kicker">Activity</p>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                            <h3 class="section-title">Productivity</h3>
                            <div style="display:flex; align-items:center; gap:4px; font-size:10px; opacity:0.3; font-weight: 800;">
                                <span>LOW</span>
                                <div style="width:8px; height:8px; border-radius:2px; background:var(--color-surface);"></div>
                                <div style="width:8px; height:8px; border-radius:2px; background:var(--color-primary);"></div>
                                <span>HIGH</span>
                            </div>
                        </div>
                        <div class="heatmap-grid" id="focusHeatmap"></div>
                    </section>

                    <section class="sanctuary-surface section-card" style="flex: 1; display:flex; flex-direction:column;">
                        <p class="section-kicker">History</p>
                        <h3 class="section-title" style="margin-bottom: 20px;">Session Logs</h3>
                        <div id="sessionLogContent" class="panel-scroll"></div>
                    </section>
                </aside>
            </div>
        </div>
    </main>
</div>

<!-- Custom Timer Modal -->
<div id="customTimerModal" class="modal-overlay">
    <div class="modal-container sanctuary-surface" style="max-width:340px; padding: 40px;">
        <div class="modal-header" style="border: none;">
            <h3 class="section-title" style="color: var(--color-primary);">Custom Duration</h3>
            <button class="modal-close" onclick="closeModal('customTimerModal')">&#215;</button>
        </div>
        <div class="modal-body">
            <div style="display:flex; justify-content:center; align-items:center; gap:16px; padding: 24px;">
                <div style="text-align:center;">
                    <button class="btn btn-icon" id="spinMinUp">&#9650;</button>
                    <div style="font-size:40px; font-weight:900; color:var(--color-primary);" id="spinMin">25</div>
                    <button class="btn btn-icon" id="spinMinDown">&#9660;</button>
                    <div style="font-size:10px;opacity:0.4;font-family:monospace;text-transform:uppercase;letter-spacing:2px;margin-top:4px;">min</div>
                </div>
                <div style="font-size:32px; font-weight:900; opacity:0.2;">:</div>
                <div style="text-align:center;">
                    <button class="btn btn-icon" id="spinSecUp">&#9650;</button>
                    <div style="font-size:40px; font-weight:900; color:var(--color-primary);" id="spinSec">00</div>
                    <button class="btn btn-icon" id="spinSecDown">&#9660;</button>
                    <div style="font-size:10px;opacity:0.4;font-family:monospace;text-transform:uppercase;letter-spacing:2px;margin-top:4px;">sec</div>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="border: none; display:flex; gap: 12px;">
            <button class="btn btn-secondary" style="flex:1;" onclick="closeModal('customTimerModal')">Cancel</button>
            <button class="btn btn-primary" style="flex:1;" id="btnApplyCustomTime">Apply</button>
        </div>
    </div>
</div>



<script src="assets/js/utils.js"></script>
<script src="assets/js/api.js"></script>
<script src="assets/js/store.js"></script>
<script src="assets/js/eyes.js"></script>
<script src="assets/js/focus.js"></script>
</body>
</html>
