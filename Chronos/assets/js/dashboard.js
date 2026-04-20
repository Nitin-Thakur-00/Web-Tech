let dashboardMonth = new Date().getMonth();
let dashboardYear = new Date().getFullYear();
let dashboardProjects = [];
let timerFrame = null;
let dashboardCalendarEvents = [];

/* Idle tracking for mini timer eyes */
let dashLastMouseMove = Date.now();

const dashboardTimer = {
    totalSeconds: 25 * 60,
    remainingSeconds: 25 * 60,
    running: false,
    startedAt: null,
    carriedSeconds: 0,
};

document.addEventListener('DOMContentLoaded', async () => {
    initClock();
    bindDashboardActions();
    await Promise.all([
        loadWelcomeState(),
        populateTaskProjects(),
        renderDashboardCalendar(),
        loadQuickTasks(),
        loadDashboardReminders(),
        loadTeamActivity(),
        loadDashboardStats(),
        loadFriendsBubbles(),
    ]);
    const saved = window.TimerSync?.getState();
    if (saved && typeof saved.remainingSeconds === 'number') {
        dashboardTimer.totalSeconds = saved.totalSeconds;
        dashboardTimer.remainingSeconds = saved.remainingSeconds;
        dashboardTimer.running = saved.running;
        dashboardTimer.startedAt = saved.startedAt;
        dashboardTimer.carriedSeconds = saved.carriedSeconds;
        if (saved.running) {
            dashboardTimer.running = false; // So startMiniTimer proceeds
            startMiniTimer(true);
        }
    }

    renderMiniTimer();
});

window.addEventListener('storage', (e) => {
    if (e.key === 'crimson_global_timer' && e.newValue) {
        const saved = JSON.parse(e.newValue);
        if (saved.source === 'dashboard') return;
        dashboardTimer.totalSeconds = saved.totalSeconds;
        dashboardTimer.remainingSeconds = saved.remainingSeconds;
        dashboardTimer.running = saved.running;
        dashboardTimer.startedAt = saved.startedAt;
        dashboardTimer.carriedSeconds = saved.carriedSeconds;
        if (saved.running) {
            if (!timerFrame) { dashboardTimer.running = false; startMiniTimer(true); }
        } else {
            cancelAnimationFrame(timerFrame);
            timerFrame = null;
            document.getElementById('miniTimerPause')?.classList.toggle('is-paused', dashboardTimer.remainingSeconds > 0 && dashboardTimer.remainingSeconds < dashboardTimer.totalSeconds);
        }
        renderMiniTimer();
    }
});

function bindDashboardActions() {
    const flagBtn = document.getElementById('quickTaskFlag');
    if (flagBtn) {
        flagBtn.addEventListener('click', () => {
            flagBtn.classList.toggle('active');
            if (flagBtn.classList.contains('active')) {
                flagBtn.style.color = '#e11d48';
                flagBtn.style.background = 'rgba(225,29,72,0.1)';
                flagBtn.querySelector('svg').setAttribute('fill', '#e11d48');
            } else {
                flagBtn.style.color = '';
                flagBtn.style.background = '';
                flagBtn.querySelector('svg').setAttribute('fill', 'none');
            }
        });
    }

    // Calendar date picker toggle on quick-add
    const dateToggleBtn = document.getElementById('quickTaskDateToggle');
    const dateInput = document.getElementById('quickTaskDate');
    const dateBadge = document.getElementById('quickTaskDateBadge');
    const dateLabel = document.getElementById('quickTaskDateLabel');
    if (dateToggleBtn && dateInput) {
        new window.ChronosDatePicker({
            trigger: dateToggleBtn,
            hidden: dateInput,
            onSelect: (val) => {
                if (val) {
                    const d = new Date(val + 'T00:00:00');
                    const formatted = d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
                    if (dateLabel) dateLabel.textContent = formatted;
                    if (dateBadge) { dateBadge.classList.remove('hidden'); dateBadge.style.display = 'flex'; }
                } else {
                    if (dateBadge) { dateBadge.classList.add('hidden'); dateBadge.style.display = ''; }
                }
            }
        });
    }

    document.getElementById('calPrev')?.addEventListener('click', async () => {
        dashboardMonth -= 1;
        if (dashboardMonth < 0) {
            dashboardMonth = 11;
            dashboardYear -= 1;
        }
        await renderDashboardCalendar();
    });

    document.getElementById('calNext')?.addEventListener('click', async () => {
        dashboardMonth += 1;
        if (dashboardMonth > 11) {
            dashboardMonth = 0;
            dashboardYear += 1;
        }
        await renderDashboardCalendar();
    });

    document.getElementById('quickTaskFilter')?.addEventListener('change', loadQuickTasks);

    document.getElementById('btnQuickTaskSave')?.addEventListener('click', createQuickTask);
    document.getElementById('quickTaskSubmit')?.addEventListener('click', createQuickTask);
    document.getElementById('quickTaskInput')?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            createQuickTask();
        }
    });

    document.getElementById('btnSubmitModalTask')?.addEventListener('click', createModalTask);

    document.getElementById('miniTimerStart')?.addEventListener('click', startMiniTimer);
    document.getElementById('miniTimerPause')?.addEventListener('click', pauseMiniTimer);
    document.getElementById('miniTimerStop')?.addEventListener('click', stopMiniTimer);

    // Day tasks panel: overlay click closes panel
    document.getElementById('dayTasksPanelOverlay')?.addEventListener('click', closeDayTasksPanel);
}

