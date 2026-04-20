/* ═══════════════════════════════════════════════════════════
   PROJECTS MODULE
   ═══════════════════════════════════════════════════════════ */

const projState = {
    tab: 'all',
    statusFilter: '',
    deadlineFilter: '',
    includePast: false,
    taskFilter: 'all',      // 'all' | 'mine'
    taskSearch: '',
    taskSort: '',           // '' | 'priority' | 'name'
    taskPrioFil: '',        // '' | 'high' | 'medium' | 'low'
    taskTypeFil: '',        // '' | 'milestone' | 'task'
    projects: [],
    selectedId: null,
    newColor: '#e11d48',
    settingsColor: '#e11d48',
};

/* Priority weight for sorting */
const PRIO_ORDER = { high: 0, medium: 1, low: 2 };

/* ── Boot ── */
document.addEventListener('DOMContentLoaded', async () => {
    bindControls();
    await loadProjects();
});

/* ── Bind top-level controls ── */
function bindControls() {
    document.querySelectorAll('[data-project-tab]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('[data-project-tab]').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            projState.tab = btn.dataset.projectTab;
            renderGrid();
        });
    });

    document.getElementById('projStatusFilter')?.addEventListener('change', e => {
        projState.statusFilter = e.target.value;
        renderGrid();
    });
    document.getElementById('projDeadlineFilter')?.addEventListener('change', e => {
        projState.deadlineFilter = e.target.value;
        renderGrid();
    });
    document.getElementById('includePastProjects')?.addEventListener('change', async e => {
        projState.includePast = e.target.checked;
        await loadProjects();
    });

    document.getElementById('btnNewProject')?.addEventListener('click', () => openModal('newProjectModal'));
    document.getElementById('btnCreateProject')?.addEventListener('click', createProject);
    document.getElementById('projCalBtn')?.addEventListener('click', () => window.location.href = 'todo.php');

    bindPalette('newProjPalette', 'newProjCustomColor', c => projState.newColor = c);
    bindPalette('settingsPalette', 'settingsCustomColor', c => projState.settingsColor = c);

    document.getElementById('btnApplyColor')?.addEventListener('click', applySettingsColor);
    document.getElementById('btnConfirmDelete')?.addEventListener('click', confirmDeleteProject);
}

function bindPalette(paletteId, customId, onChange) {
    const pal = document.getElementById(paletteId);
    if (!pal) return;
    pal.querySelectorAll('[data-color]').forEach(sw => {
        sw.addEventListener('click', () => {
            pal.querySelectorAll('.cswatch').forEach(s => s.classList.remove('active'));
            sw.classList.add('active');
            onChange(sw.dataset.color);
        });
    });
    const custom = document.getElementById(customId);
    if (custom) {
        custom.addEventListener('change', () => {
            pal.querySelectorAll('.cswatch').forEach(s => s.classList.remove('active'));
            custom.closest('.cswatch')?.classList.add('active');
            onChange(custom.value);
        });
    }
}

/* ══════════════════════════════════════════════
   LOAD & RENDER GRID
   ══════════════════════════════════════════════ */

async function loadProjects() {
    const grid = document.getElementById('projectsGrid');
    grid.innerHTML = `
        <div class="skeleton" style="height:88px;border-radius:12px;"></div>
        <div class="skeleton" style="height:88px;border-radius:12px;"></div>
        <div class="skeleton" style="height:88px;border-radius:12px;"></div>`;

    try {
        const res = await API.getProjects(projState.includePast);
        const raw = res.data || [];

        projState.projects = await Promise.all(raw.map(async p => {
            try {
                const sub = await API.getProjectSubtasks(p.id);
                const all = sub.data || [];
                const done = all.filter(s => Number(s.is_completed)).length;
                const progress = all.length ? Math.round((done / all.length) * 100) : 0;
                return { ...p, subtasks: all, progress };
            } catch {
                return { ...p, subtasks: [], progress: 0 };
            }
        }));

        renderGrid();

        // ── FIX #4: Only re-open if already had a selection; never auto-open first card ──
        if (projState.selectedId && projState.projects.some(p => Number(p.id) === projState.selectedId)) {
            await openDetail(projState.selectedId);
        } else {
            showEmpty();
        }
    } catch {
        grid.innerHTML = `<div class="empty-state"><h3>Could not load projects</h3><p>Refresh to try again.</p></div>`;
        showEmpty();
    }
}

