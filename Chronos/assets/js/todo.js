/* ═══════════════════════════════════════════════════════════
   Chronos — Todo JS
   ═══════════════════════════════════════════════════════════ */

let currentFilter      = 'all';
let currentView        = 'list';
let currentSort        = 'manual';
let activeProjectGroup = null;   // null | 'personal' | 'team'
let inlineFlagged      = false;
let todoProjects       = [];
let cachedTasks        = [];
let calendarPointer    = new Date();
let currentProjectId   = null;
let cachedCalEvents    = [];
let selectedTaskId     = null;



/* ═════════════════════════════════════════════════════════
   SORT/FILTER FLOATING DROPDOWN
   ═════════════════════════════════════════════════════════ */
function initSortFilterDropdown() {
    const sfBtn = document.getElementById('sortFilterBtn');
    const sfBox = document.getElementById('sortFilterDropdown');
    if (!sfBtn || !sfBox) return;
    let dropEl = null;

    function openDrop() {
        closeDrop();
        dropEl = createFloatingPanel(sfBtn, 'td-dropdown-float');
        dropEl.innerHTML = sfBox.innerHTML;

        dropEl.querySelectorAll('[data-sort]').forEach(btn => {
            if (btn.dataset.sort === currentSort) btn.classList.add('active');
            btn.addEventListener('click', e => {
                e.stopPropagation();
                dropEl.querySelectorAll('[data-sort]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentSort = btn.dataset.sort;
                renderTasks();
            });
        });

        const doneClone = dropEl.querySelector('#showCompletedToggle');
        const flagClone = dropEl.querySelector('#showFlaggedOnly');
        const doneOrig  = sfBox.querySelector('#showCompletedToggle');
        const flagOrig  = sfBox.querySelector('#showFlaggedOnly');
        if (doneClone) { doneClone.checked = doneOrig?.checked||false; doneClone.addEventListener('change',()=>{ if(doneOrig)doneOrig.checked=doneClone.checked; renderTasks(); }); }
        if (flagClone) { flagClone.checked = flagOrig?.checked||false; flagClone.addEventListener('change',()=>{ if(flagOrig)flagOrig.checked=flagClone.checked; renderTasks(); }); }

        sfBtn.classList.add('active');
        document.addEventListener('click', outsideClose, true);
    }
    function closeDrop() {
        dropEl?.remove(); dropEl=null;
        sfBtn.classList.remove('active');
        document.removeEventListener('click', outsideClose, true);
    }
    function outsideClose(e) { if(!dropEl?.contains(e.target)&&e.target!==sfBtn) closeDrop(); }
    sfBtn.addEventListener('click', e => { e.stopPropagation(); dropEl?closeDrop():openDrop(); });
}

/* ═════════════════════════════════════════════════════════
   INIT
   ═════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', async () => {
    bindTodoControls();
    initSortFilterDropdown();
    await Promise.all([loadTodoProjects(), fetchTasks()]);
});

/* ═════════════════════════════════════════════════════════
   BIND CONTROLS
   ═════════════════════════════════════════════════════════ */
function bindTodoControls() {

    // View toggle
    document.querySelectorAll('[data-view]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('[data-view]').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentView = btn.dataset.view;
            document.getElementById('btnCalendarJump')?.classList.toggle('hidden', currentView !== 'calendar');
            renderTasks();
        });
    });

    // Freq filter pills (All / Today / Week)
    document.addEventListener('click', e => {
        const pill = e.target.closest('[data-filter]');
        if (!pill) return;
        document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('[data-grp]').forEach(b => b.classList.remove('active'));
        pill.classList.add('active');
        currentFilter      = pill.dataset.filter;
        currentProjectId   = null;
        activeProjectGroup = null;
        fetchTasks();
    });

    // Project group toggle pills (My Projects / Team Projects)
    document.querySelectorAll('[data-grp]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const grp = btn.dataset.grp;
            if (activeProjectGroup === grp) {
                // Toggle OFF
                activeProjectGroup = null;
                btn.classList.remove('active');
            } else {
                // Toggle ON
                document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('[data-grp]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                activeProjectGroup = grp;
                currentFilter = 'all';
                currentProjectId = null;
            }
            fetchTasks();
        });
    });

    // Search
    document.getElementById('taskSearch')?.addEventListener('input', debounce(handleSearch, 250));
    document.addEventListener('click', e => {
        if (!e.target.closest('#taskSearch') && !e.target.closest('#searchSuggestions'))
            document.getElementById('searchSuggestions')?.classList.add('hidden');
    });

    // Calendar jump
    document.getElementById('btnCalendarJump')?.addEventListener('click', jumpToCalendarDate);

    // Inline adder
    document.getElementById('adderPlaceholder')?.addEventListener('click', () => expandAdder());
    document.getElementById('btnCancelAdd')?.addEventListener('click', resetInlineAdder);
    document.getElementById('btnSubmitInlineTask')?.addEventListener('click', submitInlineTask);
    document.getElementById('inlineTaskTitle')?.addEventListener('keydown', e => { if(e.key==='Enter') submitInlineTask(); });
    document.getElementById('toggleFlag')?.addEventListener('click', () => {
        inlineFlagged = !inlineFlagged;
        document.getElementById('toggleFlag')?.classList.toggle('active', inlineFlagged);
    });

    // Custom pickers
    const dT=document.getElementById('inlineTaskDateBtn'), dH=document.getElementById('inlineTaskDate'), dD=document.getElementById('inlineTaskDateDisplay');
    if(dT&&dH) new ChronosDatePicker({ trigger:dT, hidden:dH, display:dD });
    const rT=document.getElementById('inlineTaskReminderBtn'), rH=document.getElementById('inlineTaskReminder'), rD=document.getElementById('inlineTaskReminderDisplay');
    if(rT&&rH) new ChronosDateTimePicker({ trigger:rT, hidden:rH, display:rD });

    // Detail panel close
    document.getElementById('closeDetailBtn')?.addEventListener('click', () => {
        document.getElementById('todoDetailCol')?.classList.remove('visible');
        selectedTaskId = null;
        document.querySelectorAll('[data-task-row]').forEach(r => r.classList.remove('is-selected'));
    });
}

