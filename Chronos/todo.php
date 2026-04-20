<?php
require_once __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/helpers/session.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks | Chronos</title>
    <meta name="description" content="Manage your tasks, set deadlines, and track progress across all your projects.">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);if(t==='dark')document.documentElement.classList.add('dark');}());</script>
    <link rel="stylesheet" href="assets/css/theme-light.css">
    <link rel="stylesheet" href="assets/css/theme-dark.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
    /* ── Page shell — full-height sanctuary-surface card ── */
    .todo-page {
        display:flex; flex-direction:column; gap:0;
        /* Fill the padded content-wrapper (1.5rem top + 1.5rem bottom = 3rem) */
        height: calc(100dvh - 3rem);
        min-height: 560px;
        /* Sanctuary-surface card: same treatment as dashboard widget cards */
        background: var(--color-surface);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-card, 1rem);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }


    /* ── Topbar ─────────────────────────────────────────── */
    .todo-topbar {
        display:flex; align-items:center; height:46px; flex-shrink:0;
        border-bottom:1px solid var(--glass-border);
        background:var(--color-surface); position:sticky; top:0; z-index:50;
    }
    .todo-nav { display:flex; align-items:center; gap:2px; padding:0 12px; flex:1; overflow-x:auto; min-width:0; scrollbar-width:none; }
    .todo-nav::-webkit-scrollbar { display:none; }
    .todo-nav__pill {
        padding:4px 13px; border-radius:999px; font-size:10px; font-weight:700;
        letter-spacing:0.07em; text-transform:uppercase; cursor:pointer; white-space:nowrap;
        border:1px solid transparent; background:transparent;
        color:var(--color-text-secondary); transition:all 0.15s; flex-shrink:0;
    }
    .todo-nav__pill:hover  { background:rgba(var(--color-primary-rgb),.07); color:var(--color-text-primary); }
    .todo-nav__pill.active { background:rgba(var(--color-primary-rgb),.12); border-color:rgba(var(--color-primary-rgb),.3); color:var(--color-primary); }
    .todo-nav__sep { width:1px; height:16px; background:var(--glass-border); margin:0 6px; flex-shrink:0; }

    /* ── Topbar right controls ──────────────────────────── */
    .todo-controls { display:flex; align-items:center; gap:6px; padding:0 12px; border-left:1px solid var(--glass-border); flex-shrink:0; }

    /* Search */
    .todo-search-wrap { position:relative; }
    .todo-search-input {
        border:1px solid var(--glass-border); border-radius:999px;
        background:var(--color-background); padding:4px 12px 4px 28px;
        font-size:11px; color:var(--color-text-primary); outline:none; width:140px;
        transition:border-color .2s, box-shadow .2s;
    }
    .todo-search-input:focus { border-color:var(--color-primary); box-shadow:0 0 0 2px rgba(var(--color-primary-rgb),.12); }
    .todo-search-icon { position:absolute; left:9px; top:50%; transform:translateY(-50%); opacity:.35; pointer-events:none; }

    /* Sort/Filter + Display icon buttons */
    .tb-icon-btn {
        display:flex; align-items:center; justify-content:center; gap:5px;
        height:28px; padding:0 10px; border-radius:6px; border:1px solid var(--glass-border);
        background:transparent; cursor:pointer; font-size:10px; font-weight:700; letter-spacing:.06em;
        color:var(--color-text-secondary); transition:all .15s; white-space:nowrap;
    }
    .tb-icon-btn:hover, .tb-icon-btn.active { border-color:var(--color-primary); color:var(--color-primary); background:rgba(var(--color-primary-rgb),.07); }
    .view-btn-group { display:flex; border:1px solid var(--glass-border); border-radius:8px; overflow:hidden; }
    .view-btn {
        display:flex; align-items:center; justify-content:center;
        width:28px; height:28px; border:none; border-right:1px solid var(--glass-border);
        background:transparent; cursor:pointer; color:var(--color-text-secondary); transition:all .15s;
    }
    .view-btn:last-child { border-right:none; }
    .view-btn:hover, .view-btn.active { background:rgba(var(--color-primary-rgb),.1); color:var(--color-primary); }

    /* Dropdown panels */
    .td-dropdown {
        position:absolute; top:calc(100% + 6px); right:0; z-index:9999;
        background:var(--color-surface); border:1px solid var(--glass-border);
        border-radius:12px; padding:14px; box-shadow:0 12px 40px rgba(0,0,0,.2);
        width:260px; display:none;
        animation:dpFadeIn .15s ease;
    }
    .td-dropdown.open { display:block; }
    @keyframes dpFadeIn { from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:translateY(0)} }

    .td-dropdown__label { font-size:9px; font-weight:700; text-transform:uppercase;
        letter-spacing:.12em; color:var(--color-text-secondary); margin:0 0 8px; }
    .sort-grid { display:grid; grid-template-columns:1fr 1fr; gap:4px; margin-bottom:12px; }
    .sort-opt {
        padding:5px 8px; border-radius:6px; border:1px solid var(--glass-border);
        background:transparent; cursor:pointer; font-size:10px; font-weight:600;
        color:var(--color-text-secondary); transition:all .15s; text-align:left;
    }
    .sort-opt:hover { border-color:var(--color-primary); color:var(--color-primary); }
    .sort-opt.active { background:rgba(var(--color-primary-rgb),.12); border-color:rgba(var(--color-primary-rgb),.3); color:var(--color-primary); }
    .filter-row { display:flex; align-items:center; gap:8px; cursor:pointer;
        padding:5px 0; font-size:11px; color:var(--color-text-secondary); }
    .filter-row input[type=checkbox] { accent-color:var(--color-primary); }

    /* ── Body ────────────────────────────────────────────── */
    .todo-body { display:flex; flex:1; overflow:hidden; min-height:0; }

    /* Board column */
    .todo-board-col { flex:1; display:flex; flex-direction:column; overflow:hidden; }
    .todo-board-toolbar {
        display:flex; align-items:center; justify-content:space-between;
        padding:14px 20px 10px; border-bottom:1px solid var(--glass-border); flex-shrink:0;
    }
    .todo-board-scroll { flex:1; overflow-y:auto; padding:18px 20px; }

    /* Detail column — HIDDEN until task click */
    .todo-detail-col {
        width:300px; flex-shrink:0; border-left:1px solid var(--glass-border);
        display:none; flex-direction:column; overflow-y:auto;
        background:var(--color-surface); animation:slideInRight .2s ease;
    }
    @keyframes slideInRight { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:translateX(0)} }
    .todo-detail-col.visible { display:flex; }
    .todo-detail-header { padding:12px 16px; border-bottom:1px solid var(--glass-border);
        display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
    .todo-detail-body { padding:18px; display:flex; flex-direction:column; gap:16px; flex:1; }
    .tdf-label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:var(--color-text-secondary); margin-bottom:3px; }
    .tdf-val { font-size:12px; color:var(--color-text-primary); font-weight:500; line-height:1.5; }
    .tdf-val.empty { opacity:.4; font-style:italic; }

    /* ── Inline adder ───────────────────────────────────── */
    .todo-adder-ph {
        background:rgba(var(--color-primary-rgb),.03); border:1px dashed var(--glass-border);
        border-radius:var(--radius-md); padding:13px 18px; cursor:text;
        color:var(--color-text-secondary); font-size:13px; font-weight:500;
        transition:border-color .2s,background .2s; margin-bottom:14px;
    }
    .todo-adder-ph:hover { border-color:var(--color-primary); background:rgba(var(--color-primary-rgb),.06); }
    .todo-adder-exp {
        background:rgba(var(--color-primary-rgb),.04); border:1px solid var(--glass-border);
        border-radius:var(--radius-md); padding:18px; display:flex;
        flex-direction:column; gap:12px; margin-bottom:14px;
    }

    /* ── Custom picker trigger buttons ──────────────────── */
    .cp-trigger {
        display:flex; align-items:center; gap:8px; width:100%;
        background:var(--color-background); border:1px solid var(--glass-border);
        border-radius:var(--radius-sm); padding:7px 11px; cursor:pointer;
        font-size:11px; color:var(--color-text-secondary); text-align:left;
        transition:border-color .15s, color .15s;
    }
    .cp-trigger:hover, .cp-trigger.has-value { border-color:var(--color-primary); color:var(--color-text-primary); }
    .cp-trigger-val { flex:1; }

    /* ── Chronos Date / Time pickers ────────────────────── */
    .chronos-picker {
        position:absolute; top:calc(100% + 6px); left:0; z-index:9999;
        background:var(--color-surface); border:1px solid var(--glass-border);
        border-radius:14px; padding:14px; box-shadow:0 14px 44px rgba(0,0,0,.24);
        width:256px; animation:dpFadeIn .15s ease;
    }
    .cp-header {
        display:flex; align-items:center; justify-content:space-between;
        margin-bottom:10px;
    }
    .cp-header-title {
        font-size:11px; font-weight:700; text-transform:uppercase;
        letter-spacing:.07em; color:var(--color-primary); flex:1; text-align:center;
    }
    .cp-nav {
        background:transparent; border:1px solid var(--glass-border); border-radius:6px;
        width:26px; height:26px; cursor:pointer; color:var(--color-primary); font-size:15px;
        display:flex; align-items:center; justify-content:center; transition:all .15s; flex-shrink:0;
    }
    .cp-nav:hover { background:rgba(var(--color-primary-rgb),.1); }
    .cp-grid { gap:3px !important; }
    .cp-footer {
        display:flex; gap:6px; padding-top:10px;
        border-top:1px solid var(--glass-border); margin-top:8px;
    }
    .cp-footer .btn { flex:1; font-size:10px; padding:5px 8px; }

    /* Time picker */
    .ctp-phase-label { font-size:9px; font-weight:700; text-transform:uppercase;
        letter-spacing:.12em; color:var(--color-text-secondary); margin:0 0 4px; text-align:center; }
    .ctp-wheels { display:flex; align-items:center; justify-content:center; gap:10px; padding:14px 0; }
    .ctp-wheel { display:flex; flex-direction:column; align-items:center; gap:6px; }
    .ctp-digit {
        font-family:'JetBrains Mono',monospace; font-size:30px; font-weight:700;
        color:var(--color-primary); min-width:50px; text-align:center;
        filter:drop-shadow(0 0 8px rgba(var(--color-primary-rgb),.45));
    }
    .ctp-colon { font-size:26px; font-weight:700; color:var(--color-text-secondary); margin-bottom:4px; }
    .ctp-arrow {
        background:transparent; border:1px solid var(--glass-border); border-radius:6px;
        width:34px; height:20px; cursor:pointer; color:var(--color-text-secondary);
        font-size:9px; display:flex; align-items:center; justify-content:center; transition:all .15s;
    }
    .ctp-arrow:hover { border-color:var(--color-primary); color:var(--color-primary); background:rgba(var(--color-primary-rgb),.08); }
    .ctp-ampm { display:flex; flex-direction:column; gap:5px; }
    .ctp-ampm-btn {
        background:transparent; border:1px solid var(--glass-border); border-radius:6px;
        padding:4px 9px; cursor:pointer; font-size:9px; font-weight:800;
        letter-spacing:.08em; color:var(--color-text-secondary); transition:all .15s;
    }
    .ctp-ampm-btn.active { background:var(--color-primary); color:#fff; border-color:var(--color-primary); }
    .ctp-ampm-btn:not(.active):hover { border-color:var(--color-primary); color:var(--color-primary); }

    /* ── Calendar view container ────────────────────────── */
    .todo-cal-wrapper { display:flex; flex-direction:column; gap:14px; }
    .todo-cal-header { display:flex; align-items:center; justify-content:space-between; padding-bottom:10px; border-bottom:1px solid var(--glass-border); }
    .todo-cal-agenda { padding-top:14px; border-top:1px solid var(--glass-border); }

    /* Widget grid view */
    .todo-widget-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:14px; }
    .todo-widget-card { background:rgba(var(--color-primary-rgb),.04); border:1px solid var(--glass-border); border-radius:var(--radius-md); padding:16px; cursor:pointer; transition:border-color .15s,transform .15s; }
    .todo-widget-card:hover { border-color:var(--color-primary); transform:translateY(-1px); }

    /* ── Search fix: kill browser default ring ───────────── */
    .todo-search-input { outline:0 !important; box-shadow:none; }
    .todo-search-input:focus {
        border-color:var(--color-primary) !important;
        box-shadow:0 0 0 2px rgba(var(--color-primary-rgb),.15) !important;
    }
    .todo-search-wrap .search-suggestions {
        position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:99999;
    }

    /* ── picker: override position — createFloatingPanel sets fixed from JS ── */
    .chronos-picker { position:fixed !important; }

    /* ── Floating sort/filter dropdown ───────────────────── */
    .td-dropdown-float {
        background:var(--color-surface); border:1px solid var(--glass-border);
        border-radius:12px; padding:14px; box-shadow:0 12px 40px rgba(0,0,0,.22);
        width:260px; animation:dpFadeIn .15s ease;
    }
    .td-dropdown-float .td-dropdown__label {
        font-size:9px; font-weight:700; text-transform:uppercase;
        letter-spacing:.12em; color:var(--color-text-secondary); margin:0 0 8px; display:block;
    }
    .td-dropdown-float .sort-grid { display:grid; grid-template-columns:1fr 1fr; gap:4px; margin-bottom:12px; }
    .td-dropdown-float .sort-opt {
        padding:5px 8px; border-radius:6px; border:1px solid var(--glass-border);
        background:transparent; cursor:pointer; font-size:10px; font-weight:600;
        color:var(--color-text-secondary); transition:all .15s; text-align:left; white-space:nowrap;
    }
    .td-dropdown-float .sort-opt:hover { border-color:var(--color-primary); color:var(--color-primary); }
    .td-dropdown-float .sort-opt.active {
        background:rgba(var(--color-primary-rgb),.12); border-color:rgba(var(--color-primary-rgb),.3);
        color:var(--color-primary);
    }
    .td-dropdown-float .filter-row {
        display:flex; align-items:center; gap:8px; cursor:pointer;
        padding:5px 0; font-size:11px; color:var(--color-text-secondary);
    }
    .td-dropdown-float .filter-row input[type=checkbox] { accent-color:var(--color-primary); cursor:pointer; }

    /* ── Custom checkbox (button-based, no native input) ── */
    .todo-cb-btn {
        width:18px; height:18px; min-width:18px; border-radius:5px;
        border:2px solid rgba(var(--color-primary-rgb),.38);
        background:transparent; cursor:pointer; flex-shrink:0;
        transition:all .2s; position:relative; padding:0;
    }
    .todo-cb-btn:hover:not(.checked) {
        border-color:var(--color-primary);
        background:rgba(var(--color-primary-rgb),.06);
    }
    .todo-cb-btn.checked {
        background:var(--color-primary); border-color:var(--color-primary);
        box-shadow:0 0 0 3px rgba(var(--color-primary-rgb),.18);
    }
    .todo-cb-btn.checked::after {
        content:''; position:absolute;
        left:4px; top:2px; width:5px; height:8px;
        border:2px solid #fff; border-left:none; border-top:none;
        transform:rotate(45deg);
    }

    /* Fade completed task title */
    .task-item.is-complete .task-item__title { text-decoration:line-through; opacity:.45; }

    /* ── Todo full-width calendar: override aspect-ratio from components.css ── */
    /* In the dashboard, the calendar is in a narrow widget (1/3 screen);       */
    /* here it fills the full content area, so we use a fixed height instead.   */
    .todo-board-scroll .calendar-board {
        gap: 2px;
        background: rgba(var(--color-primary-rgb), 0.03);
        border-radius: var(--radius-md);
        padding: 8px;
    }
    .todo-board-scroll .calendar-board .cal-day {
        aspect-ratio: auto !important;   /* override the 1/1 from components.css */
        height: 72px;
        align-items: flex-start;
        justify-content: flex-start;
        padding: 6px 5px;
        border-radius: 6px;
        font-size: 12px;
    }
    .todo-board-scroll .calendar-board .cal-day__number {
        font-size: 12px;
        line-height: 1;
    }
    .todo-board-scroll .calendar-board .calendar-label {
        font-size: 9px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: none;
    }
    /* Boost today highlight in full-width view */
    .todo-board-scroll .calendar-board .cal-day.today {
        background: rgba(var(--color-primary-rgb), 0.18) !important;
        font-weight: 700;
    }
    /* Enhance todo-page card border slightly more than the default glass-border */
    .todo-page {
        border-color: rgba(var(--color-primary-rgb), 0.25);
    }
    </style>