function initClock() {
    const updateClock = () => {
        const now = new Date();
        const time = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
        const weekday = now.toLocaleDateString([], { weekday: 'long' });
        const date = now.toLocaleDateString([], { month: 'long', day: 'numeric', year: 'numeric' });

        document.getElementById('clockTime').textContent = time;
        document.getElementById('clockDay').textContent = weekday;
        document.getElementById('clockDate').textContent = date;
    };

    updateClock();
    setInterval(updateClock, 1000);
}

async function loadWelcomeState() {
    const cachedUser = store.user || await store.loadUser().catch(() => null);
    const username = cachedUser?.full_name || cachedUser?.username || 'there';
    document.getElementById('welcomeUsername').textContent = username;

    const hour = new Date().getHours();
    let greeting = 'A good time to clear the next blockers.';
    if (hour < 12) {
        greeting = 'Your morning runway is ready.';
    } else if (hour < 18) {
        greeting = 'Your afternoon priorities are laid out below.';
    }
    document.getElementById('dashboardContext').textContent = greeting;
}

async function populateTaskProjects() {
    const select = document.getElementById('modalTaskProject');
    if (!select) {
        return;
    }

    try {
        const response = await API.getProjects(true);
        dashboardProjects = response.data || [];
        const options = ['<option value="">No project</option>'].concat(
            dashboardProjects.map((project) => `<option value="${project.id}">${escapeHTML(project.name)}</option>`)
        );
        select.innerHTML = options.join('');
    } catch (error) {
        select.innerHTML = '<option value="">No project</option>';
    }
}