/* ═════════════════════════════════════════════════════════
   PROJECTS (populate select only; pills are static)
   ═════════════════════════════════════════════════════════ */
async function loadTodoProjects() {
    const select = document.getElementById('inlineTaskProject');
    try {
        const res = await API.getProjects(true);
        todoProjects = res.data || [];
        if (select) {
            select.innerHTML = ['<option value="">No project</option>'].concat(
                todoProjects.map(p => `<option value="${p.id}">${escapeHTML(p.name)}</option>`)
            ).join('');
        }
    } catch (_) {}
}

/* ═════════════════════════════════════════════════════════
   ADDER
   ═════════════════════════════════════════════════════════ */
function expandAdder(prefillDate = null) {
    document.getElementById('adderPlaceholder')?.classList.add('hidden');
    document.getElementById('adderExpanded')?.classList.remove('hidden');
    if (prefillDate) {
        const h=document.getElementById('inlineTaskDate');
        const d=document.getElementById('inlineTaskDateDisplay');
        const t=document.getElementById('inlineTaskDateBtn');
        if(h) h.value=prefillDate;
        if(d) d.textContent=new Date(prefillDate+'T00:00:00').toLocaleDateString([],{month:'short',day:'numeric',year:'numeric'});
        if(t) t.classList.add('has-value');
    }
    document.getElementById('inlineTaskTitle')?.focus();
}