function getVisible() {
    const uid = Number(store.user?.id || 0);
    const now = new Date();
    const in7 = new Date(); in7.setDate(now.getDate() + 7);

    return projState.projects.filter(p => {
        if (projState.tab === 'team' && !Number(p.is_team)) return false;
        if (projState.tab === 'mine' && uid && Number(p.owner_id) !== uid) return false;

        if (projState.statusFilter === 'active' && Number(p.is_past)) return false;
        if (projState.statusFilter === 'complete' && p.progress < 100) return false;
        if (projState.statusFilter === 'overdue') {
            if (!p.deadline || new Date(p.deadline) >= now) return false;
        }

        if (projState.deadlineFilter === 'soon') {
            if (!p.deadline) return false;
            const d = new Date(p.deadline);
            if (d < now || d > in7) return false;
        }
        if (projState.deadlineFilter === 'overdue') {
            if (!p.deadline || new Date(p.deadline) >= now) return false;
        }
        if (projState.deadlineFilter === 'none' && p.deadline) return false;

        return true;
    });
}

function renderGrid() {
    const grid = document.getElementById('projectsGrid');
    const list = getVisible();

    if (!list.length) {
        grid.innerHTML = `<div class="empty-state" style="min-height:160px;"><h3>No projects found</h3><p>Adjust filters or create a new project.</p></div>`;
        return;
    }

    grid.innerHTML = list.map(p => {
        const clr = p.colour || 'var(--color-primary)';
        const pct = Number(p.is_past) ? 100 : p.progress;
        const sel = Number(p.id) === projState.selectedId;
        const team = Number(p.is_team);
        const past = Number(p.is_past);
        const dl = p.deadline ? fmtDate(p.deadline) : 'No deadline';
        const tasks = (p.subtasks || []).length;

        return `
        <button class="proj-card ${sel ? 'is-selected' : ''} ${past ? 'is-past' : ''}"
                style="--card-clr:${clr};" data-pid="${p.id}" type="button">
            <div class="proj-card__top">
                <span class="proj-badge ${team ? 'team' : ''}">${team ? 'Team' : 'Personal'}</span>
                ${past ? '<span class="proj-badge past">Completed</span>' : ''}
            </div>
            <div class="proj-card__name">${escapeHTML(p.name)}</div>
            <div class="proj-card__bar-row">
                <div class="proj-card__bar">
                    <div class="proj-card__fill" style="background:${clr};" data-pct="${pct}"></div>
                </div>
                <span class="proj-card__pct">${pct}%</span>
            </div>
            <div class="proj-card__footer">
                <span>📅 ${dl}</span>
                <span>${tasks} task${tasks !== 1 ? 's' : ''}</span>
            </div>
        </button>`;
    }).join('');

    requestAnimationFrame(() => {
        grid.querySelectorAll('[data-pct]').forEach(el => { el.style.width = el.dataset.pct + '%'; });
    });

    grid.querySelectorAll('[data-pid]').forEach(btn => {
        btn.addEventListener('click', () => openDetail(btn.dataset.pid));
    });
}

function showEmpty() {
    document.getElementById('projDetailEmpty').style.display = '';
    document.getElementById('projectDetail').style.display = 'none';
}

/* ══════════════════════════════════════════════
   PROJECT DETAIL
   ══════════════════════════════════════════════ */

async function openDetail(pid) {
    projState.selectedId = Number(pid);
    renderGrid();

    document.getElementById('projDetailEmpty').style.display = 'none';
    const panel = document.getElementById('projectDetail');
    panel.style.display = '';
    panel.innerHTML = `<div class="skeleton" style="height:380px;border-radius:14px;"></div>`;

    try {
        const res = await API.getProject(pid);
        const proj = res.data;

        const isTeam = Number(proj.is_team);
        const uid = Number(store.user?.id || 0);
        const isOwner = Number(proj.owner_id) === uid;
        const members = proj.members || [];
        const subs = proj.subtasks || [];

        // ── FIX #5: canEdit = owner OR team leader role ──
        const myRole = members.find(m => Number(m.id) === uid)?.role;
        const canEdit = isOwner || myRole === 'leader';

        let chat = [];
        if (isTeam) {
            try { chat = (await API.getChat(pid, 40)).data || []; } catch { }
        }

        panel.innerHTML = buildDetail(proj, subs, members, chat, isTeam, canEdit, uid);
        wireDetail(proj, subs, members, isTeam, canEdit, uid);

    } catch (err) {
        panel.innerHTML = `<div class="empty-state" style="min-height:260px;"><h3>Detail unavailable</h3><p>${escapeHTML(err.message)}</p></div>`;
    }
}

