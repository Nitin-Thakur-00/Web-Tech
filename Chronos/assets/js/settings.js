document.addEventListener('DOMContentLoaded', async () => {
    bindSettingsControls();
    await loadSettingsData();
});

function bindSettingsControls() {
    document.getElementById('avatarInput')?.addEventListener('change', uploadAvatar);
    document.getElementById('btnSaveProfile')?.addEventListener('click', saveProfile);
    document.getElementById('btnChangePassword')?.addEventListener('click', changePassword);
    document.getElementById('btnDeleteAccount')?.addEventListener('click', deleteAccount);
}

async function loadSettingsData(force = false) {
    if (force) {
        store.invalidateUser();
    }

    try {
        const response = await API.getUserProfile();
        const user = response.data || {};

        document.getElementById('displayUsername').textContent = user.full_name || user.username || 'Chronos User';
        document.getElementById('displayEmail').textContent = user.email || '';
        document.getElementById('displayUID').textContent = `#${String(user.id || '').padStart(4, '0')}`;
        document.getElementById('setFullName').value = user.full_name || '';
        document.getElementById('setUsername').value = user.username || '';
        document.getElementById('setBio').value = user.bio || '';

        if (user.profile_pic) {
            document.getElementById('settingsAvatar').src = user.profile_pic;
        }
    } catch (error) {
        showToast('Unable to load settings. Please refresh the page.', 'danger');
    }
}

async function uploadAvatar(event) {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }

    if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
        showToast('Use a PNG, JPEG or WEBP image.', 'warning');
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        showToast('Avatar must be 2MB or less.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('avatar', file);

    try {
        const response = await API.uploadAvatar(formData);
        // Prepend base path so relative upload path resolves correctly from document root
        const avatarUrl = response.data.avatar_url;
        const imgSrc = avatarUrl.startsWith('http') ? avatarUrl : `${avatarUrl}?t=${Date.now()}`;
        document.getElementById('settingsAvatar').src = imgSrc;
        store.invalidateUser();
        await store.loadUser(true);
        showToast('Avatar updated.', 'success');
    } catch (error) {
        showToast('Avatar upload failed. Check file size and type.', 'danger');
    } finally {
        event.target.value = '';
    }
}

async function saveProfile() {
    const username = document.getElementById('setUsername').value.trim();
    if (!username) {
        showToast('Username is required.', 'warning');
        return;
    }

    try {
        await API.updateProfile({
            full_name: document.getElementById('setFullName').value.trim(),
            username,
            bio: document.getElementById('setBio').value.trim(),
        });

        store.invalidateUser();
        await store.loadUser(true);
        await loadSettingsData(true);
        showToast('Profile updated.', 'success');
    } catch (error) {
        // API.request already shows error toast; re-show in case silent
        showToast(error.message || 'Failed to update profile.', 'danger');
    }
}

async function changePassword() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (!currentPassword || !newPassword || !confirmPassword) {
        showToast('Fill in all password fields.', 'warning');
        return;
    }

    if (newPassword.length < 8) {
        showToast('New password must be at least 8 characters.', 'warning');
        return;
    }

    if (newPassword !== confirmPassword) {
        showToast('Passwords do not match.', 'warning');
        return;
    }

    try {
        await API.changePassword({
            current_password: currentPassword,
            new_password: newPassword,
        });

        ['currentPassword', 'newPassword', 'confirmPassword'].forEach((id) => {
            document.getElementById(id).value = '';
        });
        showToast('Password updated successfully.', 'success');
    } catch (error) {
        showToast(error.message || 'Failed to change password.', 'danger');
    }
}

async function deleteAccount() {
    const password = document.getElementById('deletePassword').value;
    if (!password) {
        showToast('Enter your password to confirm deletion.', 'warning');
        return;
    }

    const confirmed = await showConfirmDialog({
        title: 'Delete account',
        message: 'This permanently removes your account and all related data. This cannot be undone.',
        confirmText: 'Delete account',
        isDanger: true
    });

    if (!confirmed) {
        return;
    }

    try {
        await API.deleteAccount({ password });
        window.location.href = 'index.php';
    } catch (error) {
        showToast(error.message || 'Failed to delete account.', 'danger');
    }
}