async function loadQuickTasks() {
    const list = document.getElementById('miniTaskList');
    if (!list) {
        return;
    }

    list.innerHTML = '<div class="skeleton" style="height:72px;"></div><div class="skeleton" style="height:72px;"></div>';

    try {
        const filter = document.getElementById('quickTaskFilter')?.value || 'today';
        const response = await API.getTasks(filter);
        const tasks = (response.data || []).filter((task) => !Number(task.is_completed)).slice(0, 6);

        if (!tasks.length) {
            list.innerHTML = `
                <div class="empty-state" style="min-height:180px;">
                    <h3>No tasks yet</h3>
                    <p>Add one below or click a day in the calendar to prefill the deadline.</p>
                </div>
            `;
            return;
        }

        list.innerHTML = tasks.map((task) => `
            <label class="task-item" for="dashboard-task-${task.id}">
                <input class="sanctuary-checkbox" id="dashboard-task-${task.id}" type="checkbox" ${Number(task.is_completed) ? 'checked' : ''} data-task-id="${task.id}">
                <div class="task-item__content">
                    <p class="task-item__title">${escapeHTML(task.title)}</p>
                    <p class="task-item__meta">
                        ${task.deadline ? `<span>${formatShortDate(task.deadline)}</span>` : '<span>No deadline</span>'}
                        ${task.tag ? `<span>${escapeHTML(task.tag)}</span>` : ''}
                        ${Number(task.is_flagged) ? '<span class="status-chip">Flagged</span>' : ''}
                    </p>
                </div>
            </label>
        `).join('');

        list.querySelectorAll('[data-task-id]').forEach((checkbox) => {
            checkbox.addEventListener('change', async (event) => {
                const taskId = Number(event.currentTarget.dataset.taskId);
                await API.completeTask(taskId, event.currentTarget.checked ? 1 : 0);
                await Promise.all([loadQuickTasks(), loadDashboardReminders(), renderDashboardCalendar(), loadDashboardStats()]);
                showToast(event.currentTarget.checked ? 'Task completed.' : 'Task reopened.', 'success');
            });
        });
    } catch (error) {
        list.innerHTML = '<div class="empty-state" style="min-height:180px;"><h3>Task feed unavailable</h3><p>Please refresh the page.</p></div>';
    }
}

async function createQuickTask() {
    const titleInput = document.getElementById('quickTaskInput');
    const deadlineInput = document.getElementById('quickTaskDate');
    const isFlagged = document.getElementById('quickTaskFlag')?.classList.contains('active') ? 1 : 0;
    const title = titleInput?.value.trim();

    if (!title) {
        showToast('Add a task title first.', 'warning');
        return;
    }

    try {
        await API.createTask({
            title,
            deadline: deadlineInput?.value || null,
            is_flagged: isFlagged,
        });

        titleInput.value = '';
        if (deadlineInput) deadlineInput.value = '';
        clearQuickTaskDate();
        const flagBtn = document.getElementById('quickTaskFlag');
        if (flagBtn) {
            flagBtn.classList.remove('active');
            flagBtn.style.color = '';
            flagBtn.style.background = '';
            flagBtn.querySelector('svg')?.setAttribute('fill', 'none');
        }
        showToast('Task created.', 'success');
        document.dispatchEvent(new CustomEvent('tasksUpdated'));
        await Promise.all([loadQuickTasks(), loadDashboardReminders(), renderDashboardCalendar(), loadDashboardStats()]);
    } catch (error) {
        showToast(error.message || 'Failed to create task.', 'danger');
    }
}

function clearQuickTaskDate() {
    const dateInput = document.getElementById('quickTaskDate');
    const dateBadge = document.getElementById('quickTaskDateBadge');
    if (dateInput) dateInput.value = '';
    if (dateBadge) { dateBadge.classList.add('hidden'); dateBadge.style.display = ''; }
}

async function createModalTask() {
    const payload = {
        title: document.getElementById('modalTaskTitle')?.value.trim(),
        deadline: document.getElementById('modalTaskDate')?.value || null,
        reminder_time: document.getElementById('modalTaskReminder')?.value || null,
        project_id: document.getElementById('modalTaskProject')?.value || null,
        tag: document.getElementById('modalTaskTag')?.value.trim() || null,
        is_flagged: document.getElementById('modalTaskFlag')?.checked ? 1 : 0,
    };

    if (!payload.title) {
        showToast('Task title is required.', 'warning');
        return;
    }

    try {
        await API.createTask(payload);
        closeModal('taskModal');
        resetTaskModal();
        showToast('Task added to your schedule.', 'success');
        await Promise.all([loadQuickTasks(), loadDashboardReminders(), renderDashboardCalendar(), loadDashboardStats()]);
    } catch (error) {
        showToast(error.message || 'Failed to add task.', 'danger');
    }
}

function resetTaskModal() {
    ['modalTaskTitle', 'modalTaskDate', 'modalTaskReminder', 'modalTaskTag'].forEach((id) => {
        const element = document.getElementById(id);
        if (element) {
            element.value = '';
        }
    });
    document.getElementById('modalTaskProject').value = '';
    document.getElementById('modalTaskFlag').checked = false;
}

