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
    <title>Settings | Chronos</title>
    <meta name="description" content="Manage your Chronos profile, password, avatar, and account controls.">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);if(t==='dark')document.documentElement.classList.add('dark');}());</script>
    <link rel="stylesheet" href="assets/css/theme-light.css">
    <link rel="stylesheet" href="assets/css/theme-dark.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
        .settings-stack {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            max-width: 860px;
            margin: 0 auto;
            width: 100%;
        }
        /* Generous padding for settings cards (override section-card default) */
        .settings-stack .sanctuary-surface { padding: 2rem; }
        /* Force dark background on all inputs in dark mode */
        [data-theme='dark'] .settings-stack .input-base,
        [data-theme='dark'] .settings-stack input,
        [data-theme='dark'] .settings-stack textarea,
        [data-theme='dark'] .settings-stack select {
            background: #1a1a1a !important;
            color: #e5e5e5 !important;
            border-color: rgba(255,255,255,0.1) !important;
        }
        /* Light mode inputs */
        [data-theme='light'] .settings-stack .input-base,
        [data-theme='light'] .settings-stack input,
        [data-theme='light'] .settings-stack textarea,
        [data-theme='light'] .settings-stack select {
            background: #f8f8f8 !important;
            color: #111111 !important;
            border-color: rgba(153,27,27,0.15) !important;
        }
        /* Danger zone — red tint override on top of sanctuary-surface */
        .danger-zone {
            border-color: rgba(239,68,68,0.3) !important;
            background: rgba(239,68,68,0.06) !important;
        }
        .danger-zone h3 {
            color: #ef4444;
            font-size: 1rem;
            font-weight: 800;
            margin: 0 0 0.5rem 0;
        }
        .danger-zone p {
            color: var(--color-text-secondary);
            font-size: 0.875rem;
            margin: 0 0 1.25rem 0;
        }
        /* Grid helpers */
        .settings-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 600px) {
            .settings-grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="bg-mesh" aria-hidden="true"></div>
<div class="app-container">
    <?php include 'partials/sidebar.php'; ?>

    <main id="mainContentWrapper" class="content-wrapper" role="main">
        <div class="page-shell page-enter">
            <header class="page-header page-header--simple">
                <div>
                    <p class="page-header__eyebrow">Settings</p>
                    <h1 class="page-header__title">Profile, security, and account controls.</h1>
                    <p class="page-header__subtitle">Update your identity, change your password, and manage irreversible actions from one place.</p>
                </div>
            </header>

            <section class="settings-stack">
                <article class="sanctuary-surface">
                    <div class="section-header">
                        <div>
                            <p class="section-kicker">Profile</p>
                            <h2 class="section-title">Public identity</h2>
                        </div>
                    </div>
                    <div class="toolbar-group" style="align-items:center;">
                        <img id="settingsAvatar" src="assets/images/default-avatar.png" alt="Profile avatar" style="width:84px;height:84px;border-radius:50%;object-fit:cover;">
                        <div>
                            <h3 class="section-title" id="displayUsername" style="margin:0;">Loading...</h3>
                            <p class="section-meta" id="displayEmail">Loading account…</p>
                            <p class="section-kicker" id="displayUID">#0000</p>
                        </div>
                        <label class="btn btn-secondary" for="avatarInput" style="margin-left:auto;">Change avatar</label>
                        <input id="avatarInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden">
                    </div>

                    <div class="utility-grid utility-grid--two" style="margin-top:1rem;">
                        <div class="input-group">
                            <label class="input-label" for="setFullName">Full name</label>
                            <input class="input-base" id="setFullName" type="text">
                        </div>
                        <div class="input-group">
                            <label class="input-label" for="setUsername">Username</label>
                            <input class="input-base" id="setUsername" type="text">
                        </div>
                    </div>

                    <div class="input-group" style="margin-top:1rem;">
                        <label class="input-label" for="setBio">Bio</label>
                        <textarea class="input-base" id="setBio" placeholder="Tell people what you work on."></textarea>
                    </div>

                    <div class="toolbar-group" style="justify-content:flex-end;margin-top:1rem;">
                        <button class="btn btn-primary" id="btnSaveProfile" type="button">Save profile</button>
                    </div>
                </article>

                <article class="sanctuary-surface">
                    <div class="section-header">
                        <div>
                            <p class="section-kicker">Security</p>
                            <h2 class="section-title">Password</h2>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="currentPassword">Current password</label>
                        <input class="input-base" id="currentPassword" type="password" autocomplete="current-password">
                    </div>
                    <div class="utility-grid utility-grid--two" style="margin-top:1rem;">
                        <div class="input-group">
                            <label class="input-label" for="newPassword">New password</label>
                            <input class="input-base" id="newPassword" type="password" autocomplete="new-password">
                        </div>
                        <div class="input-group">
                            <label class="input-label" for="confirmPassword">Confirm password</label>
                            <input class="input-base" id="confirmPassword" type="password" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="toolbar-group" style="justify-content:flex-end;margin-top:1rem;">
                        <button class="btn btn-primary" id="btnChangePassword" type="button">Update password</button>
                    </div>
                </article>

                <article class="sanctuary-surface danger-zone">
                    <h3>Delete account</h3>
                    <p>Deleting your account removes your tasks, projects, chats, sessions, and profile data permanently.</p>
                    <div class="utility-grid utility-grid--two">
                        <div class="input-group">
                            <label class="input-label" for="deletePassword">Confirm with password</label>
                            <input class="input-base" id="deletePassword" type="password" autocomplete="current-password" placeholder="Enter your password">
                        </div>
                        <div style="display:flex;align-items:end;">
                            <button class="btn btn-danger" id="btnDeleteAccount" type="button">Delete my account</button>
                        </div>
                    </div>
                </article>
            </section>
            
            <div style="width: 100%; text-align: center; margin-top: 40px; padding-bottom: 20px; font-size: 10px; opacity: 0.4; color: var(--color-text-secondary); letter-spacing: 1.5px; text-transform: uppercase;">
                Made By Nitin Thakur
            </div>
        </div>
    </main>
</div>

<script src="assets/js/utils.js"></script>
<script src="assets/js/api.js"></script>
<script src="assets/js/store.js"></script>
<script src="assets/js/settings.js"></script>
</body>
</html>