async function submitInlineTask() {
    const title = document.getElementById('inlineTaskTitle')?.value.trim();
    if (!title) { showToast('Task title is required.','warning'); return; }
    await API.createTask({
        title,
        deadline:      document.getElementById('inlineTaskDate')?.value || null,
        reminder_time: document.getElementById('inlineTaskReminder')?.value || null,
        tag:           document.getElementById('inlineTaskTag')?.value.trim() || null,
        project_id:    document.getElementById('inlineTaskProject')?.value || null,
        description:   document.getElementById('inlineTaskDesc')?.value.trim() || null,
        is_flagged:    inlineFlagged ? 1 : 0,
    });
    showToast('Task created.','success');
    resetInlineAdder();
    await fetchTasks();
}

function resetInlineAdder() {
    ['inlineTaskTitle','inlineTaskTag','inlineTaskDesc'].forEach(id=>{ const e=document.getElementById(id);if(e)e.value=''; });
    const dH=document.getElementById('inlineTaskDate'), rH=document.getElementById('inlineTaskReminder');
    if(dH) dH.value=''; if(rH) rH.value='';
    const dD=document.getElementById('inlineTaskDateDisplay'), rD=document.getElementById('inlineTaskReminderDisplay');
    if(dD) dD.textContent='Pick date'; if(rD) rD.textContent='Set reminder';
    document.getElementById('inlineTaskDateBtn')?.classList.remove('has-value');
    document.getElementById('inlineTaskReminderBtn')?.classList.remove('has-value');
    const proj=document.getElementById('inlineTaskProject'); if(proj) proj.value='';
    inlineFlagged=false;
    document.getElementById('toggleFlag')?.classList.remove('active');
    document.getElementById('adderExpanded')?.classList.add('hidden');
    document.getElementById('adderPlaceholder')?.classList.remove('hidden');
}

/* ═════════════════════════════════════════════════════════
   FETCH + RENDER
   ═════════════════════════════════════════════════════════ */
async function fetchTasks(projectId = currentProjectId) {
    const c = document.getElementById('tasksContent');
    c.innerHTML='<div class="skeleton" style="height:66px;margin-bottom:10px;"></div><div class="skeleton" style="height:66px;margin-bottom:10px;"></div><div class="skeleton" style="height:66px;"></div>';
    try {
        /* When a group filter is active, fetch all tasks then filter client-side */
        const filter = activeProjectGroup ? 'all' : currentFilter;
        const res = await API.getTasks(filter, activeProjectGroup ? '' : (projectId||''));
        cachedTasks = res.data || [];
        renderTasks();
    } catch (_) {
        c.innerHTML='<div class="empty-state"><h3>Tasks unavailable</h3><p>Could not load tasks right now.</p></div>';
    }
}

function renderTasks() {
    const c       = document.getElementById('tasksContent');
    let tasks     = [...cachedTasks];
    const q       = document.getElementById('taskSearch')?.value.trim().toLowerCase()||'';
    const showDone= document.getElementById('showCompletedToggle')?.checked||false;
    const flagOnly= document.getElementById('showFlaggedOnly')?.checked||false;

    /* Project group filter */
    if (activeProjectGroup) {
        tasks = tasks.filter(t => {
            if (!t.project_id) return false;
            const proj = todoProjects.find(p => Number(p.id) === Number(t.project_id));
            if (!proj) return false;
            return activeProjectGroup === 'personal' ? !Number(proj.is_team) : Number(proj.is_team);
        });
    }

    if (q)         tasks = tasks.filter(t=>`${t.title} ${t.tag||''}`.toLowerCase().includes(q));
    if (!showDone) tasks = tasks.filter(t=>!Number(t.is_completed));
    if (flagOnly)  tasks = tasks.filter(t=> Number(t.is_flagged));

    switch (currentSort) {
        case 'deadline_asc':  tasks.sort((a,b)=>new Date(a.deadline||'9999')-new Date(b.deadline||'9999')); break;
        case 'deadline_desc': tasks.sort((a,b)=>new Date(b.deadline||'0')-new Date(a.deadline||'0'));       break;
        case 'title_asc':     tasks.sort((a,b)=>a.title.localeCompare(b.title)); break;
        case 'title_desc':    tasks.sort((a,b)=>b.title.localeCompare(a.title)); break;
        case 'created_desc':  tasks.sort((a,b)=>new Date(b.created_at||0)-new Date(a.created_at||0)); break;
        case 'created_asc':   tasks.sort((a,b)=>new Date(a.created_at||0)-new Date(b.created_at||0)); break;
        case 'priority':      tasks.sort((a,b)=>Number(b.is_flagged)-Number(a.is_flagged)); break;
    }

    document.getElementById('tasksPanelTitle').textContent =
        activeProjectGroup === 'personal' ? 'My Projects' :
        activeProjectGroup === 'team'     ? 'Team Projects' :
        currentView === 'calendar'        ? 'Calendar View' :
        currentView === 'widget'          ? 'Grid View' : 'Active Tasks';

    /* Calendar always renders regardless of task count (it shows the full month grid) */
    if (currentView==='calendar') { renderCalendarView(tasks, c); return; }

    if (!tasks.length) {
        c.innerHTML='<div class="empty-state"><h3>No tasks found</h3><p>Add a task above or adjust filters.</p></div>';
        return;
    }

    if (currentView==='widget') { renderWidgetView(tasks, c); return; }
    renderListView(tasks, c);
}