async function renderDashboardCalendar() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('calMonthYear').textContent = `${monthNames[dashboardMonth]} ${dashboardYear}`;

    const grid = document.getElementById('calGrid');
    if (!grid) {
        return;
    }

    grid.innerHTML = '';

    const firstOfMonth = new Date(dashboardYear, dashboardMonth, 1);
    const startDay = new Date(firstOfMonth);
    startDay.setDate(1 - firstOfMonth.getDay());

    const rangeStart = new Date(startDay);
    const rangeEnd = new Date(startDay);
    rangeEnd.setDate(startDay.getDate() + 41);

    try {
        const response = await API.getCalendarEvents(toISODate(rangeStart), toISODate(rangeEnd));
        dashboardCalendarEvents = response.data || [];
    } catch (error) {
        dashboardCalendarEvents = [];
    }

    const markers = dashboardCalendarEvents.reduce((lookup, event) => {
        const dateKey = String(event.event_date).slice(0, 10);
        if (!lookup[dateKey]) {
            lookup[dateKey] = { total: 0, task: 0, project: 0, custom: 0 };
        }
        lookup[dateKey].total += 1;
        lookup[dateKey][event.source_type] = (lookup[dateKey][event.source_type] || 0) + 1;
        return lookup;
    }, {});

    for (let index = 0; index < 42; index += 1) {
        const cellDate = new Date(startDay);
        cellDate.setDate(startDay.getDate() + index);
        const key = cellDate.toISOString().slice(0, 10);
        const isCurrentMonth = cellDate.getMonth() === dashboardMonth;
        const isToday = new Date().toISOString().slice(0, 10) === key;
        const counts = markers[key] || null;

        const cell = document.createElement('button');
        cell.type = 'button';
        cell.className = `cal-day ${isCurrentMonth ? '' : 'is-outside'} ${isToday ? 'today' : ''}`;
        cell.innerHTML = `
            <span class="cal-day__number">${cellDate.getDate()}</span>
            <span class="cal-day__markers">
                ${counts ? `
                    ${counts.task ? '<span class="calendar-dot" style="background:var(--color-primary);"></span>' : ''}
                    ${counts.project ? '<span class="calendar-dot" style="background:var(--color-secondary);"></span>' : ''}
                    ${counts.custom ? '<span class="calendar-dot" style="background:var(--color-success-strong);"></span>' : ''}
                    <span class="calendar-count">${counts.total}</span>
                ` : ''}
            </span>
        `;
        cell.addEventListener('click', () => {
            openDayTasksPanel(key);
        });
        if (counts) {
            const summary = [];
            if (counts.task) summary.push(`${counts.task} task${counts.task > 1 ? 's' : ''}`);
            if (counts.project) summary.push(`${counts.project} project deadline${counts.project > 1 ? 's' : ''}`);
            if (counts.custom) summary.push(`${counts.custom} custom event${counts.custom > 1 ? 's' : ''}`);
            cell.title = `${key}: ${summary.join(', ')}`;
        }
        grid.appendChild(cell);
    }
}

async function loadDashboardReminders() {
    const list = document.getElementById('remindersList');
    if (!list) {
        return;
    }

    list.innerHTML = '<div class="skeleton" style="height:68px;"></div>';

    try {
        const [taskResponse, projectResponse] = await Promise.all([
            API.getTasks('all'),
            API.getProjects(true)
        ]);

        const now = new Date();
        const upcomingTasks = (taskResponse.data || [])
            .filter((task) => !Number(task.is_completed) && task.deadline)
            .map((task) => ({
                type: new Date(task.deadline) < now ? 'overdue' : 'deadline',
                title: task.title,
                date: task.deadline,
                message: new Date(task.deadline) < now ? 'Task overdue' : 'Task due soon',
            }));

        const stalledProjects = (projectResponse.data || [])
            .filter((project) => project.deadline)
            .slice(0, 3)
            .map((project) => ({
                type: 'project',
                title: project.name,
                date: project.deadline,
                message: 'Review project progress',
            }));

        const items = upcomingTasks.concat(stalledProjects)
            .sort((left, right) => new Date(left.date) - new Date(right.date))
            .slice(0, 6);

        if (!items.length) {
            list.innerHTML = '<div class="empty-state" style="min-height:180px;"><h3>No urgent deadlines</h3><p>Your upcoming schedule looks clear.</p></div>';
            return;
        }

        list.innerHTML = items.map((item) => `
            <div class="task-item">
                <div class="status-chip ${item.type === 'overdue' ? 'status-chip--danger' : item.type === 'project' ? '' : 'status-chip--warning'}">${item.message}</div>
                <div class="task-item__content">
                    <p class="task-item__title">${escapeHTML(item.title)}</p>
                    <p class="task-item__meta"><span>${formatShortDate(item.date)}</span></p>
                </div>
            </div>
        `).join('');
    } catch (error) {
        list.innerHTML = '<div class="empty-state" style="min-height:180px;"><h3>Reminders unavailable</h3><p>Try refreshing the page.</p></div>';
    }
}