/* ── Filter/sort subtasks ── */
function applyTaskFilters(subs, isTeam, uid) {
    let result = [...subs];

    // Team "my tasks" filter
    if (projState.taskFilter === 'mine' && isTeam) {
        result = result.filter(s => Number(s.assigned_to) === uid || !s.assigned_to);
    }

    // Only show incomplete by default (completed goes to Task Log)
    result = result.filter(s => !Number(s.is_completed));

    // ── FIX #3: Search ──
    if (projState.taskSearch.trim()) {
        const q = projState.taskSearch.toLowerCase();
        result = result.filter(s => s.title.toLowerCase().includes(q));
    }

    // Priority filter
    if (projState.taskPrioFil) {
        result = result.filter(s => (s.priority || 'medium') === projState.taskPrioFil);
    }

    // Type filter
    if (projState.taskTypeFil === 'milestone') {
        result = result.filter(s => Number(s.is_milestone));
    } else if (projState.taskTypeFil === 'task') {
        result = result.filter(s => !Number(s.is_milestone));
    }

    // Sort
    if (projState.taskSort === 'priority') {
        result.sort((a, b) => (PRIO_ORDER[a.priority || 'medium'] || 1) - (PRIO_ORDER[b.priority || 'medium'] || 1));
    } else if (projState.taskSort === 'name') {
        result.sort((a, b) => a.title.localeCompare(b.title));
    }

    return result;
}