/* ─── LIST VIEW ─────────────────────────────────────────── */
function renderListView(tasks, c) {
    /* Show project name in meta when a group filter is active */
    const showProj = !!activeProjectGroup;
    c.innerHTML = `<div class="task-board">
        ${tasks.map(t => `
            <article class="task-item ${Number(t.is_completed)?'is-complete':''} ${Number(t.id)===selectedTaskId?'is-selected':''}"
                     data-task-row="${t.id}">
                <button class="todo-cb-btn ${Number(t.is_completed)?'checked':''}"
                    data-action="toggle" data-task-id="${t.id}" type="button"
                    aria-label="${Number(t.is_completed)?'Mark incomplete':'Mark complete'}"></button>
                <div class="task-item__content" data-action="detail" data-task-id="${t.id}" style="cursor:pointer;flex:1;">
                    <p class="task-item__title">${escapeHTML(t.title)}</p>
                    <p class="task-item__meta">
                        ${t.deadline?`<span>${fmtDate(t.deadline)}</span>`:'<span style="opacity:.4;">No deadline</span>'}
                        ${t.tag?`<span>${escapeHTML(t.tag)}</span>`:''}
                        ${Number(t.is_flagged)?'<span class="status-chip">Priority</span>':''}
                        ${(showProj && t.project_name)?`<span>${escapeHTML(t.project_name)}</span>`:''}
                    </p>
                </div>
                <button class="btn btn-icon" type="button" data-action="delete" data-task-id="${t.id}" aria-label="Delete">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>
                    </svg>
                </button>
            </article>`).join('')}
    </div>`;
    bindTaskActions(c);
}

/* ─── WIDGET VIEW ─────────────────────────────────────────── */
function renderWidgetView(tasks, c) {
    c.innerHTML = `<div class="todo-widget-grid">
        ${tasks.map(t=>`
            <div class="todo-widget-card ${Number(t.id)===selectedTaskId?'is-selected':''}"
                 data-action="detail" data-task-id="${t.id}">
                <p class="section-kicker" style="margin:0 0 6px;">${t.tag?escapeHTML(t.tag):'Task'}</p>
                <h3 class="section-title" style="font-size:.9rem;margin:0 0 8px;line-height:1.3;">${escapeHTML(t.title)}</h3>
                <p class="task-item__meta">
                    ${t.deadline?`<span>${fmtDate(t.deadline)}</span>`:'<span style="opacity:.4;">No deadline</span>'}
                    ${Number(t.is_flagged)?'<span class="status-chip">Priority</span>':''}
                    ${t.project_name?`<span>${escapeHTML(t.project_name)}</span>`:''}
                </p>
            </div>`).join('')}
    </div>`;
    c.querySelectorAll('[data-action="detail"]').forEach(el=>
        el.addEventListener('click', ()=>showTaskDetail(Number(el.dataset.taskId)))
    );
}