async function loadFriendsBubbles() {
    const container = document.getElementById('activeProjectFriends');
    if (!container) return;

    try {
        const response = await API.getFriends();
        const friends = (response.data || []).filter(f => f.direction === 'connected');

        if (!friends.length) {
            container.innerHTML = `<div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 border-2 border-white dark:border-white/10
                flex items-center justify-center text-[9px] font-bold text-slate-400 dark:text-slate-500">0</div>`;
            return;
        }

        const shown = friends.slice(0, 3);
        const extra = friends.length - shown.length;
        const colors = ['bg-rose-50 dark:bg-rose-950 text-rose-600 dark:text-rose-400', 'bg-violet-50 dark:bg-violet-950 text-violet-600 dark:text-violet-400', 'bg-sky-50 dark:bg-sky-950 text-sky-600 dark:text-sky-400'];

        let html = shown.map((f, i) => {
            const initials = (f.full_name || f.username || '?').split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase();
            return `<div class="w-8 h-8 rounded-full ${colors[i]} border-2 border-white dark:border-white/10 flex items-center justify-center text-[9px] font-bold" title="${escapeHTML(f.full_name || f.username)}">${initials}</div>`;
        }).join('');

        if (extra > 0) {
            html += `<div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-black border-2 border-white dark:border-white/10 flex items-center justify-center text-[9px] font-bold text-slate-400 dark:text-slate-500">+${extra}</div>`;
        }

        container.innerHTML = html;
    } catch (error) {
        // Non-blocking — leave placeholder
    }
}