</head>
<body>
<div class="bg-mesh" aria-hidden="true"></div>
<div class="app-container">
    <?php include 'partials/sidebar.php'; ?>

    <main id="mainContentWrapper" class="content-wrapper" role="main">
    <div class="page-shell page-enter todo-page">

        <!-- ══ TOP NAV BAR ══════════════════════════════════════ -->
        <div class="todo-topbar">

            <!-- Frequency + Project pills -->
            <nav class="todo-nav" id="todoFreqNav" aria-label="Task filters">
                <button class="todo-nav__pill active" data-filter="all">All</button>
                <button class="todo-nav__pill" data-filter="today">Today</button>
                <button class="todo-nav__pill" data-filter="week">This Week</button>
                <div class="todo-nav__sep" aria-hidden="true"></div>
                <button class="todo-nav__pill" id="grpPersonal" data-grp="personal">My Projects</button>
                <button class="todo-nav__pill" id="grpTeam" data-grp="team">Team Projects</button>
            </nav>

            <!-- Right controls -->
            <div class="todo-controls">

                <!-- Search -->
                <div class="todo-search-wrap" style="position:relative;">
                    <svg class="todo-search-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="taskSearch" placeholder="Search…" class="todo-search-input" autocomplete="off">
                    <div id="searchSuggestions" class="sanctuary-surface hidden search-suggestions" style="top:calc(100% + 4px);left:0;right:0;z-index:9999;"></div>
                </div>

                <!-- Sort & Filter button + dropdown -->
                <div style="position:relative;">
                    <button class="tb-icon-btn" id="sortFilterBtn" title="Sort &amp; Filter">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                        Sort &amp; Filter
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="td-dropdown" id="sortFilterDropdown">
                        <p class="td-dropdown__label">Sort By</p>
                        <div class="sort-grid" id="sortOptsGrid">
                            <button class="sort-opt active" data-sort="manual">Default</button>
                            <button class="sort-opt" data-sort="deadline_asc">Deadline ↑</button>
                            <button class="sort-opt" data-sort="deadline_desc">Deadline ↓</button>
                            <button class="sort-opt" data-sort="title_asc">Title A→Z</button>
                            <button class="sort-opt" data-sort="title_desc">Title Z→A</button>
                            <button class="sort-opt" data-sort="created_desc">Newest</button>
                            <button class="sort-opt" data-sort="created_asc">Oldest</button>
                            <button class="sort-opt" data-sort="priority">Priority</button>
                        </div>
                        <p class="td-dropdown__label" style="margin-top:8px;">Filter</p>
                        <label class="filter-row">
                            <input type="checkbox" id="showCompletedToggle"> Show completed
                        </label>
                        <label class="filter-row">
                            <input type="checkbox" id="showFlaggedOnly"> Flagged only
                        </label>
                    </div>
                </div>

                <!-- Display toggle (List / Widget / Calendar) -->
                <div class="view-btn-group" role="group" aria-label="View mode">
                    <button class="view-btn active" data-view="list" title="List view">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </button>
                    <button class="view-btn" data-view="widget" title="Grid view">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    </button>
                    <button class="view-btn" data-view="calendar" title="Calendar view">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </button>
                </div>

                <!-- Calendar jump-to-date (shown in calendar view) -->
                <button id="btnCalendarJump" class="tb-icon-btn hidden" title="Jump to date">
                    <span class="material-symbols-outlined" style="font-size:13px;">today</span>
                </button>

            </div><!-- /controls -->
        </div><!-- /topbar -->

        <!-- ══ BODY ═════════════════════════════════════════════ -->
        <div class="todo-body">

            <!-- Board column -->
            <div class="todo-board-col">
                <div class="todo-board-toolbar">
                    <div>
                        <p class="section-kicker">Task Board</p>
                        <h2 class="section-title" id="tasksPanelTitle" style="font-size:1.05rem;margin:0;">Active Tasks</h2>
                    </div>
                </div>

                <div class="todo-board-scroll">

                    <!-- Inline adder -->
                    <div id="adderPlaceholder" class="todo-adder-ph">+ Add a new task…</div>
                    <div id="adderExpanded" class="todo-adder-exp hidden">
                        <input type="text" id="inlineTaskTitle" placeholder="What do you need to do?"
                               class="sigil-input" style="font-weight:700;font-size:1.1rem;">

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <!-- Custom date picker for deadline -->
                            <div class="input-group">
                                <label class="input-label">Deadline</label>
                                <div style="position:relative;">
                                    <button type="button" id="inlineTaskDateBtn" class="cp-trigger">
                                        <span class="material-symbols-outlined" style="font-size:13px;flex-shrink:0;">event</span>
                                        <span class="cp-trigger-val" id="inlineTaskDateDisplay">Pick date</span>
                                    </button>
                                    <input type="hidden" id="inlineTaskDate">
                                </div>
                            </div>
                            <!-- Custom date+time picker for reminder -->
                            <div class="input-group">
                                <label class="input-label">Reminder</label>
                                <div style="position:relative;">
                                    <button type="button" id="inlineTaskReminderBtn" class="cp-trigger">
                                        <span class="material-symbols-outlined" style="font-size:13px;flex-shrink:0;">schedule</span>
                                        <span class="cp-trigger-val" id="inlineTaskReminderDisplay">Set reminder</span>
                                    </button>
                                    <input type="hidden" id="inlineTaskReminder">
                                </div>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <div class="input-group">
                                <label class="input-label" for="inlineTaskTag">Tag</label>
                                <input type="text" id="inlineTaskTag" class="input-base" placeholder="e.g. design" style="font-size:12px;">
                            </div>
                            <div class="input-group">
                                <label class="input-label" for="inlineTaskProject">Project</label>
                                <select id="inlineTaskProject" class="input-base" style="font-size:12px;">
                                    <option value="">No project</option>
                                </select>
                            </div>
                        </div>

                        <div class="input-group">
                            <label class="input-label" for="inlineTaskDesc">Notes</label>
                            <textarea id="inlineTaskDesc" class="input-base" rows="2"
                                placeholder="Optional description…" style="resize:vertical;font-size:12px;"></textarea>
                        </div>

                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <button class="btn btn-icon" id="toggleFlag" title="Flag as priority">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                    <line x1="4" y1="22" x2="4" y2="15"/>
                                </svg>
                            </button>
                            <div style="display:flex;gap:8px;">
                                <button class="btn btn-ghost" id="btnCancelAdd" style="font-size:12px;">Cancel</button>
                                <button class="btn btn-primary" id="btnSubmitInlineTask" style="font-size:12px;">Add Task</button>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks rendered by JS -->
                    <div id="tasksContent"></div>
                </div>
            </div><!-- /board col -->

            <!-- Detail panel — hidden by default -->
            <aside class="todo-detail-col" id="todoDetailCol">
                <div class="todo-detail-header">
                    <p class="section-kicker" style="margin:0;font-size:9px;">Task Detail</p>
                    <button type="button" id="closeDetailBtn" class="btn btn-icon" style="width:22px;height:22px;font-size:16px;line-height:1;"
                        title="Close detail panel">×</button>
                </div>
                <div id="taskDetailPanel" class="todo-detail-body">
                    <div class="empty-state" style="margin:auto;">
                        <h3>No selection</h3>
                        <p>Click a task to view its details.</p>
                    </div>
                </div>
            </aside>

        </div><!-- /body -->
    </div>
    </main>
</div>

<script src="assets/js/utils.js"></script>
<script src="assets/js/api.js"></script>
<script src="assets/js/store.js"></script>
<script src="assets/js/todo.js"></script>
</body>
</html>
