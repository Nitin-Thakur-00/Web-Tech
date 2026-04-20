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
    <title>Projects | Chronos</title>
    <meta name="description" content="Manage personal and team projects, track milestones, and collaborate.">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);if(t==='dark')document.documentElement.classList.add('dark');}());</script>
    <link rel="stylesheet" href="assets/css/theme-light.css">
    <link rel="stylesheet" href="assets/css/theme-dark.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/animations.css">
<style>
/* ══════════════════════════════════════════════
   PROJECTS — Dedicated Styles
   ══════════════════════════════════════════════ */
@media (max-width: 768px) {
    #settingsBodyGrid {
        grid-template-columns: 1fr !important;
    }
    .modal-container {
        max-height: 90vh;
        overflow-y: auto;
    }
    .proj-tabbar {
        flex-wrap: wrap;
        gap: 10px;
        padding-bottom: 10px;
    }
    .proj-tab-spacer { 
        display: none; 
    }
}

/* Tab bar */
.proj-tabbar {
    display: flex;
    align-items: center;
    gap: 0;
    border-bottom: 1px solid var(--glass-border);
    margin-bottom: 1rem;
    flex-shrink: 0;
}
.proj-tab {
    padding: 0.55rem 1.1rem;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    border: none;
    background: transparent;
    color: var(--color-text-secondary);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: all 0.18s;
    white-space: nowrap;
}
.proj-tab:hover { color: var(--color-text-primary); }
.proj-tab.active { color: var(--color-primary); border-bottom-color: var(--color-primary); }
.proj-tab-spacer { flex: 1; }

/* Toolbar */
.proj-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 1rem;
    flex-shrink: 0;
}
.proj-select {
    height: 32px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid var(--glass-border);
    background: var(--color-surface);
    color: var(--color-text-secondary);
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.63rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    cursor: pointer;
    outline: none;
    transition: border-color 0.15s;
}
.proj-select:focus { border-color: var(--color-primary); }
.proj-past-label {
    display: flex;
    align-items: center;
    gap: 7px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.63rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--color-text-secondary);
    cursor: pointer;
    margin-left: auto;
    user-select: none;
}

/* Main split layout */
.proj-main {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 14px;
    flex: 1;
    min-height: 0;
}
@media (max-width: 960px) { .proj-main { grid-template-columns: 1fr; } }

/* Grid panel */
.proj-grid-panel {
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--glass-border) transparent;
}
.proj-grid { display: flex; flex-direction: column; gap: 9px; }

/* Project card */
.proj-card {
    width: 100%;
    padding: 14px 16px;
    border-radius: 12px;
    border: 1px solid var(--glass-border);
    border-left: 4px solid var(--card-clr, var(--color-primary));
    background: var(--color-surface);
    cursor: pointer;
    text-align: left;
    transition: transform 0.18s, box-shadow 0.18s, border-color 0.18s, background 0.18s;
    position: relative;
    overflow: hidden;
}
.proj-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, rgba(255,255,255,0.015) 0%, transparent 60%);
    pointer-events: none;
}
.proj-card:hover {
    transform: translateX(4px);
    box-shadow: -4px 0 0 0 var(--card-clr, var(--color-primary)), 0 6px 24px rgba(0,0,0,0.25);
    border-color: var(--card-clr, var(--color-primary));
}
.proj-card.is-selected {
    background: rgba(var(--color-primary-rgb), 0.07);
    border-color: var(--card-clr, var(--color-primary));
    box-shadow: -4px 0 0 0 var(--card-clr, var(--color-primary));
}
.proj-card.is-past .proj-card__name { text-decoration: line-through; opacity: 0.55; }