async function openDayTasksPanel(dateKey) {
    const panel = document.getElementById('dayTasksPanel');
    const overlay = document.getElementById('dayTasksPanelOverlay');
    const body = document.getElementById('dayTasksPanelBody');
    const title = document.getElementById('dayTasksPanelTitle');
    const addBtn = document.getElementById('dayTasksAddBtn');
    const newInput = document.getElementById('dayTasksNewTitle');

    if (!panel || !body) return;

    // Format date nicely
    const d = new Date(dateKey + 'T00:00:00');
    const formatted = d.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    if (title) title.textContent = formatted;

    // Open panel
    panel.classList.add('open');
    if (overlay) overlay.classList.add('open');

    // Load tasks for that day
    body.innerHTML = '<div class="skeleton" style="height:60px;border-radius:8px;"></div>';

    try {
        const response = await API.getTasks('all');
        const allTasks = response.data || [];
        const dayTasks = allTasks.filter(t => t.deadline && String(t.deadline).slice(0, 10) === dateKey);

        if (!dayTasks.length) {
            body.innerHTML = '<div style="text-align:center;padding:32px 0;opacity:0.5;"><p style="font-size:0.85rem;font-weight:600;margin:0 0 6px;">No tasks for this day</p><p style="font-size:0.78rem;margin:0;">Use the input below to add one.</p></div>';
        } else {
            body.innerHTML = dayTasks.map(t => `
                <div class="dtp-task-item">
                    <input type="checkbox" ${Number(t.is_completed) ? 'checked' : ''}
                        data-dtp-task="${t.id}"
                        style="margin-top:3px;accent-color:#e11d48;width:15px;height:15px;flex-shrink:0;cursor:pointer;">
                    <div style="flex:1;min-width:0;">
                        <p class="dtp-task-name" style="${Number(t.is_completed) ? 'text-decoration:line-through;opacity:0.45;' : ''}">${escapeHTML(t.title)}</p>
                        ${t.tag ? `<p class="dtp-task-meta">${escapeHTML(t.tag)}</p>` : ''}
                    </div>
                </div>
            `).join('');

            // Wire checkboxes
            body.querySelectorAll('[data-dtp-task]').forEach(cb => {
                cb.addEventListener('change', async () => {
                    await API.completeTask(Number(cb.dataset.dtpTask), cb.checked ? 1 : 0);
                    await Promise.all([loadQuickTasks(), loadDashboardStats(), renderDashboardCalendar()]);
                    openDayTasksPanel(dateKey); // Refresh panel
                });
            });
        }
    } catch (e) {
        body.innerHTML = '<p style="font-size:0.82rem;color:#94a3b8;padding:16px 0;">Could not load tasks.</p>';
    }

    // Wire add button
    if (addBtn) {
        const newAddBtn = addBtn.cloneNode(true); // remove old listeners
        addBtn.parentNode.replaceChild(newAddBtn, addBtn);
        newAddBtn.addEventListener('click', async () => {
            const val = newInput?.value.trim();
            if (!val) return;
            await API.createTask({ title: val, deadline: dateKey });
            if (newInput) newInput.value = '';
            showToast('Task added.', 'success');
            await Promise.all([loadQuickTasks(), loadDashboardReminders(), renderDashboardCalendar(), loadDashboardStats()]);
            openDayTasksPanel(dateKey);
        });
        newInput?.addEventListener('keydown', async (e) => {
            if (e.key === 'Enter') newAddBtn.click();
        });
    }
}

function closeDayTasksPanel() {
    document.getElementById('dayTasksPanel')?.classList.remove('open');
    document.getElementById('dayTasksPanelOverlay')?.classList.remove('open');
}

async function loadTeamActivity() {
    const list = document.getElementById('teamActivityList');
    if (!list) {
        return;
    }

    list.innerHTML = '<div class="skeleton" style="height:68px;"></div><div class="skeleton" style="height:68px;"></div>';

    try {
        const projectResponse = await API.getProjects(true);
        const teamProjects = (projectResponse.data || []).filter((project) => Number(project.is_team) === 1);

        if (!teamProjects.length) {
            list.innerHTML = '<div class="empty-state" style="min-height:180px;"><h3>No recent activity</h3><p>Join or create a team project to start sharing updates.</p></div>';
            return;
        }

        const messageBuckets = await Promise.all(teamProjects.map(async (project) => {
            try {
                const chat = await API.getChat(project.id, 3);
                return (chat.data || []).map((message) => ({ ...message, projectName: project.name }));
            } catch (error) {
                return [];
            }
        }));

        const messages = messageBuckets.flat().sort((left, right) => new Date(right.created_at) - new Date(left.created_at)).slice(0, 3);

        if (!messages.length) {
            list.innerHTML = '<div class="empty-state" style="min-height:180px;"><h3>No recent activity</h3><p>Messages from team projects will appear here.</p></div>';
            return;
        }

        list.innerHTML = messages.map((message) => `
            <div class="task-item">
                <img class="friend-avatar" src="${escapeHTML(message.sender_pic || 'assets/images/default-avatar.png')}" alt="">
                <div class="task-item__content">
                    <p class="task-item__title">${escapeHTML(message.sender_name || 'Teammate')}</p>
                    <p class="task-item__meta">
                        <span>${escapeHTML(message.projectName)}</span>
                        <span>${new Date(message.created_at).toLocaleString()}</span>
                    </p>
                    <p style="margin:0.45rem 0 0;color:var(--color-text-secondary);">${escapeHTML(message.message)}</p>
                </div>
            </div>
        `).join('');
    } catch (error) {
        list.innerHTML = '<div class="empty-state" style="min-height:180px;"><h3>Activity unavailable</h3><p>Unable to load project messages right now.</p></div>';
    }
}