/* ─── CALENDAR VIEW ──────────────────────────────────────── */
function renderCalendarView(tasks, c) {
    const p = new Date(calendarPointer); p.setDate(1);
    const ymVal = `${p.getFullYear()}-${String(p.getMonth()+1).padStart(2,'0')}`;
    c.innerHTML = `
        <div class="todo-cal-wrapper">
            <div class="todo-cal-header">
                <div style="position:relative;display:flex;align-items:center;gap:6px;">
                    <h3 class="section-title" style="font-size:.95rem;margin:0;cursor:pointer;user-select:none;"
                        id="todoCalMonthLabel" title="Click to jump to a month">
                        ${p.toLocaleDateString([],{month:'long',year:'numeric'})}
                        <svg style="width:10px;height:10px;opacity:.5;margin-left:2px;vertical-align:middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </h3>
                    <input type="month" id="calJumpPicker"
                        value="${ymVal}"
                        style="position:absolute;left:0;top:0;opacity:0;pointer-events:none;width:1px;height:1px;" tabindex="-1">
                </div>
                <div style="display:flex;gap:4px;">
                    <button class="btn btn-icon" id="calPrev" type="button">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <button class="btn btn-icon" id="calNext" type="button">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                </div>
            </div>
            <!-- Grid: calendar-board class from components.css (7-col grid) -->
            <div class="calendar-board" id="todoCalGrid"></div>
            <div class="todo-cal-agenda" id="todoCalAgenda">
                <p style="font-size:12px;opacity:.4;padding-top:8px;">Click a day to see its items.</p>
            </div>
        </div>`;

    /* Month label → open month picker */
    const jumpPicker = document.getElementById('calJumpPicker');
    const monthLabel = document.getElementById('todoCalMonthLabel');
    if (monthLabel && jumpPicker) {
        monthLabel.addEventListener('click', () => {
            jumpPicker.style.pointerEvents = 'auto';
            jumpPicker.showPicker ? jumpPicker.showPicker() : jumpPicker.click();
            setTimeout(() => { jumpPicker.style.pointerEvents = 'none'; }, 300);
        });
        jumpPicker.addEventListener('change', () => {
            if (jumpPicker.value) {
                const [yr, mo] = jumpPicker.value.split('-').map(Number);
                calendarPointer.setFullYear(yr, mo - 1, 1);
                renderTasks();
            }
        });
    }

    document.getElementById('calPrev')?.addEventListener('click', ()=>{ calendarPointer.setMonth(calendarPointer.getMonth()-1); renderTasks(); });
    document.getElementById('calNext')?.addEventListener('click', ()=>{ calendarPointer.setMonth(calendarPointer.getMonth()+1); renderTasks(); });
    buildCalendarGrid(p);
}

