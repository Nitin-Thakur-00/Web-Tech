const API = {
    baseURL: 'backend/api/',

    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },

    async request(endpoint, options = {}) {
        const isFormData = options.body instanceof FormData;
        const headers = {
            'X-CSRF-Token': this.getCSRFToken(),
            ...(options.headers || {}),
        };

        if (!isFormData && !headers['Content-Type']) {
            headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(this.baseURL + endpoint, {
            credentials: 'same-origin',
            ...options,
            headers,
        });

        let payload = null;

        try {
            payload = await response.json();
        } catch (error) {
            payload = { success: false, error: 'Invalid server response.' };
        }

        if (response.status === 401 && !endpoint.includes('../auth/')) {
            window.location.href = 'index.php';
            throw new Error('Session expired.');
        }

        if (!response.ok || payload?.success === false) {
            const message = payload?.error || payload?.message || 'Something went wrong.';
            if (!options.silent && typeof showToast === 'function') {
                showToast(message, 'danger');
            }
            throw new Error(message);
        }

        return payload;
    },

    login(data) {
        return this.request('../auth/login.php', { method: 'POST', body: JSON.stringify(data) });
    },

    register(data) {
        return this.request('../auth/register.php', { method: 'POST', body: JSON.stringify(data) });
    },

    getUserProfile() {
        return this.request('user/profile.php');
    },

    updateProfile(data) {
        return this.request('user/profile.php', { method: 'PUT', body: JSON.stringify(data) });
    },

    uploadAvatar(formData) {
        return this.request('user/upload-avatar.php', { method: 'POST', body: formData });
    },

    changePassword(data) {
        return this.request('user/change-password.php', { method: 'POST', body: JSON.stringify(data) });
    },

    deleteAccount(data) {
        return this.request('user/delete-account.php', { method: 'POST', body: JSON.stringify(data) });
    },

    getTasks(filter = 'all', projectId = '', tag = '') {
        const params = new URLSearchParams({ filter });
        if (projectId) {
            params.set('project_id', projectId);
        }
        if (tag) {
            params.set('tag', tag);
        }
        return this.request(`tasks/index.php?${params.toString()}`);
    },

    createTask(data) {
        return this.request('tasks/index.php', { method: 'POST', body: JSON.stringify(data) });
    },

    updateTask(id, data) {
        return this.request(`tasks/[id].php?id=${encodeURIComponent(id)}`, { method: 'PUT', body: JSON.stringify(data) });
    },

    deleteTask(id) {
        return this.request(`tasks/[id].php?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
    },

    completeTask(id, isCompleted) {
        return this.request('tasks/complete.php', { method: 'PUT', body: JSON.stringify({ task_id: id, is_completed: isCompleted }) });
    },

    getProjects(includePast = false) {
        return this.request(`projects/index.php?include_past=${includePast ? 'true' : 'false'}`);
    },

    getProject(id) {
        return this.request(`projects/[id].php?id=${encodeURIComponent(id)}`);
    },

    createProject(data) {
        return this.request('projects/index.php', { method: 'POST', body: JSON.stringify(data) });
    },

    updateProject(id, data) {
        return this.request(`projects/[id].php?id=${encodeURIComponent(id)}`, { method: 'PUT', body: JSON.stringify(data) });
    },

    deleteProject(id) {
        return this.request(`projects/[id].php?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
    },

    getProjectSubtasks(projectId) {
        return this.request(`projects/subtasks.php?project_id=${encodeURIComponent(projectId)}`);
    },

    createSubtask(data) {
        return this.request('projects/subtasks.php', { method: 'POST', body: JSON.stringify(data) });
    },

    toggleSubtask(id, isCompleted) {
        return this.request('projects/subtasks.php', { method: 'PUT', body: JSON.stringify({ id, is_completed: isCompleted }) });
    },

    updateSubtask(id, data) {
        return this.request('projects/subtasks.php', { method: 'PUT', body: JSON.stringify({ id, ...data }) });
    },

    deleteSubtask(id) {
        return this.request('projects/subtasks.php', { method: 'DELETE', body: JSON.stringify({ id }) });
    },

    getProjectMembers(projectId) {
        return this.request(`projects/members.php?project_id=${encodeURIComponent(projectId)}`);
    },

    inviteMember(data) {
        return this.request('projects/members.php', { method: 'POST', body: JSON.stringify(data) });
    },

    removeMember(data) {
        return this.request('projects/members.php', { method: 'DELETE', body: JSON.stringify(data) });
    },

    getChat(projectId, limit = 20) {
        return this.request(`projects/chat.php?project_id=${encodeURIComponent(projectId)}&limit=${encodeURIComponent(limit)}`);
    },

    sendChat(data) {
        return this.request('projects/chat.php', { method: 'POST', body: JSON.stringify(data) });
    },

    logSession(data) {
        return this.request('timers/session.php', { method: 'POST', body: JSON.stringify(data) });
    },

    getSessions(limit = 8) {
        return this.request(`timers/sessions.php?limit=${encodeURIComponent(limit)}`);
    },

    getHeatmap(year) {
        return this.request(`timers/heatmap.php?year=${encodeURIComponent(year)}`);
    },

    getCalendarEvents(startDate, endDate) {
        const params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate,
        });
        return this.request(`calendar/events.php?${params.toString()}`);
    },

    searchCalendarDate(date) {
        return this.request('calendar/search.php', { method: 'POST', body: JSON.stringify({ date }) });
    },

    getFriends() {
        return this.request('friends/index.php');
    },

    searchUsers(query) {
        return this.request(`friends/search.php?q=${encodeURIComponent(query)}`);
    },

    sendFriendRequest(identifier) {
        const payload = typeof identifier === 'number' || /^\d+$/.test(String(identifier))
            ? { friend_id: Number(identifier) }
            : { username: String(identifier) };

        return this.request('friends/request.php', { method: 'POST', body: JSON.stringify(payload) });
    },

    acceptFriendRequest(friendId) {
        return this.request('friends/accept.php', { method: 'POST', body: JSON.stringify({ friend_id: friendId }) });
    },

    declineFriendRequest(friendId) {
        return this.request('friends/decline.php', { method: 'POST', body: JSON.stringify({ friend_id: friendId }) });
    },

    removeFriend(friendId) {
        return this.request('friends/remove.php', { method: 'DELETE', body: JSON.stringify({ friend_id: friendId }) });
    },
};