/* ── Build detail HTML ── */
function buildDetail(proj, subs, members, chat, isTeam, canEdit, uid) {
    const listedSubs = applyTaskFilters(subs, isTeam, uid);

    const pencilSVG = `<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`;

    return `
    <div class="proj-detail-split">

        <!-- ── LEFT: Info ── -->
        <div class="proj-section">

            <!-- Title -->
            <div class="proj-editable">
                <span class="proj-lbl">Project</span>
                <h2 id="detTitle" style="font-family:'Space Grotesk',sans-serif;font-size:1.25rem;font-weight:800;color:var(--color-text-primary);margin:0;padding-right:30px;line-height:1.25;word-break:break-word;">${escapeHTML(proj.name)}</h2>
                ${canEdit ? `<button class="proj-pencil" id="btnEditTitle" title="Edit title">${pencilSVG}</button>` : ''}
            </div>

            <!-- GitHub -->
            <div>
                <span class="proj-lbl">Repository</span>
                ${proj.github_repo
            ? `<a href="${escapeHTML(proj.github_repo)}" target="_blank" rel="noreferrer"
                          style="font-size:0.8rem;color:var(--color-primary);word-break:break-all;text-decoration:none;"
                          onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                           🔗 ${escapeHTML(proj.github_repo)}</a>`
            : `<span style="font-size:0.8rem;color:var(--color-text-secondary);">No repository linked</span>`}
            </div>

            <!-- Deadline -->
            ${proj.deadline ? `
            <div>
                <span class="proj-lbl">Deadline</span>
                <span style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;font-weight:600;color:var(--color-text-primary);">📅 ${fmtDate(proj.deadline)}</span>
            </div>` : ''}

            <!-- Description -->
            <div class="proj-editable">
                <span class="proj-lbl">Description</span>
                <p id="detDesc" style="font-size:0.82rem;color:var(--color-text-secondary);line-height:1.6;margin:0;padding-right:30px;min-height:36px;">${escapeHTML(proj.description || 'No description yet.')}</p>
                ${canEdit ? `<button class="proj-pencil" id="btnEditDesc" title="Edit description">${pencilSVG}</button>` : ''}
            </div>

            <!-- Team members -->
            ${isTeam ? `
            <div style="padding-top:12px;border-top:1px solid var(--glass-border);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <span class="proj-lbl" style="margin:0;">Team Members</span>
                    ${canEdit ? `<button id="btnToggleInvite" style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;padding:3px 10px;border-radius:6px;border:1px solid var(--glass-border);background:transparent;color:var(--color-primary);cursor:pointer;">Manage</button>` : ''}
                </div>
                <div id="membersList">${buildMembers(members, canEdit, proj.id)}</div>
                ${canEdit ? `
                <div id="inviteBox" style="display:none;margin-top:10px;">
                    <div style="display:flex;gap:7px;">
                        <input id="inviteInput" class="input-base" placeholder="Username to invite" style="flex:1;height:32px;font-size:0.78rem;padding:0 10px;">
                        <button id="btnDoInvite" class="btn btn-primary" style="height:32px;padding:0 12px;font-size:0.72rem;">Invite</button>
                    </div>
                </div>` : ''}
            </div>` : ''}

            <!-- Settings row -->
            <div class="proj-settings-row">
                <span style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:var(--color-text-secondary);">ID #${proj.id}</span>
                <button id="btnOpenSettings" style="display:flex;align-items:center;gap:6px;padding:5px 12px;border-radius:8px;border:1px solid var(--glass-border);background:transparent;cursor:pointer;color:var(--color-text-secondary);font-family:'JetBrains Mono',monospace;font-size:0.63rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;transition:all 0.15s;" onmouseover="this.style.color='var(--color-primary)';this.style.borderColor='var(--color-primary)';" onmouseout="this.style.color='';this.style.borderColor='';">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Settings
                </button>
            </div>
        </div>

        <!-- ── RIGHT: Tasks + Notes + Chat ── -->
        <div class="proj-section">

            <!-- ── FIX #3: Search / filter / sort toolbar ── -->
            <div style="display:flex;gap:7px;flex-wrap:wrap;align-items:center;">
                <div style="flex:1;min-width:120px;position:relative;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--color-text-secondary);pointer-events:none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input id="taskSearchInput" class="proj-select" placeholder="Search tasks…" value="${escapeHTML(projState.taskSearch)}" style="width:100%;height:30px;padding-left:28px;font-size:0.7rem;text-transform:none;letter-spacing:0;">
                </div>
                <select id="taskPrioFil" class="proj-select" style="height:30px;">
                    <option value="">All Priority</option>
                    <option value="high"   ${projState.taskPrioFil === 'high' ? 'selected' : ''}>🔴 High</option>
                    <option value="medium" ${projState.taskPrioFil === 'medium' ? 'selected' : ''}>🟡 Medium</option>
                    <option value="low"    ${projState.taskPrioFil === 'low' ? 'selected' : ''}>🟢 Low</option>
                </select>
                <select id="taskTypeFil" class="proj-select" style="height:30px;">
                    <option value="">All Types</option>
                    <option value="milestone" ${projState.taskTypeFil === 'milestone' ? 'selected' : ''}>🏁 Milestones</option>
                    <option value="task"      ${projState.taskTypeFil === 'task' ? 'selected' : ''}>☑ Tasks</option>
                </select>
                <select id="taskSort" class="proj-select" style="height:30px;">
                    <option value="">Default</option>
                    <option value="priority" ${projState.taskSort === 'priority' ? 'selected' : ''}>Sort: Priority</option>
                    <option value="name"     ${projState.taskSort === 'name' ? 'selected' : ''}>Sort: A–Z</option>
                </select>
            </div>

            <!-- Team task filter -->
            ${isTeam ? `
            <div style="display:flex;align-items:center;gap:14px;">
                <label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-family:'JetBrains Mono',monospace;font-size:0.63rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:${projState.taskFilter === 'all' ? 'var(--color-primary)' : 'var(--color-text-secondary)'};">
                    <input type="radio" name="taskFil" value="all" ${projState.taskFilter === 'all' ? 'checked' : ''} style="accent-color:var(--color-primary);"> All Tasks
                </label>
                <label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-family:'JetBrains Mono',monospace;font-size:0.63rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:${projState.taskFilter === 'mine' ? 'var(--color-primary)' : 'var(--color-text-secondary)'};">
                    <input type="radio" name="taskFil" value="mine" ${projState.taskFilter === 'mine' ? 'checked' : ''} style="accent-color:var(--color-primary);"> My Tasks
                </label>
            </div>` : ''}

            <!-- Subtasks/milestones -->
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <span class="proj-lbl" style="margin:0;">Milestones &amp; Tasks</span>
                    <button id="btnToggleAdd" style="width:24px;height:24px;border-radius:6px;border:1px solid var(--glass-border);background:transparent;cursor:pointer;color:var(--color-primary);font-size:17px;display:flex;align-items:center;justify-content:center;line-height:1;transition:all 0.15s;" title="Add task">+</button>
                </div>
                <div id="subtaskList">${buildSubtasks(listedSubs, members)}</div>

                <!-- ── FIX #2: Cleaner add-task form ── -->
                <div id="addStaskForm" class="add-stask-form" style="display:none;">
                    <input id="newStaskTitle" placeholder="Task or milestone title…" style="flex:1;">
                    <select id="newStaskType">
                        <option value="task">☑ Task</option>
                        <option value="milestone">🏁 Milestone</option>
                    </select>
                    <select id="newStaskPriority">
                        <option value="high">🔴 High</option>
                        <option value="medium" selected>🟡 Medium</option>
                        <option value="low">🟢 Low</option>
                    </select>
                    ${isTeam ? `
                    <select id="newStaskAssign">
                        <option value="">Unassigned</option>
                        ${members.map(m => `<option value="${m.id}">${escapeHTML(m.username)}</option>`).join('')}
                    </select>` : ''}
                    <button id="btnSaveStask" class="btn btn-primary" style="height:32px;padding:0 14px;font-size:0.73rem;white-space:nowrap;">Add</button>
                </div>
            </div>

            <!-- Notes -->
            <div style="padding-top:12px;border-top:1px solid var(--glass-border);">
                <span class="proj-lbl">Project Notes</span>
                <textarea class="proj-notes" id="projNotes" placeholder="Add notes, links, context…" ${!canEdit ? 'readonly style="opacity:0.6;cursor:default;"' : ''}>${escapeHTML(proj.notes || '')}</textarea>
                ${canEdit ? `
                <div style="display:flex;justify-content:flex-end;margin-top:5px;">
                    <button id="btnSaveNotes" class="btn btn-ghost" style="height:28px;padding:0 12px;font-size:0.7rem;">Save Notes</button>
                </div>` : ''}
            </div>

            <!-- Team Chat -->
            ${isTeam ? `
            <div style="padding-top:12px;border-top:1px solid var(--glass-border);flex:1;display:flex;flex-direction:column;gap:8px;">
                <span class="proj-lbl">Team Chat <span style="font-weight:400;opacity:.6;">(use @username for private)</span></span>
                <div class="chat-feed" id="chatFeed">${buildChat(chat, uid)}</div>
                <div style="display:flex;gap:7px;">
                    <input id="chatInput" class="input-base" placeholder="Message the team…" style="flex:1;height:34px;font-size:0.78rem;padding:0 10px;">
                    <button id="btnSendChat" class="btn btn-primary" style="height:34px;padding:0 12px;font-size:0.72rem;">Send</button>
                </div>
            </div>` : ''}

        </div>
    </div>`;
}