async function buildCalendarGrid(pointer) {
    const grid  = document.getElementById('todoCalGrid');
    if (!grid) return;
    const start = new Date(pointer); start.setDate(1-pointer.getDay());
    const end   = new Date(start); end.setDate(start.getDate()+41);

    try {
        const res = await API.getCalendarEvents(toISO(start), toISO(end));
        cachedCalEvents = res.data || [];
    } catch (_) { cachedCalEvents = []; }

    const markers = cachedCalEvents.reduce((acc,ev)=>{
        const k=String(ev.event_date).slice(0,10);
        if(!acc[k]) acc[k]={total:0};
        acc[k].total++;
        acc[k][ev.source_type]=(acc[k][ev.source_type]||0)+1;
        return acc;
    },{});

    const today = new Date().toISOString().slice(0,10);

    /* Build 7 weekday headers + 42 day cells */
    const headers = ['S','M','T','W','T','F','S'].map(d=>
        `<div class="calendar-label" style="text-align:center;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--color-text-secondary);padding:4px 0;">${d}</div>`
    ).join('');

    const cells = Array.from({length:42},(_,i)=>{
        const d=new Date(start); d.setDate(start.getDate()+i);
        const k=toISO(d), m=markers[k];
        const outside=d.getMonth()!==pointer.getMonth();
        return `<button class="cal-day ${outside?'is-outside':''} ${k===today?'today':''}"
                type="button" data-cal-date="${k}">
            <span class="cal-day__number">${d.getDate()}</span>
            <span class="cal-day__markers">
                ${m?`${m.task?'<span class="calendar-dot" style="background:var(--color-primary);"></span>':''}
                     ${m.project?'<span class="calendar-dot" style="background:var(--color-secondary);"></span>':''}
                     ${m.custom?'<span class="calendar-dot" style="background:var(--color-success-strong);"></span>':''}
                     <span class="calendar-count">${m.total}</span>`:''}
            </span>
        </button>`;
    }).join('');

    grid.innerHTML = headers + cells;

    grid.querySelectorAll('[data-cal-date]').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            grid.querySelectorAll('.cal-day').forEach(c=>c.classList.remove('is-selected'));
            btn.classList.add('is-selected');
            renderCalAgenda(btn.dataset.calDate);
            expandAdder(btn.dataset.calDate);
        });
    });

    const todayBtn = grid.querySelector(`[data-cal-date="${today}"]`);
    if (todayBtn) { todayBtn.classList.add('is-selected'); renderCalAgenda(today); }
}

async function renderCalAgenda(date) {
    const agenda = document.getElementById('todoCalAgenda');
    if (!agenda) return;
    let evts = cachedCalEvents.filter(e=>String(e.event_date).slice(0,10)===date);
    if (!evts.length) {
        try { const r=await API.searchCalendarDate(date); evts=r.data||[]; } catch(_) {}
    }
    /* Also include tasks with this deadline from cachedTasks */
    const dayTasks = cachedTasks.filter(t=>t.deadline&&String(t.deadline).slice(0,10)===date);

    const label = new Date(date+'T00:00:00').toLocaleDateString([],{weekday:'long',month:'long',day:'numeric'});
    const typeMap = {task:'Task',project:'Project',custom:'Event',milestone:'Milestone'};

    const taskItems = dayTasks.map(t=>`
        <article class="task-item ${Number(t.is_completed)?'is-complete':''}">
            <button class="todo-cb-btn ${Number(t.is_completed)?'checked':''}" type="button"
                data-action="toggle" data-task-id="${t.id}"
                aria-label="${Number(t.is_completed)?'Mark incomplete':'Mark complete'}"></button>
            <div class="task-item__content">
                <p class="task-item__title">${escapeHTML(t.title)}</p>
                <p class="task-item__meta">
                    <span>Task</span>
                    ${t.project_name?`<span>${escapeHTML(t.project_name)}</span>`:''}
                    ${Number(t.is_flagged)?'<span class="status-chip">Priority</span>':''}
                </p>
            </div>
        </article>`).join('');

    const calItems = evts.filter(ev=>ev.source_type!=='task').map(ev=>`
        <article class="task-item">
            <div class="task-item__content">
                <p class="task-item__title">${escapeHTML(ev.title)}</p>
                <p class="task-item__meta"><span>${typeMap[ev.source_type]||'Event'}</span></p>
            </div>
        </article>`).join('');

    const combined = taskItems + calItems;
    agenda.innerHTML = `
        <p class="section-kicker" style="margin:0 0 10px;">${label}</p>
        ${combined || '<div class="empty-state" style="min-height:80px;"><h3>Nothing here</h3><p>No items for this day.</p></div>'}`;

    /* Make checkboxes in agenda interactive */
    bindTaskActions(agenda);
}

