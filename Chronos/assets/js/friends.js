document.addEventListener('DOMContentLoaded', async () => {
    bindFriendControls();
    await loadFriends();
});

function bindFriendControls() {
    document.getElementById('friendSearchInput')?.addEventListener('input', debounce(handleFriendSearch, 250));
    document.getElementById('btnAddFriend')?.addEventListener('click', async () => {
        const input = document.getElementById('friendSearchInput');
        const raw = input.value.trim().replace(/^#/, '');
        if (!raw) { showToast('Enter a username or UID first.', 'warning'); return; }
        try {
            await API.sendFriendRequest(raw);
            input.value = '';
            document.getElementById('searchResults').innerHTML = '';
            showToast('Friend request sent.', 'success');
            await loadFriends();
        } catch (error) {
            showToast(error.message || 'Could not send friend request.', 'danger');
        }
    });
}

async function loadFriends() {
    const requestsGrid = document.getElementById('requestsGrid');
    const friendsGrid  = document.getElementById('friendsGrid');
    requestsGrid.innerHTML = '<div class="skeleton" style="height:72px;"></div>';
    friendsGrid.innerHTML  = '<div class="skeleton" style="height:72px;"></div>';
    try {
        const response = await API.getFriends();
        const records  = response.data || [];
        const incoming = records.filter(r => r.status === 'pending' && r.direction === 'incoming');
        const accepted = records.filter(r => r.status === 'accepted');
        renderFriendRequests(incoming);
        renderFriendList(accepted);
    } catch (error) {
        requestsGrid.innerHTML = emptyState('Requests unavailable', 'We could not load pending requests.');
        friendsGrid.innerHTML  = emptyState('Friends unavailable', 'We could not load your connections.');
    }
}

function emptyState(title, sub) {
    return `<div class="empty-state" style="min-height:120px;grid-column:1/-1;">
        <h3>${title}</h3><p>${sub}</p></div>`;
}

function renderFriendRequests(requests) {
    const section = document.getElementById('requestsSection');
    const grid    = document.getElementById('requestsGrid');
    const badge   = document.getElementById('requestsCount');
    if (!requests.length) { section.classList.add('hidden'); return; }
    section.classList.remove('hidden');
    if (badge) badge.textContent = requests.length;

    grid.innerHTML = requests.map(req => `
        <article class="fr-card">
            <img class="fr-avatar" src="${escapeHTML(req.profile_pic || 'assets/images/default-avatar.png')}" alt="">
            <div class="fr-card-info">
                <div class="fr-name">${escapeHTML(req.username)}</div>
                <div class="fr-bio">${escapeHTML(req.full_name || 'Wants to connect')}</div>
            </div>
            <div class="fr-actions">
                <button class="btn btn-primary" data-accept-id="${req.id}" type="button">Accept</button>
                <button class="btn btn-secondary" data-decline-id="${req.id}" type="button">Decline</button>
            </div>
        </article>`).join('');

    grid.querySelectorAll('[data-accept-id]').forEach(btn => {
        btn.addEventListener('click', async () => {
            try { await API.acceptFriendRequest(Number(btn.dataset.acceptId)); showToast('Request accepted.', 'success'); await loadFriends(); }
            catch (e) { showToast(e.message || 'Could not accept request.', 'danger'); }
        });
    });
    grid.querySelectorAll('[data-decline-id]').forEach(btn => {
        btn.addEventListener('click', async () => {
            try { await API.declineFriendRequest(Number(btn.dataset.declineId)); showToast('Request declined.', 'success'); await loadFriends(); }
            catch (e) { showToast(e.message || 'Could not decline request.', 'danger'); }
        });
    });
}

function renderFriendList(friends) {
    const grid  = document.getElementById('friendsGrid');
    const badge = document.getElementById('friendsCount');
    if (badge) badge.textContent = friends.length;

    if (!friends.length) {
        grid.innerHTML = emptyState('No friends yet', 'Search above to add collaborators by username or UID.');
        return;
    }
    grid.innerHTML = friends.map(f => `
        <article class="fr-card">
            <img class="fr-avatar" src="${escapeHTML(f.profile_pic || 'assets/images/default-avatar.png')}" alt="">
            <div class="fr-card-info">
                <div class="fr-name">${escapeHTML(f.username)}</div>
                <div class="fr-bio">${escapeHTML(f.full_name || f.bio || 'Chronos user')}</div>
            </div>
            <div class="fr-actions">
                <button class="btn btn-danger" data-remove-id="${f.id}" type="button">Remove</button>
            </div>
        </article>`).join('');

    grid.querySelectorAll('[data-remove-id]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const ok = await showConfirmDialog({ title: 'Remove friend', message: 'Remove this connection?', confirmText: 'Remove', isDanger: true });
            if (!ok) return;
            try { await API.removeFriend(Number(btn.dataset.removeId)); showToast('Friend removed.', 'success'); await loadFriends(); }
            catch (e) { showToast(e.message || 'Could not remove friend.', 'danger'); }
        });
    });
}

async function handleFriendSearch() {
    const input   = document.getElementById('friendSearchInput');
    const results = document.getElementById('searchResults');
    const query   = input.value.trim();
    if (query.length < 2) { results.innerHTML = ''; return; }

    try {
        const response = await API.searchUsers(query);
        const users = response.data || [];
        if (!users.length) {
            results.innerHTML = `<div class="empty-state" style="grid-column:1/-1;min-height:80px;"><h3>No matches</h3><p>Try a different username or UID.</p></div>`;
            return;
        }
        results.innerHTML = users.map(u => `
            <article class="fr-search-card">
                <img class="fr-avatar" src="${escapeHTML(u.profile_pic || 'assets/images/default-avatar.png')}" alt="">
                <div class="fr-name">${escapeHTML(u.username)}</div>
                <div class="fr-bio">${escapeHTML(u.full_name || u.bio || 'Chronos user')}</div>
                <button class="btn btn-primary" data-user-id="${u.id}" type="button" style="width:100%;margin-top:4px;">Add</button>
            </article>`).join('');

        results.querySelectorAll('[data-user-id]').forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    await API.sendFriendRequest(Number(btn.dataset.userId));
                    showToast('Friend request sent.', 'success');
                    results.innerHTML = '';
                    document.getElementById('friendSearchInput').value = '';
                    await loadFriends();
                } catch (e) { showToast(e.message || 'Could not send request.', 'danger'); }
            });
        });
    } catch (error) {
        results.innerHTML = `<div class="empty-state" style="grid-column:1/-1;min-height:80px;"><h3>Search unavailable</h3><p>We could not search right now.</p></div>`;
    }
}