/* ── Build members HTML ── */
function buildMembers(members, canEdit, projId) {
    if (!members.length) return `<p style="font-size:0.78rem;color:var(--color-text-secondary);">No members yet.</p>`;
    return members.map(m => `
    <div class="member-row" data-mid="${m.id}">
        <div class="member-av">${(m.username || '?')[0].toUpperCase()}</div>
        <span class="member-name">${escapeHTML(m.full_name || m.username)}</span>
        <span class="member-role ${m.role === 'leader' ? 'leader' : ''}">${m.role || 'member'}</span>
        ${canEdit ? `
        <button class="btn-rm-member" data-uid="${m.id}" data-pid="${projId}"
                style="width:20px;height:20px;border:none;background:transparent;cursor:pointer;color:var(--color-text-secondary);border-radius:4px;font-size:15px;display:flex;align-items:center;justify-content:center;transition:color 0.15s;"
                onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color=''"
                title="Remove member">×</button>` : ''}
    </div>`).join('');
}

/* ── Build subtasks HTML ── */
function buildSubtasks(subs, members) {
    if (!subs.length) return `<div style="text-align:center;padding:18px;color:var(--color-text-secondary);font-size:0.8rem;">No tasks match. Click + to add one.</div>`;
    return subs.map(s => {
        const done = Number(s.is_completed);
        const prio = s.priority || 'medium';
        const isMile = Number(s.is_milestone);
        const assignee = members.find(m => Number(m.id) === Number(s.assigned_to));
        return `
        <div class="stask ${done ? 'done' : ''}" data-sid="${s.id}">
            <input type="checkbox" class="sanctuary-checkbox stask-cb" data-sid="${s.id}" ${done ? 'checked' : ''} style="flex-shrink:0;margin-top:3px;">
            <div style="flex:1;min-width:0;">
                <div class="stask-title">${isMile ? '🏁 ' : ''}${escapeHTML(s.title)}</div>
                <div class="stask-meta">
                    <span class="prio prio-${prio}">${prio}</span>
                    ${assignee ? `<span style="font-size:0.68rem;color:var(--color-text-secondary);">@${escapeHTML(assignee.username)}</span>` : ''}
                    ${isMile ? `<span style="font-size:0.65rem;color:var(--color-text-secondary);font-family:'JetBrains Mono',monospace;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Milestone</span>` : ''}
                </div>
            </div>
            <button class="stask-del" data-del="${s.id}" title="Delete task">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>`;
    }).join('');
}

/* ── Build chat HTML ── */
function buildChat(msgs, uid) {
    if (!msgs.length) return `<div style="text-align:center;padding:16px;color:var(--color-text-secondary);font-size:0.78rem;">No messages yet.</div>`;
    return msgs.map(m => {
        const priv = Number(m.is_private);
        return `
        <div class="chat-bubble ${priv ? 'private' : ''}">
            <div class="chat-bubble__hd">
                <span class="chat-bubble__name">${escapeHTML(m.sender_name || 'User')}</span>
                <span class="chat-bubble__time">${relTime(m.created_at)}</span>
                ${priv ? `<span style="font-family:'JetBrains Mono',monospace;font-size:0.57rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:1px 5px;border-radius:3px;background:rgba(139,92,246,0.15);color:#a78bfa;">Private</span>` : ''}
            </div>
            <div class="chat-bubble__body">${hlMentions(escapeHTML(m.message))}</div>
        </div>`;
    }).join('');
}

function hlMentions(html) {
    return html.replace(/@(\w+)/g, '<span class="mention">@$1</span>');
}

/* ══════════════════════════════════════════════
   WIRE DETAIL EVENTS
   ══════════════════════════════════════════════ */

function wireDetail(proj, subs, members, isTeam, canEdit, uid) {
    const pid = proj.id;

    /* ── FIX #3: Search / filter / sort ── */
    document.getElementById('taskSearchInput')?.addEventListener('input', e => {
        projState.taskSearch = e.target.value;
        const filtered = applyTaskFilters(subs, isTeam, uid);
        document.getElementById('subtaskList').innerHTML = buildSubtasks(filtered, members);
        wireSubtaskEvents(pid);
    });
    document.getElementById('taskPrioFil')?.addEventListener('change', e => {
        projState.taskPrioFil = e.target.value;
        const filtered = applyTaskFilters(subs, isTeam, uid);
        document.getElementById('subtaskList').innerHTML = buildSubtasks(filtered, members);
        wireSubtaskEvents(pid);
    });
    document.getElementById('taskTypeFil')?.addEventListener('change', e => {
        projState.taskTypeFil = e.target.value;
        const filtered = applyTaskFilters(subs, isTeam, uid);
        document.getElementById('subtaskList').innerHTML = buildSubtasks(filtered, members);
        wireSubtaskEvents(pid);
    });
    document.getElementById('taskSort')?.addEventListener('change', e => {
        projState.taskSort = e.target.value;
        const filtered = applyTaskFilters(subs, isTeam, uid);
        document.getElementById('subtaskList').innerHTML = buildSubtasks(filtered, members);
        wireSubtaskEvents(pid);
    });

    /* Team task filter radios */
    document.querySelectorAll('[name="taskFil"]').forEach(r => {
        r.addEventListener('change', () => { projState.taskFilter = r.value; openDetail(pid); });
    });

    /* ── FIX #5: Only wire pencil buttons if canEdit ── */
    if (canEdit) {
        document.getElementById('btnEditTitle')?.addEventListener('click', () => {
            const el = document.getElementById('detTitle');
            const old = el.textContent.trim();
            el.innerHTML = `<input id="etInput" value="${escapeHTML(old)}" style="width:100%;padding:4px 8px;border-radius:6px;border:1px solid var(--color-primary);background:var(--color-background);color:var(--color-text-primary);font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:800;outline:none;">`;
            const inp = document.getElementById('etInput');
            inp.focus(); inp.select();
            const save = () => { if (inp.value.trim()) saveField(pid, { name: inp.value.trim() }, () => openDetail(pid)); else el.textContent = old; };
            inp.addEventListener('blur', save);
            inp.addEventListener('keydown', e => { if (e.key === 'Enter') inp.blur(); if (e.key === 'Escape') { el.textContent = old; } });
        });

        document.getElementById('btnEditDesc')?.addEventListener('click', () => {
            const el = document.getElementById('detDesc');
            const old = proj.description || '';
            el.innerHTML = `<textarea id="edInput" style="width:100%;min-height:70px;padding:7px 10px;border-radius:6px;border:1px solid var(--color-primary);background:var(--color-background);color:var(--color-text-primary);font-family:'Inter',sans-serif;font-size:0.82rem;resize:vertical;outline:none;">${escapeHTML(old)}</textarea>`;
            const ta = document.getElementById('edInput');
            ta.focus();
            ta.addEventListener('blur', () => saveField(pid, { description: ta.value.trim() }, () => openDetail(pid)));
            ta.addEventListener('keydown', e => { if (e.key === 'Escape') ta.blur(); });
        });

        document.getElementById('btnSaveNotes')?.addEventListener('click', async () => {
            const notes = document.getElementById('projNotes')?.value || '';
            await saveField(pid, { notes }, () => showToast('Notes saved.', 'success'));
        });

        document.getElementById('btnToggleInvite')?.addEventListener('click', () => {
            const box = document.getElementById('inviteBox');
            if (box) box.style.display = box.style.display === 'none' ? '' : 'none';
        });
        document.getElementById('btnDoInvite')?.addEventListener('click', () => inviteMember(pid));
        document.getElementById('inviteInput')?.addEventListener('keydown', e => { if (e.key === 'Enter') inviteMember(pid); });

        document.querySelectorAll('.btn-rm-member').forEach(btn => {
            btn.addEventListener('click', async () => {
                await API.removeMember({ project_id: pid, user_id: Number(btn.dataset.uid) });
                showToast('Member removed.', 'success');
                await openDetail(pid);
            });
        });
    }

    /* Toggle add-task form */
    document.getElementById('btnToggleAdd')?.addEventListener('click', () => {
        const f = document.getElementById('addStaskForm');
        const hidden = f.style.display === 'none';
        f.style.display = hidden ? 'flex' : 'none';
        if (hidden) document.getElementById('newStaskTitle')?.focus();
    });

    document.getElementById('btnSaveStask')?.addEventListener('click', () => addSubtask(pid));
    document.getElementById('newStaskTitle')?.addEventListener('keydown', e => { if (e.key === 'Enter') addSubtask(pid); });

    wireSubtaskEvents(pid);

    /* Chat */
    document.getElementById('btnSendChat')?.addEventListener('click', () => sendChat(pid));
    document.getElementById('chatInput')?.addEventListener('keydown', e => { if (e.key === 'Enter') sendChat(pid); });

    /* Settings */
    document.getElementById('btnOpenSettings')?.addEventListener('click', () => openSettings(proj, subs, canEdit));
}

function wireSubtaskEvents(pid) {
    document.querySelectorAll('.stask-cb').forEach(cb => {
        cb.addEventListener('change', async () => {
            await API.toggleSubtask(Number(cb.dataset.sid), cb.checked ? 1 : 0);
            await loadProjects();
            await openDetail(pid);
        });
    });

    document.querySelectorAll('[data-del]').forEach(btn => {
        btn.addEventListener('click', async e => {
            e.stopPropagation();
            await API.deleteSubtask(Number(btn.dataset.del));
            await loadProjects();
            await openDetail(pid);
        });
    });
}

/* ══════════════════════════════════════════════
   ACTIONS
   ══════════════════════════════════════════════ */

async function saveField(pid, data, onDone) {
    try {
        await API.updateProject(pid, data);
        await loadProjects();
        if (onDone) onDone();
    } catch (err) {
        showToast(err.message || 'Save failed.', 'danger');
    }
}

async function addSubtask(pid) {
    const titleEl = document.getElementById('newStaskTitle');
    const title = titleEl?.value.trim();
    if (!title) { showToast('Title is required.', 'warning'); return; }

    const priority = document.getElementById('newStaskPriority')?.value || 'medium';
    const typeVal = document.getElementById('newStaskType')?.value || 'task';
    const assigned_to = document.getElementById('newStaskAssign')?.value || null;
    const is_milestone = typeVal === 'milestone' ? 1 : 0;

    await API.createSubtask({ project_id: pid, title, priority, assigned_to: assigned_to || null, is_milestone });
    titleEl.value = '';
    await loadProjects();
    await openDetail(pid);
}

async function inviteMember(pid) {
    const inp = document.getElementById('inviteInput');
    const username = inp?.value.trim();
    if (!username) return;
    try {
        await API.inviteMember({ project_id: pid, username });
        showToast(`${username} invited.`, 'success');
        inp.value = '';
        await openDetail(pid);
    } catch (err) {
        showToast(err.message || 'Invite failed.', 'danger');
    }
}

async function sendChat(pid) {
    const inp = document.getElementById('chatInput');
    const msg = inp?.value.trim();
    if (!msg) return;
    const is_private = /@\w+/.test(msg) ? 1 : 0;
    await API.sendChat({ project_id: pid, message: msg, is_private });
    inp.value = '';
    await openDetail(pid);
    const feed = document.getElementById('chatFeed');
    if (feed) feed.scrollTop = feed.scrollHeight;
}

/* ══════════════════════════════════════════════
   SETTINGS MODAL — ── FIX #1: Task Log ──
   ══════════════════════════════════════════════ */

let _settingsPid = null;
let _settingsCanEdit = false;

function openSettings(proj, subs, canEdit) {
    _settingsPid = proj.id;
    _settingsCanEdit = canEdit;
    projState.settingsColor = proj.colour || '#e11d48';

    /* Pre-select colour swatch */
    const pal = document.getElementById('settingsPalette');
    if (pal) {
        pal.querySelectorAll('[data-color]').forEach(sw => {
            sw.classList.toggle('active', sw.dataset.color === projState.settingsColor);
        });
    }

    /* ── Populate Task Log with completed subtasks ── */
    const completed = (subs || []).filter(s => Number(s.is_completed));
    const logEl = document.getElementById('settingsTaskLog');
    if (logEl) {
        if (!completed.length) {
            logEl.innerHTML = `<div style="text-align:center;padding:24px;color:var(--color-text-secondary);font-size:0.78rem;">No completed tasks yet.</div>`;
        } else {
            logEl.innerHTML = completed.map(s => {
                const prio = s.priority || 'medium';
                const isMile = Number(s.is_milestone);
                return `
                <div class="task-log-item" data-log-sid="${s.id}" style="padding:9px 10px;border-radius:8px;border:1px solid var(--glass-border);background:rgba(var(--color-primary-rgb),0.03);cursor:pointer;transition:all 0.15s;margin-bottom:6px;" onmouseover="this.style.borderColor='var(--color-primary)'" onmouseout="this.style.borderColor=''">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>
                        <span style="flex:1;font-size:0.8rem;color:var(--color-text-primary);font-weight:600;">${isMile ? '🏁 ' : ''}${escapeHTML(s.title)}</span>
                        <span class="prio prio-${prio}">${prio}</span>
                    </div>
                </div>`;
            }).join('');

            /* Bind clicks → show task detail panel */
            logEl.querySelectorAll('[data-log-sid]').forEach(item => {
                item.addEventListener('click', () => {
                    const sid = Number(item.dataset.logSid);
                    const task = completed.find(s => Number(s.id) === sid);
                    if (task) showTaskLogDetail(task);
                });
            });
        }
    }

    /* Clear detail panel */
    const detEl = document.getElementById('settingsTaskDetail');
    if (detEl) detEl.innerHTML = `<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--color-text-secondary);font-size:0.8rem;text-align:center;">Click a completed task<br>to view details.</div>`;

    openModal('projSettingsModal');
}

/* ── Show task detail (like to-do click) ── */
function showTaskLogDetail(task) {
    const detEl = document.getElementById('settingsTaskDetail');
    if (!detEl) return;

    const prio = task.priority || 'medium';
    const isMile = Number(task.is_milestone);

    detEl.innerHTML = `
    <div style="padding:16px;height:100%;overflow-y:auto;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span style="font-family:'JetBrains Mono',monospace;font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#4ade80;">Completed</span>
        </div>
        <h3 style="font-family:'Space Grotesk',sans-serif;font-size:1rem;font-weight:800;color:var(--color-text-primary);margin:0 0 12px;line-height:1.3;">${isMile ? '🏁 ' : ''}${escapeHTML(task.title)}</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <div>
                <span style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--color-text-secondary);display:block;margin-bottom:4px;">Priority</span>
                <span class="prio prio-${prio}" style="font-size:0.7rem;">${prio.charAt(0).toUpperCase() + prio.slice(1)}</span>
            </div>
            <div>
                <span style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--color-text-secondary);display:block;margin-bottom:4px;">Type</span>
                <span style="font-size:0.8rem;color:var(--color-text-primary);">${isMile ? '🏁 Milestone' : '☑ Task'}</span>
            </div>
            <div>
                <span style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--color-text-secondary);display:block;margin-bottom:4px;">Task ID</span>
                <span style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:var(--color-text-primary);">#${task.id}</span>
            </div>
            ${task.assigned_to ? `
            <div>
                <span style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--color-text-secondary);display:block;margin-bottom:4px;">Assigned To</span>
                <span style="font-size:0.8rem;color:var(--color-text-primary);">User #${task.assigned_to}</span>
            </div>` : ''}
        </div>
    </div>`;
}