/* ═════════════════════════════════════════════════════════
   TASK ACTIONS (bind to rendered list)
   ═════════════════════════════════════════════════════════ */
function bindTaskActions(c) {
    /* Toggle complete — button click (not native checkbox change) */
    c.querySelectorAll('[data-action="toggle"]').forEach(btn=>{
        btn.addEventListener('click', async e=>{
            e.stopPropagation();
            const isChecked = btn.classList.toggle('checked');
            const taskId    = Number(btn.dataset.taskId);
            /* Optimistic visual: strike through sibling title */
            const article   = btn.closest('.task-item');
            if (article) article.classList.toggle('is-complete', isChecked);
            await API.completeTask(taskId, isChecked?1:0);
            showToast(isChecked?'Task completed.':'Task reopened.','success');
            await fetchTasks();
            if (selectedTaskId===taskId) showTaskDetail(selectedTaskId);
        });
    });

    /* Open detail panel */
    c.querySelectorAll('[data-action="detail"]').forEach(el=>{
        el.addEventListener('click', ()=>showTaskDetail(Number(el.dataset.taskId)));
    });

    /* Delete */
    c.querySelectorAll('[data-action="delete"]').forEach(btn=>{
        btn.addEventListener('click', async e=>{
            e.stopPropagation();
            const task=cachedTasks.find(t=>Number(t.id)===Number(btn.dataset.taskId));
            const ok=await showConfirmDialog({title:'Delete task',message:`Delete "${task?.title||'this task'}"?`,confirmText:'Delete',isDanger:true});
            if(!ok) return;
            await API.deleteTask(Number(btn.dataset.taskId));
            showToast('Task deleted.','success');
            if(selectedTaskId===Number(btn.dataset.taskId)){
                document.getElementById('todoDetailCol')?.classList.remove('visible');
                selectedTaskId=null;
            }
            await fetchTasks();
        });
    });
}

/* ═════════════════════════════════════════════════════════
   DETAIL PANEL
   ═════════════════════════════════════════════════════════ */