.proj-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
    gap: 6px;
}
.proj-badge {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 2px 7px;
    border-radius: 4px;
    background: rgba(var(--color-primary-rgb), 0.12);
    color: var(--color-primary);
    flex-shrink: 0;
}
.proj-badge.team { background: rgba(139,92,246,0.18); color: #a78bfa; }
.proj-badge.past { background: rgba(115,115,115,0.18); color: var(--color-text-secondary); }

.proj-card__name {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--color-text-primary);
    margin: 0 0 10px;
    line-height: 1.25;
}
.proj-card__bar-row {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 8px;
}
.proj-card__bar {
    flex: 1;
    height: 4px;
    border-radius: 2px;
    background: rgba(var(--color-primary-rgb), 0.1);
    overflow: hidden;
}
.proj-card__fill {
    height: 100%;
    border-radius: 2px;
    width: 0%;
    transition: width 0.9s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.proj-card__pct {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.63rem;
    font-weight: 700;
    color: var(--card-clr, var(--color-primary));
    white-space: nowrap;
    flex-shrink: 0;
}
.proj-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.6rem;
    font-weight: 600;
    color: var(--color-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Detail panel */
.proj-detail-panel {
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--glass-border) transparent;
}
.proj-detail-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
@media (max-width: 1280px) { .proj-detail-split { grid-template-columns: 1fr; } }

.proj-section {
    background: var(--color-surface);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* Section label */
.proj-lbl {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--color-text-secondary);
    margin-bottom: 4px;
    display: block;
}

/* Editable field container */
.proj-editable {
    position: relative;
}
.proj-pencil {
    position: absolute;
    top: 2px;
    right: 0;
    width: 26px;
    height: 26px;
    border: none;
    background: transparent;
    cursor: pointer;
    color: var(--color-text-secondary);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.15s, color 0.15s, background 0.15s;
}
.proj-editable:hover .proj-pencil { opacity: 1; }
.proj-pencil:hover { color: var(--color-primary); background: rgba(var(--color-primary-rgb), 0.1); }

/* Priority chips */
.prio { font-family: 'JetBrains Mono', monospace; font-size: 0.58rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; padding: 2px 6px; border-radius: 4px; }
.prio-high   { background: rgba(239,68,68,0.15);  color: #f87171; }
.prio-medium { background: rgba(251,191,36,0.15);  color: #fbbf24; }
.prio-low    { background: rgba(74,222,128,0.15);  color: #4ade80; }

/* Subtask row */
.stask {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(var(--color-primary-rgb), 0.06);
}
.stask:last-child { border-bottom: none; }
.stask.done .stask-title { text-decoration: line-through; opacity: 0.4; }
.stask-title {
    flex: 1;
    font-size: 0.82rem;
    color: var(--color-text-primary);
    line-height: 1.4;
    min-width: 0;
}
.stask-meta {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 3px;
    flex-wrap: wrap;
}
.stask-del {
    width: 20px; height: 20px;
    border: none; background: transparent;
    cursor: pointer; color: transparent;
    border-radius: 4px;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
    flex-shrink: 0;
    margin-top: 2px;
}
.stask:hover .stask-del { color: var(--color-text-secondary); }
.stask-del:hover { color: #ef4444 !important; background: rgba(239,68,68,0.1); }

/* Add subtask form */
.add-stask-form {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
    align-items: flex-end;
    padding: 12px;
    background: rgba(var(--color-primary-rgb), 0.03);
    border-radius: 10px;
    border: 1px dashed rgba(var(--color-primary-rgb), 0.15);
    margin-top: 6px;
}
.add-stask-form input, .add-stask-form select {
    height: 32px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid var(--glass-border);
    background: var(--color-background);
    color: var(--color-text-primary);
    font-size: 0.78rem;
    outline: none;
    transition: border-color 0.15s;
}
.add-stask-form input { flex: 1; min-width: 130px; }
.add-stask-form input:focus, .add-stask-form select:focus { border-color: var(--color-primary); }
.add-stask-form select { font-family: 'JetBrains Mono', monospace; font-size: 0.65rem; font-weight: 600; }

/* Notes textarea */
.proj-notes {
    width: 100%;
    min-height: 90px;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid var(--glass-border);
    background: var(--color-background);
    color: var(--color-text-primary);
    font-family: 'Inter', sans-serif;
    font-size: 0.8rem;
    line-height: 1.55;
    resize: vertical;
    outline: none;
    transition: border-color 0.15s;
}
.proj-notes:focus { border-color: var(--color-primary); }

/* Member row */
.member-row {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 5px 0;
}
.member-av {
    width: 28px; height: 28px;
    border-radius: 8px;
    background: rgba(var(--color-primary-rgb), 0.14);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800;
    color: var(--color-primary);
    flex-shrink: 0;
    font-family: 'Space Grotesk', sans-serif;
}
.member-name { flex: 1; font-size: 0.8rem; font-weight: 600; }
.member-role {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.58rem; font-weight: 700;
    letter-spacing: 0.07em; text-transform: uppercase;
    padding: 2px 6px; border-radius: 4px;
    background: rgba(var(--color-primary-rgb), 0.1);
    color: var(--color-primary);
}
.member-role.leader { background: rgba(251,191,36,0.15); color: #fbbf24; }

/* Chat */
.chat-feed {
    overflow-y: auto;
    max-height: 200px;
    display: flex;
    flex-direction: column;
    gap: 5px;
    scrollbar-width: thin;
    scrollbar-color: var(--glass-border) transparent;
}
.chat-bubble {
    padding: 8px 10px;
    border-radius: 8px;
    background: rgba(var(--color-primary-rgb), 0.04);
    border: 1px solid rgba(var(--color-primary-rgb), 0.06);
}
.chat-bubble.private { border-left: 3px solid #a78bfa; background: rgba(139,92,246,0.05); }
.chat-bubble__hd { display: flex; align-items: center; gap: 7px; margin-bottom: 3px; }
.chat-bubble__name { font-size: 0.73rem; font-weight: 700; color: var(--color-text-primary); }
.chat-bubble__time { font-family: 'JetBrains Mono', monospace; font-size: 0.58rem; color: var(--color-text-secondary); }
.chat-bubble__body { font-size: 0.78rem; color: var(--color-text-secondary); line-height: 1.45; }
.chat-bubble__body .mention { color: var(--color-primary); font-weight: 700; }

/* Color palette (modal) */
.color-row { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
.cswatch {
    width: 28px; height: 28px;
    border-radius: 7px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: transform 0.12s, border-color 0.12s;
    flex-shrink: 0;
}
.cswatch:hover { transform: scale(1.2); }
.cswatch.active { border-color: #fff; box-shadow: 0 0 0 2px var(--color-primary); }

/* Settings row */
.proj-settings-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 14px;
    border-top: 1px solid var(--glass-border);
    margin-top: auto;
}
</style>
</head>
<body>
<div class="bg-mesh" aria-hidden="true"></div>
<div class="app-container">
    <?php include 'partials/sidebar.php'; ?>

    <main id="mainContentWrapper" class="content-wrapper" role="main">
        <div class="page-shell page-enter" style="display:flex;flex-direction:column;height:calc(100vh - 2rem);overflow:hidden;">

            <!-- Page header (no button here — moved to tab bar) -->
            <header class="page-header" style="flex-shrink:0;">
                <div>
                    <p class="page-header__eyebrow">Project Management</p>
                    <h1 class="page-header__title">Projects.</h1>
                    <p class="page-header__subtitle">Track milestones, manage teams, and ship great work.</p>
                </div>
            </header>

            <!-- Tab bar — #7 New Project next to Team tab, #1 search replaces calendar -->
            <div class="proj-tabbar">
                <button class="proj-tab active" data-project-tab="all">All Projects</button>
                <button class="proj-tab" data-project-tab="mine">My Projects</button>
                <button class="proj-tab" data-project-tab="team">Team Projects</button>
                <button id="btnNewProject" class="btn btn-primary" style="height:30px;padding:0 14px;font-size:0.72rem;font-weight:800;border-radius:8px;display:inline-flex;align-items:center;gap:6px;margin:0 8px;flex-shrink:0;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    New Project
                </button>
                <span class="proj-tab-spacer"></span>
                <!-- Project search (#1 replaces calendar shortcut) -->
                <div style="position:relative;display:flex;align-items:center;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                         style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--color-text-secondary);pointer-events:none;">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input id="projSearch" type="text" placeholder="Search projects…" class="proj-select"
                           style="width:190px;height:30px;padding-left:28px;font-size:0.72rem;text-transform:none;letter-spacing:0;font-weight:500;">
                </div>
            </div>

            <!-- Filter toolbar -->
            <div class="proj-toolbar">
                <select class="proj-select" id="projStatusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="complete">Completed</option>
                    <option value="overdue">Overdue</option>
                </select>
                <select class="proj-select" id="projDeadlineFilter">
                    <option value="">Any Deadline</option>
                    <option value="soon">Due in 7 Days</option>
                    <option value="overdue">Overdue</option>
                    <option value="none">No Deadline</option>
                </select>
                <label class="proj-past-label">
                    <input type="checkbox" id="includePastProjects" style="accent-color:var(--color-primary);">
                    Include Past
                </label>
            </div>

            <!-- Main split -->
            <div class="proj-main" style="flex:1;min-height:0;">
                <!-- LEFT: card grid -->
                <div class="proj-grid-panel sanctuary-surface" style="padding:14px;">
                    <div id="projectsGrid" class="proj-grid">
                        <div class="skeleton" style="height:88px;border-radius:12px;"></div>
                        <div class="skeleton" style="height:88px;border-radius:12px;"></div>
                        <div class="skeleton" style="height:88px;border-radius:12px;"></div>
                    </div>
                </div>

                <!-- RIGHT: detail -->
                <div class="proj-detail-panel sanctuary-surface" style="padding:18px;" id="projDetailWrap">
                    <div id="projDetailEmpty" class="empty-state" style="min-height:260px;">
                        <p class="section-kicker">Detail View</p>
                        <h3>Select a Project</h3>
                        <p>Click any project card to view milestones, notes, and team details.</p>
                    </div>
                    <div id="projectDetail" style="display:none;"></div>
                </div>
            </div>

        </div><!-- end page-shell -->
    </main>
</div><!-- end app-container -->

<!-- ── New Project Modal ── -->
<div id="newProjectModal" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-container sanctuary-surface" style="max-width:540px;padding:40px;">
        <div class="modal-header" style="border:none;padding-bottom:24px;">
            <h3 class="section-title" style="font-size:20px;color:var(--color-primary);">New Project</h3>
            <button class="modal-close modal-cancel-btn">&times;</button>
        </div>
        <div class="modal-body" style="display:flex;flex-direction:column;gap:20px;">
            <div class="input-group">
                <label class="input-label" style="opacity:.5;">PROJECT NAME</label>
                <input type="text" id="newProjectName" class="input-base" placeholder="Project title" style="font-weight:700;">
            </div>
            <div class="input-group">
                <label class="input-label" style="opacity:.5;">DESCRIPTION</label>
                <textarea id="newProjectDesc" class="input-base" placeholder="What is this project about?" style="min-height:70px;"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="input-group">
                    <label class="input-label" style="opacity:.5;">DEADLINE</label>
                    <input type="date" id="newProjectDeadline" class="input-base">
                </div>
                <div class="input-group">
                    <label class="input-label" style="opacity:.5;">GITHUB REPO</label>
                    <input type="url" id="newProjectGithub" class="input-base" placeholder="https://github.com/…">
                </div>
            </div>
            <div class="input-group">
                <label class="input-label" style="opacity:.5;">COLOUR TAG</label>
                <div class="color-row" id="newProjPalette">
                    <div class="cswatch active" data-color="#e11d48" style="background:#e11d48;"></div>
                    <div class="cswatch" data-color="#7c3aed" style="background:#7c3aed;"></div>
                    <div class="cswatch" data-color="#2563eb" style="background:#2563eb;"></div>
                    <div class="cswatch" data-color="#059669" style="background:#059669;"></div>
                    <div class="cswatch" data-color="#d97706" style="background:#d97706;"></div>
                    <div class="cswatch" data-color="#db2777" style="background:#db2777;"></div>
                    <div class="cswatch" data-color="#0891b2" style="background:#0891b2;"></div>
                    <div class="cswatch" data-color="#65a30d" style="background:#65a30d;"></div>
                    <label class="cswatch" title="Custom colour" style="background:conic-gradient(red,yellow,lime,cyan,blue,magenta,red);position:relative;overflow:hidden;">
                        <input type="color" id="newProjCustomColor" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;" value="#e11d48">
                    </label>
                </div>
            </div>
            <label style="display:flex;align-items:center;gap:12px;background:rgba(var(--color-primary-rgb),0.04);padding:12px;border-radius:10px;cursor:pointer;">
                <input type="checkbox" id="newProjectIsTeam" style="width:18px;height:18px;accent-color:var(--color-primary);">
                <span style="font-size:13px;font-weight:700;">Team Project <span style="font-weight:400;color:var(--color-text-secondary);">(invite collaborators after creation)</span></span>
            </label>
        </div>
        <div class="modal-footer" style="border:none;padding-top:28px;display:flex;gap:12px;">
            <button class="btn btn-ghost modal-cancel-btn" style="flex:1;">Cancel</button>
            <button class="btn btn-primary" id="btnCreateProject" style="flex:1;font-weight:900;">Create Project</button>
        </div>
    </div>
</div>

<!-- ── Project Settings Modal ── -->
<div id="projSettingsModal" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-container sanctuary-surface" style="max-width:820px;width:95vw;padding:0;overflow:hidden;">
        <!-- Modal header -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--glass-border);">
            <h3 class="section-title" style="font-size:17px;color:var(--color-primary);margin:0;">⚙ Project Settings</h3>
            <button class="modal-cancel-btn" style="width:30px;height:30px;border:none;background:transparent;cursor:pointer;color:var(--color-text-secondary);font-size:20px;border-radius:6px;display:flex;align-items:center;justify-content:center;transition:all 0.15s;" onmouseover="this.style.background='rgba(var(--color-primary-rgb),0.1)'" onmouseout="this.style.background=''">&times;</button>
        </div>
        <!-- 3-column body (starts at 2; expands to 3 when task clicked) -->
        <div id="settingsBodyGrid" style="display:grid;grid-template-columns:200px 1fr;min-height:380px;max-height:70vh;">

            <!-- COL 1: Config -->
            <div style="padding:20px;border-right:1px solid var(--glass-border);display:flex;flex-direction:column;gap:16px;overflow-y:auto;">
                <div>
                    <span class="proj-lbl">Colour Tag</span>
                    <div class="color-row" id="settingsPalette" style="gap:6px;margin-top:6px;">
                        <div class="cswatch active" data-color="#e11d48" style="background:#e11d48;width:24px;height:24px;"></div>
                        <div class="cswatch" data-color="#7c3aed" style="background:#7c3aed;width:24px;height:24px;"></div>
                        <div class="cswatch" data-color="#2563eb" style="background:#2563eb;width:24px;height:24px;"></div>
                        <div class="cswatch" data-color="#059669" style="background:#059669;width:24px;height:24px;"></div>
                        <div class="cswatch" data-color="#d97706" style="background:#d97706;width:24px;height:24px;"></div>
                        <div class="cswatch" data-color="#db2777" style="background:#db2777;width:24px;height:24px;"></div>
                        <div class="cswatch" data-color="#0891b2" style="background:#0891b2;width:24px;height:24px;"></div>
                        <div class="cswatch" data-color="#65a30d" style="background:#65a30d;width:24px;height:24px;"></div>
                        <label class="cswatch" title="Custom" style="background:conic-gradient(red,yellow,lime,cyan,blue,magenta,red);position:relative;overflow:hidden;width:24px;height:24px;">
                            <input type="color" id="settingsCustomColor" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">
                        </label>
                    </div>
                    <button id="btnApplyColor" class="btn btn-primary" style="margin-top:10px;width:100%;height:32px;font-size:0.75rem;">Apply Colour</button>
                </div>
                <div style="margin-top:auto;display:flex;flex-direction:column;gap:8px;">
                    <!-- #5: Mark as Complete -->
                    <button id="btnMarkComplete" class="btn btn-success" style="width:100%;min-height:30px;padding:6px;font-size:0.65rem;text-transform:uppercase;white-space:normal;line-height:1.2;color:#ffffff !important;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
                        Mark as Complete
                    </button>
                    <div style="padding:12px;background:rgba(239,68,68,0.06);border-radius:8px;border:1px solid rgba(239,68,68,0.14);">
                        <p style="font-size:0.73rem;color:var(--color-text-secondary);margin:0 0 8px;line-height:1.4;">Delete project and all its data permanently.</p>
                        <button id="btnConfirmDelete" class="btn btn-danger" style="width:100%;height:30px;font-size:0.75rem;">Delete Project</button>
                    </div>
                </div>
            </div>

            <!-- COL 2: Task Log -->
            <div style="padding:20px;border-right:1px solid var(--glass-border);overflow-y:auto;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span class="proj-lbl" style="margin:0;">Completed Task Log</span>
                </div>
                <div id="settingsTaskLog">
                    <div style="text-align:center;padding:30px;color:var(--color-text-secondary);font-size:0.78rem;">Loading task log…</div>
                </div>
            </div>

            <!-- COL 3: Task Detail — hidden until a task is clicked (#4) -->
            <div id="settingsTaskDetail" style="overflow-y:auto;display:none;"></div>

        </div>
    </div>
</div>

<script src="assets/js/utils.js"></script>
<script src="assets/js/api.js"></script>
<script src="assets/js/store.js"></script>
<script src="assets/js/projects.js"></script>
</body>
</html>
