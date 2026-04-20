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
    <title>Dashboard | Chronos</title>
    <meta name="description" content="Your Chronos control board — tasks, calendar, focus timer, and activity stats at a glance.">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <!-- Theme: set BOTH data-theme AND .dark class immediately to prevent FOUC -->
    <script>
    (function () {
        var t = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', t);
        if (t === 'dark') document.documentElement.classList.add('dark');
    }());
    </script>
    <link rel="stylesheet" href="assets/css/theme-light.css">
    <link rel="stylesheet" href="assets/css/theme-dark.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <!-- Tailwind CDN: required for the bento grid layout (grid-cols-12, col-span-*, flex utilities, etc.) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <style>
        /* ── Dashboard-specific overrides ─────────────────────── */
        body { background: #f8f7fa; overflow-x: hidden; }
        html.dark body { background: #08070b; overflow-x: hidden; }

        .glass-bd {
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }
        html.dark .glass-bd { background: rgba(12,11,16,0.65); }

        /* Task list injected by dashboard.js */
        #miniTaskList .task-item {
            display: flex; align-items: flex-start; gap: 14px;
            padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,0.05);
            cursor: pointer; transition: border-color 0.25s;
        }
        html.dark #miniTaskList .task-item { border-bottom-color: rgba(255,255,255,0.05); }
        #miniTaskList .task-item:hover { border-bottom-color: rgba(225,29,72,0.35); }
        #miniTaskList .task-item:last-child { border-bottom: none; }
        #miniTaskList .task-item__title { font-size: 0.875rem; margin: 0; color: #334155; }
        html.dark #miniTaskList .task-item__title { color: #f1f5f9; }
        #miniTaskList .task-item.is-complete .task-item__title { text-decoration: line-through; opacity: 0.4; }
        #miniTaskList .task-item__meta { display: flex; gap: 8px; margin-top: 4px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; font-family: 'JetBrains Mono', monospace; }
        #miniTaskList .sanctuary-checkbox {
            appearance: none; width: 18px; height: 18px; min-width: 18px; border-radius: 2px;
            border: 1px solid rgba(225,29,72,0.45); background: transparent; cursor: pointer;
            display: flex; align-items: center; justify-content: center; transition: all 0.2s; margin-top: 2px;
        }
        #miniTaskList .sanctuary-checkbox:hover { border-color: #e11d48; background: rgba(225,29,72,0.08); }
        #miniTaskList .sanctuary-checkbox:checked { border-color: #e11d48; background: rgba(225,29,72,0.1); }
        #miniTaskList .sanctuary-checkbox:checked::after { content: '✔'; color: #e11d48; font-size: 11px; }

        /* Reminders injected into #remindersList */
        #remindersList .task-item {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 6px 0; font-size: 0.82rem;
        }
        #remindersList .task-item__content { flex: 1; }
        #remindersList .task-item__title { font-size: 0.8rem; margin: 0; color: #334155; }
        html.dark #remindersList .task-item__title { color: #cbd5e1; }
        #remindersList .task-item__meta { font-size: 9px; color: #94a3b8; font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 2px; }

        /* Team activity */
        #teamActivityList .task-item { display: flex; gap: 12px; padding: 8px 0; align-items: flex-start; border-bottom: 1px solid rgba(0,0,0,0.04); }
        html.dark #teamActivityList .task-item { border-bottom-color: rgba(255,255,255,0.04); }
        #teamActivityList .task-item:last-child { border-bottom: none; }
        #teamActivityList .task-item__content { flex: 1; }
        #teamActivityList .task-item__title { font-size: 0.82rem; margin: 0; color: #1e293b; font-weight: 600; }
        html.dark #teamActivityList .task-item__title { color: #e2e8f0; }
        #teamActivityList .task-item__meta { font-size: 9px; color: #94a3b8; font-family: 'JetBrains Mono', monospace; display: flex; gap: 8px; margin-top: 3px; }
        #teamActivityList .friend-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(0,0,0,0.08); }

        /* Calendar */
        .cal-board-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 3px; margin-top: 8px; }
        .calendar-label { aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center; font-family: 'JetBrains Mono', monospace; font-size: 9px; text-transform: uppercase; color: #94a3b8; }
        html.dark .calendar-label { color: #4b5563; }
        #calGrid button.cal-day {
            aspect-ratio: 1/1; display: flex; flex-direction: column; align-items: center; justify-content: center;
            font-family: 'JetBrains Mono', monospace; font-size: 11px; background: none; border: none; padding: 0;
            color: #334155; cursor: pointer; border-radius: 4px; transition: background 0.15s;
            position: relative;
        }
        html.dark #calGrid button.cal-day { color: #e2e8f0; }
        #calGrid button.cal-day:hover { background: rgba(225,29,72,0.1); }
        #calGrid button.cal-day.is-outside { opacity: 0.25; pointer-events: none; }
        #calGrid button.cal-day.today { background: rgba(225,29,72,0.12); color: #e11d48; font-weight: 700; }
        #calGrid .cal-day__markers { display: flex; gap: 2px; margin-top: 2px; height: 3px; }
        #calGrid .calendar-dot { width: 4px; height: 4px; border-radius: 50%; }
        #calGrid .calendar-count { font-size: 7px; color: #94a3b8; }
        .cal-day__number { font-size: 11px; line-height: 1; }

        /* Input focus crimson border */
        .crimson-border-focus:focus-within {
            border-bottom: 2px solid #e11d48;
            box-shadow: 0 4px 20px -2px rgba(225,29,72,0.25);
        }

        /* Content wrapper — margin-left is controlled EXCLUSIVELY by sidebar.php JS */
        .dash-content { min-height: 100vh; padding: 2rem; transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); min-width: 0; margin-left: 256px; }
        @media (max-width: 768px) { .dash-content { margin-left: 0 !important; } }
        .dash-content { visibility: visible; }

        /* ── Dashboard Mini Timer Eyes ── */
        .mini-timer-eye {
            width: 20px; height: 20px;
            background: #ffffff;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.35), inset 0 1px 3px rgba(0,0,0,0.1);
        }
        .mini-timer-pupil {
            width: 8px; height: 8px;
            background: #1a0f14;
            border-radius: 50%;
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            transition: transform 0.07s linear;
        }
        .mini-timer-pupil::after {
            content: '';
            width: 3px; height: 3px;
            background: rgba(255,255,255,0.8);
            border-radius: 50%;
            position: absolute;
            top: 1px; left: 2px;
        }
        /* Pause button paused state for mini timer */
        #miniTimerPause.is-paused {
            border-color: #e11d48 !important;
            color: #e11d48 !important;
            background: rgba(225,29,72,0.1) !important;
            box-shadow: 0 0 0 2px rgba(225,29,72,0.2) !important;
        }

        /* Day tasks popover */
        #dayTasksPanel {
            position: fixed; top: 0; right: -420px; width: 410px; height: 100vh; z-index: 10000;
            background: #fff; border-left: 1px solid rgba(0,0,0,0.07);
            box-shadow: -20px 0 60px rgba(0,0,0,0.12); display: flex; flex-direction: column;
            transition: right 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        html.dark #dayTasksPanel { background: #0d0c11; border-left-color: rgba(255,255,255,0.06); }
        #dayTasksPanel.open { right: 0; }
        #dayTasksPanel .dtp-header { padding: 20px 24px 14px; border-bottom: 1px solid rgba(0,0,0,0.06); display:flex; align-items:center; justify-content:space-between; }
        html.dark #dayTasksPanel .dtp-header { border-bottom-color: rgba(255,255,255,0.05); }
        #dayTasksPanel .dtp-title { font-family:'Space Grotesk',sans-serif; font-size:1rem; font-weight:700; color:#1e293b; margin:0; }
        html.dark #dayTasksPanel .dtp-title { color:#f1f5f9; }
        #dayTasksPanel .dtp-close { background:none; border:none; cursor:pointer; color:#94a3b8; font-size:22px; line-height:1; padding:4px; }
        #dayTasksPanel .dtp-body { flex:1; overflow-y:auto; padding:16px 24px; }
        #dayTasksPanel .dtp-add-row { padding:14px 24px; border-top:1px solid rgba(0,0,0,0.06); display:flex; gap:10px; }
        html.dark #dayTasksPanel .dtp-add-row { border-top-color: rgba(255,255,255,0.05); }
        #dayTasksPanel .dtp-task-item { display:flex; align-items:flex-start; gap:12px; padding:10px 0; border-bottom:1px solid rgba(0,0,0,0.04); }
        html.dark #dayTasksPanel .dtp-task-item { border-bottom-color:rgba(255,255,255,0.04); }
        #dayTasksPanel .dtp-task-item:last-child { border-bottom:none; }
        #dayTasksPanel .dtp-task-name { font-size:0.85rem; color:#334155; margin:0; font-weight:500; }
        html.dark #dayTasksPanel .dtp-task-name { color:#e2e8f0; }
        #dayTasksPanel .dtp-task-meta { font-size:9px; color:#94a3b8; font-family:'JetBrains Mono',monospace; text-transform:uppercase; letter-spacing:0.06em; margin-top:3px; }
        #dayTasksPanelOverlay { position:fixed; inset:0; background:rgba(0,0,0,0.3); z-index:9999; display:none; backdrop-filter:blur(2px); }
        #dayTasksPanelOverlay.open { display:block; }

        /* Task modal (old CSS classes) */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 9999; opacity: 0; pointer-events: none; transition: opacity 0.25s; }
        .modal-overlay.active { opacity: 1; pointer-events: all; }
        .modal-container { background: #fff; border-radius: 20px; padding: 36px; width: 100%; max-width: 480px; box-shadow: 0 40px 80px rgba(0,0,0,0.18); }
        html.dark .modal-container { background: #111015; border: 1px solid rgba(255,255,255,0.06); }
        .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; line-height: 1; }
        .modal-footer { display: flex; gap: 12px; margin-top: 28px; }

        /* Skeleton loader */
        .skeleton { border-radius: 8px; background: linear-gradient(90deg, rgba(0,0,0,0.06) 25%, rgba(0,0,0,0.1) 50%, rgba(0,0,0,0.06) 75%); background-size: 200% 100%; animation: skeletonWave 1.5s infinite; }
        html.dark .skeleton { background: linear-gradient(90deg, rgba(255,255,255,0.04) 25%, rgba(255,255,255,0.08) 50%, rgba(255,255,255,0.04) 75%); background-size: 200% 100%; }
        @keyframes skeletonWave { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        /* Empty state */
        .empty-state { text-align: center; padding: 24px; opacity: 0.6; }
        .empty-state h3 { font-size: 1rem; font-weight: 700; margin: 0 0 6px; }
        .empty-state p { font-size: 0.82rem; margin: 0; }

        /* Status chips */
        .status-chip { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; background: rgba(225,29,72,0.1); color: #e11d48; }
        .status-chip--danger { background: rgba(239,68,68,0.1); color: #ef4444; }
        .status-chip--warning { background: rgba(245,158,11,0.1); color: #f59e0b; }
    </style>
    <!-- Pre-paint sidebar state: read saved collapse from localStorage and apply before first paint -->
    <script>
    (function(){
        var collapsed = localStorage.getItem('sidebarCollapsed') === '1';
        var isMobile  = window.innerWidth < 768;
        var ml = isMobile ? '0px' : (collapsed ? '68px' : '256px');
        // Inject a <style> rule so .dash-content gets the right margin immediately
        var s = document.createElement('style');
        s.id  = 'sidebar-margin-init';
        s.textContent = '.dash-content { margin-left: ' + ml + ' !important; transition: none !important; }';
        document.head.appendChild(s);
        // Remove the override after first paint so transitions work normally
        requestAnimationFrame(function(){ requestAnimationFrame(function(){
            var el = document.getElementById('sidebar-margin-init');
            if (el) el.remove();
        }); });
    })();
    </script>
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<main class="dash-content" id="mainContentWrapper">

    <!-- ── HEADER: Clock & Greeting ─────────────────────────── -->
    <header class="flex flex-col lg:flex-row items-center justify-between gap-6 mb-10">
        <div class="w-full lg:w-1/4 text-center lg:text-left">
            <p class="font-headline text-xs uppercase tracking-widest mb-1" style="color:#94a3b8;">
                <span id="timeGreeting">Good morning</span>, <span id="welcomeUsername" class="text-slate-700 dark:text-slate-200 font-semibold">User</span>
            </p>
            <p id="dashboardContext" class="font-technical text-[10px] text-rose-600 dark:text-rose-500 uppercase tracking-[0.18em]">Session: Absolute Focus</p>
        </div>

        <div class="w-full lg:w-2/4 text-center">
            <div id="clockTime" class="font-headline font-bold tracking-tighter text-6xl text-slate-800 dark:text-slate-100" style="filter:drop-shadow(0 0 12px rgba(225,29,72,0.5));">00:00</div>
            <div class="flex items-center justify-center gap-1 font-technical text-[10px] uppercase tracking-[0.4em] text-slate-400 dark:text-slate-600 mt-2">
                <span id="clockDay">Monday</span><span>,</span><span id="clockDate">Jan 1</span>
            </div>
        </div>

        <!-- Empty w-1/4 to preserve structural balancing -->
        <div class="w-full lg:w-1/4 flex justify-center lg:justify-end">
        </div>
    </header>

    <!-- ── BENTO GRID ────────────────────────────────────────── -->
    <div class="grid grid-cols-12 gap-5 w-full max-w-[1680px] mx-auto">

        <!-- ╔═ COLUMN 1 (4/12) — Calendar + Transmissions ═══════╗ -->
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-5">

            <!-- Calendar Widget -->
            <div class="glass-bd rounded-xl border border-slate-200 dark:border-white/5 shadow-xl dark:shadow-2xl p-6
                        hover:border-rose-200 dark:hover:border-rose-600/30 transition-all duration-500">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-headline text-xs font-bold tracking-widest uppercase text-rose-600 dark:text-rose-500">Calendar</h3>
                    <div class="flex items-center gap-2">
                        <span id="calMonthYear" class="font-technical text-[10px] uppercase text-slate-400 dark:text-slate-600">Loading…</span>
                        <div class="flex gap-0.5">
                            <button id="calPrev" class="w-6 h-6 rounded flex items-center justify-center bg-transparent border-0 cursor-pointer text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-white/5 transition-colors">
                                <span class="material-symbols-outlined" style="font-size:14px;">chevron_left</span>
                            </button>
                            <button id="calNext" class="w-6 h-6 rounded flex items-center justify-center bg-transparent border-0 cursor-pointer text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-white/5 transition-colors">
                                <span class="material-symbols-outlined" style="font-size:14px;">chevron_right</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Weekday headers (static) -->
                <div class="cal-board-grid">
                    <?php foreach (['S','M','T','W','T','F','S'] as $d): ?>
                    <div class="calendar-label"><?= $d ?></div>
                    <?php endforeach; ?>
                </div>
                <!-- Day cells (JS rendered into this) -->
                <div id="calGrid" class="cal-board-grid"></div>

                <!-- Upcoming reminders from dashboard.js loadDashboardReminders() -->
                <div id="remindersList" class="mt-4 pt-4 border-t border-slate-100 dark:border-white/5">
                    <div class="flex items-center gap-3">
                        <div class="w-1 h-8 rounded-full bg-rose-600" style="filter:drop-shadow(0 0 6px rgba(225,29,72,0.5));"></div>
                        <div>
                            <p class="text-[10px] font-headline font-bold uppercase text-slate-700 dark:text-slate-200">No deadlines yet</p>
                            <p class="text-[9px] font-technical text-slate-400">Your upcoming schedule looks clear</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ╔═ COLUMNS 2+3 (8/12) — 2×2 sub-grid ═══════════════════╗ -->
        <div class="col-span-12 lg:col-span-8 grid grid-cols-8 gap-5">

            <!-- ROW A — To-Do (5) + Focus Timer (3) -->

            <!-- Tasks Widget -->
            <div class="col-span-8 md:col-span-5 bg-white dark:bg-[#0c0b10] rounded-xl border border-slate-200 dark:border-white/5 shadow-xl dark:shadow-2xl p-7 relative overflow-hidden group
                        hover:border-rose-300 dark:hover:border-rose-600/40 transition-all duration-300">
                <div class="absolute top-0 right-0 w-36 h-36 bg-rose-600/10 blur-[80px] rounded-full pointer-events-none"></div>

                <div class="flex items-center justify-between mb-5 relative z-10">
                    <h3 class="font-headline text-xs font-bold tracking-widest uppercase text-rose-600 dark:text-rose-500">TO-DO</h3>
                    <select id="quickTaskFilter" class="bg-transparent border-0 font-technical text-[10px] uppercase text-slate-400 focus:ring-0 outline-none cursor-pointer">
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="all">All Pending</option>
                    </select>
                </div>

                <!-- JS-rendered task list -->
                <div id="miniTaskList" class="relative z-10" style="min-height:160px;">
                    <div class="skeleton" style="height:44px;margin-bottom:10px;"></div>
                    <div class="skeleton" style="height:44px;margin-bottom:10px;"></div>
                    <div class="skeleton" style="height:44px;"></div>
                </div>

                <!-- Quick-add input -->
                <div class="mt-5 relative z-10">
                    <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-white/8
                                hover:border-rose-300 dark:hover:border-rose-600/40 transition-all duration-200
                                focus-within:border-rose-400 dark:focus-within:border-rose-500 focus-within:shadow-md">
                        <input id="quickTaskInput" type="text" placeholder="Add a new task..."
                            class="flex-1 bg-transparent border-0 font-technical text-xs focus:ring-0 outline-none placeholder:text-slate-300 dark:placeholder:text-slate-700 text-slate-700 dark:text-slate-200"
                            autocomplete="off"/>
                        <!-- Calendar date-picker icon -->
                        <button id="quickTaskDateToggle" title="Pick deadline date"
                            class="flex items-center justify-center w-6 h-6 rounded-md transition-all border-0 bg-transparent cursor-pointer
                                   text-slate-300 dark:text-slate-600 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                            <span class="material-symbols-outlined" style="font-size:14px;">calendar_today</span>
                        </button>
                        <!-- Flag icon -->
                        <button id="quickTaskFlag" title="Flag as high priority"
                            class="flex items-center justify-center w-6 h-6 rounded-md transition-all border-0 bg-transparent cursor-pointer
                                   text-slate-300 dark:text-slate-600 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                <line x1="4" y1="22" x2="4" y2="15"/>
                            </svg>
                        </button>
                        <!-- Submit plus button -->
                        <button id="quickTaskSubmit" title="Add Task"
                            class="flex items-center justify-center w-6 h-6 rounded-md transition-all border-0 bg-transparent cursor-pointer
                                   text-slate-300 dark:text-slate-600 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                            <span class="material-symbols-outlined" style="font-size:16px;">add</span>
                        </button>
                        <input type="hidden" id="quickTaskDate" value="">
                    </div>
                    <!-- Date badge -->
                    <div id="quickTaskDateBadge" class="hidden items-center gap-1.5 mt-1.5 pl-1">
                        <span class="material-symbols-outlined text-rose-500" style="font-size:11px;">event</span>
                        <span class="font-technical text-[9px] text-rose-600 dark:text-rose-400 uppercase tracking-wider" id="quickTaskDateLabel"></span>
                        <button onclick="clearQuickTaskDate()" class="text-slate-400 hover:text-rose-500 border-0 bg-transparent cursor-pointer p-0 leading-none" style="font-size:11px;">&#x2715;</button>
                    </div>
                </div>
            </div>

            <!-- Focus Timer Widget -->
            <div class="col-span-8 md:col-span-3 glass-bd rounded-xl border border-rose-600/20 shadow-xl dark:shadow-2xl px-5 py-5 relative overflow-hidden
                        hover:border-rose-400/50 dark:hover:border-rose-500/50 transition-all duration-300 flex flex-col items-center">

                <p class="font-technical text-[10px] text-rose-600 dark:text-rose-500 uppercase tracking-[0.3em] mb-0.5 relative z-10">Focus Timer</p>
                <h4 id="miniTimerDisplay" class="font-headline font-bold text-4xl text-slate-800 dark:text-slate-100 tracking-tighter relative z-10 mb-0"
                    style="filter:drop-shadow(0 0 12px rgba(225,29,72,0.5));">25:00</h4>

                <!-- Timer ring -->
                <div class="relative w-32 h-32 flex items-center justify-center my-3 z-10">
                    <svg id="miniTimerRingSvg" class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 192 192">
                        <circle cx="96" cy="96" r="84" fill="none" class="text-slate-200 dark:text-white/5" stroke="currentColor" stroke-width="4"/>
                        <circle id="miniTimerArc" cx="96" cy="96" r="84" fill="none" class="text-rose-600" stroke="currentColor" stroke-width="6" stroke-dasharray="527.8" stroke-dashoffset="0" stroke-linecap="round"/>
                    </svg>
                    <canvas id="miniTimerCanvas" width="300" height="300" class="absolute inset-0 w-full h-full opacity-0" aria-hidden="true"></canvas>
                    <div class="relative z-10 text-center flex gap-3 items-center justify-center">
                        <div class="mini-timer-eye" id="miniEyeL"><div class="mini-timer-pupil"></div></div>
                        <div class="mini-timer-eye" id="miniEyeR"><div class="mini-timer-pupil"></div></div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="w-full flex flex-col gap-1.5 relative z-10">
                    <button id="miniTimerStart"
                        class="w-full py-1.5 font-headline font-bold uppercase tracking-widest text-[10px] rounded cursor-pointer
                               bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-600/30
                               text-rose-600 dark:text-rose-500 hover:bg-rose-600 hover:text-white hover:border-rose-600
                               transition-all hover:shadow-[0_0_20px_rgba(225,29,72,0.4)]">
                        Start Focus
                    </button>
                    <div class="grid grid-cols-2 gap-1.5">
                        <button id="miniTimerPause"
                            class="py-1 font-headline font-bold uppercase tracking-widest text-[9px] rounded cursor-pointer border
                                   border-slate-200 dark:border-white/10 text-slate-400 dark:text-slate-600
                                   hover:border-rose-400 hover:text-rose-500 transition-all bg-transparent">
                            Pause
                        </button>
                        <button id="miniTimerStop"
                            class="py-1 font-headline font-bold uppercase tracking-widest text-[9px] rounded cursor-pointer border
                                   border-slate-200 dark:border-white/10 text-slate-400 dark:text-slate-600
                                   hover:border-rose-400 hover:text-rose-500 transition-all bg-transparent">
                            Stop
                        </button>
                    </div>
                </div>

                <!-- Daily Progress -->
                <div class="w-full mt-3 relative z-10">
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-technical text-[10px] uppercase text-slate-400 dark:text-slate-600">Daily Progress</span>
                        <span id="energyPct" class="font-technical text-[10px] text-rose-600 dark:text-rose-500">0%</span>
                    </div>
                    <div class="w-full h-1 bg-slate-200 dark:bg-white/5 rounded-full overflow-hidden">
                        <div id="energyBar" class="h-full bg-gradient-to-r from-rose-600 via-rose-500 to-purple-600 transition-all" style="width:0%;filter:drop-shadow(0 0 4px rgba(225,29,72,0.4));"></div>
                    </div>
                </div>

                <div class="absolute -bottom-8 -right-8 w-32 h-32 bg-purple-600/10 dark:bg-purple-900/10 blur-[70px] rounded-full pointer-events-none"></div>
            </div>

            <!-- ROW B — Team Activity (5) + Music (3) -->

            <!-- Team Activity -->
            <div class="col-span-8 md:col-span-5 glass-bd rounded-xl border border-slate-200 dark:border-white/5 shadow-xl dark:shadow-2xl p-6
                        hover:border-rose-200 dark:hover:border-rose-600/30 transition-all duration-300">
                <h3 class="font-headline text-xs font-bold tracking-widest uppercase text-rose-600 dark:text-rose-500 mb-4">Team Activity</h3>
                <div id="teamActivityList">
                    <div class="skeleton" style="height:56px;margin-bottom:12px;"></div>
                    <div class="skeleton" style="height:56px;"></div>
                </div>
            </div>

            <!-- Now Playing (YouTube Embed) -->
            <div class="col-span-8 md:col-span-3 glass-bd rounded-xl border border-slate-200 dark:border-white/5 shadow-xl dark:shadow-2xl p-5 overflow-hidden relative group
                        hover:border-rose-300 dark:hover:border-rose-600/40 transition-all duration-300" id="musicWidget">
                <div class="flex items-center justify-between mb-3 relative z-10">
                    <h3 class="font-headline text-xs font-bold tracking-widest uppercase text-rose-600 dark:text-rose-500">Now Playing</h3>
                    <a href="https://music.youtube.com/playlist?list=PLoEBBv9er0BNTHoXJuqlK7EWLsVqmHGxo" target="_blank" rel="noopener"
                        class="flex items-center gap-1.5 text-[10px] font-technical text-slate-400 dark:text-slate-600 hover:text-red-500 transition-colors">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.376 0 0 5.376 0 12s5.376 12 12 12 12-5.376 12-12S18.624 0 12 0zm4.594 13.55l-6.363 3.664A1.8 1.8 0 0 1 7.5 15.658V8.342a1.8 1.8 0 0 1 2.731-1.537l6.363 3.658a1.8 1.8 0 0 1 0 3.087z"/></svg>
                        YT Music
                    </a>
                </div>
                <div class="relative z-10 rounded-xl overflow-hidden" style="height:175px;">
                    <iframe
                        src="https://www.youtube.com/embed/videoseries?list=PLoEBBv9er0BNTHoXJuqlK7EWLsVqmHGxo&controls=1&modestbranding=1&rel=0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen loading="lazy"
                        style="width:100%;height:100%;border:none;border-radius:10px;"
                        title="Music Playlist"
                    ></iframe>
                </div>
                <a href="https://music.youtube.com/playlist?list=PLoEBBv9er0BNTHoXJuqlK7EWLsVqmHGxo" target="_blank" rel="noopener"
                    class="mt-2 relative z-10 flex items-center justify-center gap-1.5 w-full py-1.5 rounded-lg
                           bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-600/20
                           text-red-600 dark:text-red-500 text-[10px] font-bold hover:bg-red-600 hover:text-white hover:border-red-600 transition-all">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.376 0 0 5.376 0 12s5.376 12 12 12 12-5.376 12-12S18.624 0 12 0zm4.594 13.55l-6.363 3.664A1.8 1.8 0 0 1 7.5 15.658V8.342a1.8 1.8 0 0 1 2.731-1.537l6.363 3.658a1.8 1.8 0 0 1 0 3.087z"/></svg>
                    Open Playlist
                </a>
            </div>

        </div><!-- /col 2+3 -->

    </div><!-- /grid -->

    <!-- ── FOOTER STATS ───────────────────────────────────────── -->
    <footer class="mt-8 flex flex-col md:flex-row gap-5 w-full max-w-[1680px] mx-auto pb-10">

        <div class="flex-1 glass-bd rounded-xl border-b-2 border-rose-600/50 shadow-xl dark:shadow-2xl p-6 flex items-center justify-between">
            <div>
                <p class="font-technical text-[10px] uppercase tracking-[0.25em] text-slate-400 dark:text-slate-600">Focus Minutes (7d)</p>
                <p class="font-headline font-bold text-2xl text-slate-800 dark:text-slate-100 mt-1">
                    <span id="statFocusMinutes">—</span><span class="text-sm font-light text-rose-600 dark:text-rose-500 ml-1">min</span>
                </p>
                <p id="statFocusMeta" class="font-technical text-[9px] text-slate-400 mt-1">Loading…</p>
            </div>
            <div class="flex items-end gap-0.5 h-12">
                <?php foreach ([1,2,3,4,3,4,5] as $h): ?>
                <div class="w-2 bg-rose-<?= [200,300,400,500,400,500,600][$h-1] ?? 500 ?> dark:bg-rose-<?= [950,900,800,700,800,700,600][$h-1] ?? 700 ?> rounded-t-sm" style="height:<?= $h*20 ?>%"></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex-1 glass-bd rounded-xl border-b-2 border-purple-600/50 shadow-xl dark:shadow-2xl p-6 flex items-center justify-between">
            <div>
                <p class="font-technical text-[10px] uppercase tracking-[0.25em] text-slate-400 dark:text-slate-600">Open Tasks</p>
                <p class="font-headline font-bold text-2xl text-slate-800 dark:text-slate-100 mt-1">
                    <span id="statOpenTasks">—</span><span class="text-sm font-light text-purple-600 dark:text-purple-500 ml-1">tasks</span>
                </p>
                <p id="statOpenTasksMeta" class="font-technical text-[9px] text-slate-400 mt-1">Loading…</p>
            </div>
            <div class="w-12 h-12 rounded-full border-2 border-purple-200 dark:border-purple-600/20 flex items-center justify-center bg-purple-50 dark:bg-purple-950/20">
                <span class="material-symbols-outlined text-purple-600 dark:text-purple-500 fill-icon" style="font-size:22px;filter:drop-shadow(0 0 6px rgba(168,85,247,0.4));">verified</span>
            </div>
        </div>

        <div class="flex-1 bg-white dark:bg-[#0c0b10] rounded-xl border border-slate-200 dark:border-white/5 shadow-xl dark:shadow-2xl p-6 flex items-center justify-between">
            <div>
                <p class="font-technical text-[10px] uppercase tracking-[0.25em] text-slate-400 dark:text-slate-600">Active Projects</p>
                <p class="font-headline font-bold text-2xl text-slate-800 dark:text-slate-100 mt-1">
                    <span id="statProjects">—</span>
                </p>
                <p id="statProjectsMeta" class="font-technical text-[9px] text-slate-400 mt-1">Loading…</p>
            </div>
            <div class="flex -space-x-2" id="activeProjectFriends">
                <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 border-2 border-white dark:border-white/10 flex items-center justify-center text-[9px] font-bold text-slate-400 dark:text-slate-500">…</div>
            </div>
        </div>

    </footer>
</main>

<!-- Day Tasks Panel -->
<div id="dayTasksPanelOverlay"></div>
<div id="dayTasksPanel">
    <div class="dtp-header">
        <p class="dtp-title" id="dayTasksPanelTitle">Tasks for this day</p>
        <button class="dtp-close" onclick="closeDayTasksPanel()">&#x2715;</button>
    </div>
    <div class="dtp-body" id="dayTasksPanelBody"></div>
    <div class="dtp-add-row">
        <input type="text" id="dayTasksNewTitle" class="input-base" style="flex:1;font-size:0.82rem;" placeholder="Add a task for this day…">
        <button id="dayTasksAddBtn" class="btn btn-primary" style="padding:0 16px;font-size:0.82rem;">Add</button>
    </div>
</div>

<!-- Task Creation Modal (for manual use) -->
<div id="taskModal" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="section-title" style="margin:0;font-size:1.2rem;">New Task</h3>
            <button class="modal-close" onclick="closeModal('taskModal')">&times;</button>
        </div>
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="input-group">
                <label class="input-label" for="modalTaskTitle">Title</label>
                <input type="text" id="modalTaskTitle" class="input-base" placeholder="What needs to be done?">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="input-group">
                    <label class="input-label" for="modalTaskDate">Deadline</label>
                    <input type="date" id="modalTaskDate" class="input-base">
                </div>
                <div class="input-group">
                    <label class="input-label" for="modalTaskReminder">Reminder</label>
                    <input type="datetime-local" id="modalTaskReminder" class="input-base">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="input-group">
                    <label class="input-label" for="modalTaskProject">Project</label>
                    <select id="modalTaskProject" class="input-base">
                        <option value="">No project</option>
                    </select>
                </div>
                <div class="input-group">
                    <label class="input-label" for="modalTaskTag">Tag</label>
                    <input type="text" id="modalTaskTag" class="input-base" placeholder="e.g. design">
                </div>
            </div>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:0.85rem;">
                <input type="checkbox" id="modalTaskFlag" style="accent-color:var(--color-primary);">
                Flag as high priority
            </label>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" style="flex:1;" onclick="closeModal('taskModal')">Cancel</button>
            <button class="btn btn-primary" id="btnSubmitModalTask" style="flex:1;">Add Task</button>
        </div>
    </div>
</div>

<script src="assets/js/utils.js"></script>
<script src="assets/js/api.js"></script>
<script src="assets/js/store.js"></script>
<script src="assets/js/dashboard.js"></script>

<script>
/* Dispatch userLoaded event for sidebar once store hydrates */
document.addEventListener('DOMContentLoaded', function () {
    store.loadUser().then(function (u) {
        if (u) window.dispatchEvent(new CustomEvent('userLoaded', { detail: u }));
    }).catch(function () {});

    /* Update greeting prefix based on time */
    var h = new Date().getHours();
    var g = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
    var el = document.getElementById('timeGreeting');
    if (el) el.textContent = g;

    /* Daily Progress = tasks completed today / tasks due today */
    async function refreshDailyProgress() {
        try {
            var resp = await API.getTasks('all');
            var all = (resp.data || []);
            var todayKey = new Date().toISOString().slice(0, 10);
            var todayTasks = all.filter(function(t) {
                return t.deadline && String(t.deadline).slice(0, 10) === todayKey;
            });
            var done = todayTasks.filter(function(t) { return Number(t.is_completed); }).length;
            var total = todayTasks.length;
            var pct = total > 0 ? Math.round((done / total) * 100) : 0;
            var bar = document.getElementById('energyBar');
            var lbl = document.getElementById('energyPct');
            if (bar) bar.style.width = pct + '%';
            if (lbl) lbl.textContent = (total > 0 ? done + '/' + total + ' (' + pct + '%)' : '0%');
        } catch(e) {}
    }
    refreshDailyProgress();

    /* Re-sync whenever tasks change */
    document.addEventListener('tasksUpdated', refreshDailyProgress);
});
</script>
</body>
</html>