async function loadDashboardStats() {
    try {
        const [taskResponse, projectResponse, sessionResponse] = await Promise.all([
            API.getTasks('all'),
            API.getProjects(true),
            API.getSessions(30)
        ]);

        const tasks = taskResponse.data || [];
        const openTasks = tasks.filter((task) => !Number(task.is_completed));
        const projects = projectResponse.data || [];
        const sessions = sessionResponse.data || [];
        const focusMinutes = sessions
            .filter((session) => {
                const sessionDate = new Date(session.session_date || session.created_at);
                const oneWeekAgo = new Date();
                oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
                return sessionDate >= oneWeekAgo;
            })
            .reduce((total, session) => total + Number(session.duration_minutes || 0), 0);

        document.getElementById('statOpenTasks').textContent = String(openTasks.length);
        document.getElementById('statOpenTasksMeta').textContent = openTasks.length ? `${openTasks.filter((task) => task.deadline).length} with deadlines scheduled.` : 'Nothing pending right now.';
        document.getElementById('statFocusMinutes').textContent = String(focusMinutes);
        document.getElementById('statFocusMeta').textContent = focusMinutes ? `${sessions.length} recent focus sessions logged.` : 'Your latest sessions will appear here.';
        document.getElementById('statProjects').textContent = String(projects.length);
        document.getElementById('statProjectsMeta').textContent = projects.length ? `${projects.filter((project) => Number(project.is_team) === 1).length} team workspaces active.` : 'No collaborative workspaces yet.';
    } catch (error) {
        // Stats are non-blocking.
    }
}

function saveDashboardTimer() {
    if (window.TimerSync) {
        window.TimerSync.setState({
            source: 'dashboard',
            totalSeconds: dashboardTimer.totalSeconds,
            remainingSeconds: dashboardTimer.remainingSeconds,
            running: dashboardTimer.running,
            startedAt: dashboardTimer.startedAt,
            carriedSeconds: dashboardTimer.carriedSeconds
        });
    }
}

function startMiniTimer(skipSave = false) {
    if (dashboardTimer.running) {
        return;
    }

    dashboardTimer.running = true;
    if (skipSave !== true) {
        dashboardTimer.startedAt = Date.now();
        saveDashboardTimer();
    }
    updateMiniTimer();
}

function pauseMiniTimer() {
    const btn = document.getElementById('miniTimerPause');

    if (dashboardTimer.running) {
        /* PAUSE */
        dashboardTimer.running = false;
        dashboardTimer.carriedSeconds += Math.floor((Date.now() - dashboardTimer.startedAt) / 1000);
        cancelAnimationFrame(timerFrame);
        timerFrame = null;
        btn?.classList.add('is-paused');
        saveDashboardTimer();
    } else if (dashboardTimer.carriedSeconds > 0 || dashboardTimer.remainingSeconds < dashboardTimer.totalSeconds) {
        /* RESUME */
        btn?.classList.remove('is-paused');
        dashboardTimer.startedAt = Date.now();
        startMiniTimer(true);
        saveDashboardTimer();
    }
}

function stopMiniTimer() {
    dashboardTimer.running = false;
    dashboardTimer.carriedSeconds = 0;
    dashboardTimer.remainingSeconds = dashboardTimer.totalSeconds;
    cancelAnimationFrame(timerFrame);
    timerFrame = null;
    document.getElementById('miniTimerPause')?.classList.remove('is-paused');
    saveDashboardTimer();
    renderMiniTimer();
}

function updateMiniTimer() {
    if (!dashboardTimer.running) {
        return;
    }

    const elapsedSeconds = dashboardTimer.carriedSeconds + Math.floor((Date.now() - dashboardTimer.startedAt) / 1000);
    dashboardTimer.remainingSeconds = Math.max(0, dashboardTimer.totalSeconds - elapsedSeconds);
    renderMiniTimer();

    if (dashboardTimer.remainingSeconds === 0) {
        dashboardTimer.running = false;
        dashboardTimer.carriedSeconds = 0;
        cancelAnimationFrame(timerFrame);
        timerFrame = null;
        saveDashboardTimer();
        API.logSession({ duration_minutes: dashboardTimer.totalSeconds / 60, session_type: 'pomodoro' }).catch(() => {});
        showToast('Focus session complete.', 'success');
        return;
    }

    timerFrame = requestAnimationFrame(updateMiniTimer);
}