async function applySettingsColor() {
    if (!_settingsPid) return;
    await API.updateProject(_settingsPid, { colour: projState.settingsColor });
    showToast('Colour updated.', 'success');
    closeModal('projSettingsModal');
    await loadProjects();
    await openDetail(_settingsPid);
}

async function confirmDeleteProject() {
    if (!_settingsPid) return;
    const proj = projState.projects.find(p => Number(p.id) === _settingsPid);
    closeModal('projSettingsModal');
    const ok = await showConfirmDialog({
        title: 'Delete project',
        message: `Delete "${proj?.name || 'this project'}" and all its data?`,
        confirmText: 'Delete',
        isDanger: true
    });
    if (!ok) return;
    await API.deleteProject(_settingsPid);
    projState.selectedId = null;
    _settingsPid = null;
    showToast('Project deleted.', 'success');
    await loadProjects();
}

/* ── Create Project ── */
async function createProject() {
    const name = document.getElementById('newProjectName')?.value.trim();
    if (!name) { showToast('Project name is required.', 'warning'); return; }

    try {
        await API.createProject({
            name,
            description: document.getElementById('newProjectDesc')?.value.trim() || null,
            deadline: document.getElementById('newProjectDeadline')?.value || null,
            github_repo: document.getElementById('newProjectGithub')?.value.trim() || null,
            colour: projState.newColor,
            is_team: document.getElementById('newProjectIsTeam')?.checked ? 1 : 0,
        });

        ['newProjectName', 'newProjectDesc', 'newProjectDeadline', 'newProjectGithub'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const cb = document.getElementById('newProjectIsTeam');
        if (cb) cb.checked = false;

        closeModal('newProjectModal');
        showToast('Project created!', 'success');
        await loadProjects();
    } catch (err) {
        showToast(err.message || 'Failed to create project.', 'danger');
    }
}

/* ── Utilities ── */
function fmtDate(v) {
    return new Date(v).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
}

function relTime(v) {
    const diff = Date.now() - new Date(v);
    const m = Math.floor(diff / 60000);
    if (m < 1) return 'just now';
    if (m < 60) return `${m}m ago`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h}h ago`;
    return new Date(v).toLocaleDateString();
}