function showTaskDetail(taskId) {
    const task  = cachedTasks.find(t=>Number(t.id)===Number(taskId));
    const panel = document.getElementById('taskDetailPanel');
    const col   = document.getElementById('todoDetailCol');
    if (!panel||!task) return;

    selectedTaskId=Number(taskId);
    col.classList.add('visible');
    document.querySelectorAll('[data-task-row]').forEach(r=>r.classList.remove('is-selected'));
    document.querySelector(`[data-task-row="${taskId}"]`)?.classList.add('is-selected');

    const created  = task.created_at?new Date(task.created_at).toLocaleDateString([],{month:'long',day:'numeric',year:'numeric'}):'—';
    const deadline = task.deadline   ?new Date(task.deadline).toLocaleDateString([],{weekday:'short',month:'long',day:'numeric',year:'numeric'}):'—';
    const reminder = task.reminder_time?new Date(task.reminder_time).toLocaleString([],{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}):'—';
    const projHTML = task.project_name
        ?`${escapeHTML(task.project_name)}${+task.is_team?'<span class="status-chip" style="margin-left:4px;">Team</span>':''}`
        :'<span style="opacity:.4;font-style:italic;">No project</span>';

    panel.innerHTML=`
        <div style="display:flex;flex-direction:column;gap:14px;flex:1;">
            <div><p class="tdf-label">Task</p>
                 <p class="tdf-val" style="font-size:14px;font-weight:700;line-height:1.35;">${escapeHTML(task.title)}</p></div>
            ${task.description?`<div><p class="tdf-label">Notes</p><p class="tdf-val" style="font-size:12px;">${escapeHTML(task.description)}</p></div>`:''}
            <div><p class="tdf-label">Status</p>
                 <p class="tdf-val" style="display:flex;gap:5px;flex-wrap:wrap;">
                    ${Number(task.is_completed)?'<span class="status-chip" style="background:rgba(34,197,94,.12);color:#22c55e;">Completed</span>':'<span class="status-chip">Open</span>'}
                    ${Number(task.is_flagged)?'<span class="status-chip">Priority</span>':''}
                 </p></div>
            <div><p class="tdf-label">Project</p><p class="tdf-val">${projHTML}</p></div>
            <div><p class="tdf-label">Deadline</p><p class="tdf-val ${!task.deadline?'empty':''}">${deadline}</p></div>
            <div><p class="tdf-label">Reminder</p><p class="tdf-val ${!task.reminder_time?'empty':''}">${reminder}</p></div>
            <div><p class="tdf-label">Tag</p><p class="tdf-val ${!task.tag?'empty':''}">${task.tag?escapeHTML(task.tag):'No tag'}</p></div>
            <div><p class="tdf-label">Created</p><p class="tdf-val">${created}</p></div>
        </div>
        <div style="display:flex;flex-direction:column;gap:6px;padding-top:14px;border-top:1px solid var(--glass-border);">
            <button class="btn btn-ghost" id="dtlToggleBtn" type="button" style="font-size:11px;">
                ${Number(task.is_completed)?'Reopen Task':'Mark Complete'}
            </button>
            <button class="btn btn-ghost" id="dtlDeleteBtn" type="button" style="font-size:11px;color:#ef4444;">Delete Task</button>
        </div>`;

    document.getElementById('dtlToggleBtn')?.addEventListener('click', async()=>{
        await API.completeTask(Number(task.id),Number(task.is_completed)?0:1);
        showToast(Number(task.is_completed)?'Task reopened.':'Task completed.','success');
        await fetchTasks(); showTaskDetail(taskId);
    });
    document.getElementById('dtlDeleteBtn')?.addEventListener('click', async()=>{
        const ok=await showConfirmDialog({title:'Delete task',message:`Delete "${task.title}"?`,confirmText:'Delete',isDanger:true});
        if(!ok) return;
        await API.deleteTask(Number(task.id));
        showToast('Task deleted.','success');
        col.classList.remove('visible'); selectedTaskId=null;
        await fetchTasks();
    });
}

/* ═════════════════════════════════════════════════════════
   SEARCH
   ═════════════════════════════════════════════════════════ */
function handleSearch() {
    const inp=document.getElementById('taskSearch');
    const sugg=document.getElementById('searchSuggestions');
    const val=inp.value.trim().toLowerCase();
    if(val.length<2){sugg.classList.add('hidden');renderTasks();return;}
    const m=cachedTasks.filter(t=>t.title.toLowerCase().includes(val)).slice(0,6);
    if(!m.length){sugg.classList.add('hidden');renderTasks();return;}
    sugg.classList.remove('hidden');
    sugg.innerHTML=m.map(t=>`<div class="suggestion-item" data-s="${escapeHTML(t.title)}">${escapeHTML(t.title)}</div>`).join('');
    sugg.querySelectorAll('[data-s]').forEach(item=>item.addEventListener('click',()=>{
        inp.value=item.dataset.s; sugg.classList.add('hidden'); renderTasks();
    }));
    renderTasks();
}

const btnCalJump = document.getElementById('btnCalendarJump');
if (btnCalJump) {
    const hiddenDate = Object.assign(document.createElement('input'), { type: 'hidden' });
    document.body.appendChild(hiddenDate);
    new window.ChronosDatePicker({
        trigger: btnCalJump,
        hidden: hiddenDate,
        onSelect: (val) => {
            if (val) {
                calendarPointer = new Date(val + 'T00:00:00');
                if (currentView === 'calendar') renderTasks();
            }
        }
    });
}

/* ─── Helpers ─────────────────────────────────────────────── */
function fmtDate(v) { return new Date(v).toLocaleDateString([],{month:'short',day:'numeric',year:'numeric'}); }
function toISO(d)   { return d.toISOString().slice(0,10); }