function renderMiniTimer() {
    const display = document.getElementById('miniTimerDisplay');
    const canvas = document.getElementById('miniTimerCanvas');
    const context = canvas?.getContext('2d');
    if (!display || !context) {
        return;
    }

    const minutes = Math.floor(dashboardTimer.remainingSeconds / 60).toString().padStart(2, '0');
    const seconds = String(dashboardTimer.remainingSeconds % 60).padStart(2, '0');
    display.textContent = `${minutes}:${seconds}`;

    /* Update SVG arc dynamically */
    const arc = document.getElementById('miniTimerArc');
    if (arc) {
        const progress = dashboardTimer.totalSeconds > 0
            ? dashboardTimer.remainingSeconds / dashboardTimer.totalSeconds : 0;
        arc.style.strokeDashoffset = String(527.8 * (1 - progress));
    }

    context.clearRect(0, 0, canvas.width, canvas.height);
    const center = canvas.width / 2;
    const radius = 126;
    const progress = 1 - (dashboardTimer.remainingSeconds / dashboardTimer.totalSeconds);
    const startAngle = -Math.PI / 2;
    const endAngle = startAngle + progress * Math.PI * 2;

    context.beginPath();
    context.arc(center, center, radius, 0, Math.PI * 2);
    context.strokeStyle = 'rgba(255,255,255,0.12)';
    context.lineWidth = 12;
    context.stroke();

    context.beginPath();
    context.arc(center, center, radius, startAngle, endAngle);
    context.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
    context.lineWidth = 12;
    context.lineCap = 'round';
    context.stroke();

    /* When running + idle ≥ 5s → eyes follow ring tip (seconds hand) */
    if (dashboardTimer.running && (Date.now() - dashLastMouseMove) >= 5000) {
        const svg = document.getElementById('miniTimerRingSvg');
        if (svg) {
            const r = svg.getBoundingClientRect();
            const cx = r.left + r.width / 2;
            const cy = r.top  + r.height / 2;
            const radius = (r.width / 192) * 84;
            const progress = dashboardTimer.totalSeconds > 0
                ? dashboardTimer.remainingSeconds / dashboardTimer.totalSeconds : 0;
            const angle = -Math.PI / 2 + progress * 2 * Math.PI;
            updateMiniTimerEyes(cx + radius * Math.cos(angle), cy + radius * Math.sin(angle));
        }
    }
}

/* Update mini eyes to look toward (targetX, targetY) in viewport coords */
function updateMiniTimerEyes(targetX, targetY) {
    document.querySelectorAll('.mini-timer-eye').forEach(eye => {
        const rect = eye.getBoundingClientRect();
        const eyeX = rect.left + rect.width / 2;
        const eyeY = rect.top  + rect.height / 2;
        const dx = targetX - eyeX;
        const dy = targetY - eyeY;
        const angle = Math.atan2(dy, dx);
        const maxDist = (rect.width / 2) - 3;
        const dist = Math.min(maxDist, Math.hypot(dx, dy) * 0.15);
        const pupil = eye.querySelector('.mini-timer-pupil');
        if (pupil) {
            pupil.style.transform = `translate(calc(-50% + ${Math.cos(angle) * dist}px), calc(-50% + ${Math.sin(angle) * dist}px))`;
        }
    });
}

/* Cursor tracking for mini eyes — always track + reset idle timer */
document.addEventListener('mousemove', e => {
    dashLastMouseMove = Date.now();
    updateMiniTimerEyes(e.clientX, e.clientY);
});

function formatShortDate(value) {
    return new Date(value).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
}

function toISODate(value) {
    return value.toISOString().slice(0, 10);
}
