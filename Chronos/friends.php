<?php
require_once __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/helpers/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$myUid = '#' . str_pad($_SESSION['user_id'], 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends | Chronos</title>
    <meta name="description" content="Search for friends, manage requests, and keep collaboration close.">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);if(t==='dark')document.documentElement.classList.add('dark');}());</script>
    <link rel="stylesheet" href="assets/css/theme-light.css">
    <link rel="stylesheet" href="assets/css/theme-dark.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
        /* ── Friends layout ── */
        .friends-layout {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-4);
        }
        .friends-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .friends-uid-badge {
            padding: 1rem 1.5rem;
            text-align: center;
            flex-shrink: 0;
        }
        .friends-uid-num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.75rem;
            font-weight: 900;
            color: var(--color-primary);
            letter-spacing: -0.04em;
            display: block;
            margin-top: 4px;
            filter: drop-shadow(0 0 12px rgba(var(--color-primary-rgb), 0.5));
        }

        /* ── Search bar ── */
        .friends-search-bar {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 1.25rem;
        }
        .friends-search-wrap {
            flex: 1;
            position: relative;
        }
        .friends-search-wrap svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-tertiary);
            pointer-events: none;
        }
        .friends-search-input {
            width: 100%;
            background: var(--color-background);
            border: 1px solid var(--glass-border);
            border-radius: 999px;
            padding: 0.625rem 1rem 0.625rem 2.75rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            color: var(--color-text-primary);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .friends-search-input::placeholder { color: var(--color-text-tertiary); }
        .friends-search-input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb), 0.12);
        }

        /* ── Search result grid ── */
        .friends-results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
            margin-top: 1.25rem;
        }
        .fr-search-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 1.25rem 1rem;
            border-radius: 0.75rem;
            background: rgba(var(--color-primary-rgb), 0.03);
            border: 1px solid var(--glass-border);
            text-align: center;
            transition: all 0.2s;
            cursor: default;
        }
        .fr-search-card:hover {
            background: rgba(var(--color-primary-rgb), 0.07);
            border-color: rgba(var(--color-primary-rgb), 0.35);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .fr-avatar {
            width: 52px; height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(var(--color-primary-rgb), 0.35);
            box-shadow: 0 0 0 4px rgba(var(--color-primary-rgb), 0.06);
        }
        .fr-search-card .fr-name {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--color-text-primary);
            letter-spacing: -0.01em;
        }
        .fr-search-card .fr-bio {
            font-size: 0.7rem;
            color: var(--color-text-secondary);
            margin-top: -4px;
        }

        /* ── Friend list (horizontal rows) ── */
        .friends-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 1rem;
        }
        .fr-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            border-radius: 0.75rem;
            background: rgba(var(--color-primary-rgb), 0.03);
            border: 1px solid var(--glass-border);
            transition: all 0.2s;
        }
        .fr-card:hover {
            background: rgba(var(--color-primary-rgb), 0.06);
            border-color: rgba(var(--color-primary-rgb), 0.3);
        }
        .fr-card .fr-avatar {
            width: 42px; height: 42px;
            flex-shrink: 0;
        }
        .fr-card-info { flex: 1; min-width: 0; }
        .fr-name {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--color-text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .fr-bio {
            font-size: 0.75rem;
            color: var(--color-text-secondary);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .fr-card .fr-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        /* ── Friends count badge ── */
        .fr-count-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: var(--color-primary);
            background: rgba(var(--color-primary-rgb), 0.12);
            padding: 2px 10px;
            border-radius: 999px;
            border: 1px solid rgba(var(--color-primary-rgb), 0.25);
        }
    </style>
</head>
<body>
<div class="bg-mesh" aria-hidden="true"></div>
<div class="app-container">
    <?php include 'partials/sidebar.php'; ?>

    <main id="mainContentWrapper" class="content-wrapper" role="main">
        <div class="page-shell page-enter friends-layout">

            <!-- Page header -->
            <header class="page-header friends-hero">
                <div>
                    <p class="page-header__eyebrow">Friends</p>
                    <h1 class="page-header__title">Collaboration Circle.</h1>
                    <p class="page-header__subtitle">Search by username or UID, respond to requests, and manage your connections.</p>
                </div>
                <div class="sanctuary-surface friends-uid-badge">
                    <p class="section-kicker">Your UID</p>
                    <span class="friends-uid-num"><?php echo htmlspecialchars($myUid); ?></span>
                </div>
            </header>

            <!-- Search -->
            <section class="sanctuary-surface section-card">
                <div class="section-header">
                    <div>
                        <p class="section-kicker">Discover</p>
                        <h2 class="section-title">Find collaborators</h2>
                    </div>
                </div>
                <div class="friends-search-bar">
                    <div class="friends-search-wrap">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input class="friends-search-input" id="friendSearchInput" type="search"
                               placeholder="Search by username or enter UID...">
                    </div>
                    <button class="btn btn-primary" id="btnAddFriend" type="button">Add by UID</button>
                </div>
                <div class="friends-results-grid" id="searchResults"></div>
            </section>

            <!-- Pending requests (hidden when empty) -->
            <section class="sanctuary-surface section-card hidden" id="requestsSection">
                <div class="section-header">
                    <div>
                        <p class="section-kicker">Pending</p>
                        <h2 class="section-title">Incoming requests</h2>
                    </div>
                    <span id="requestsCount" class="fr-count-badge">0</span>
                </div>
                <div class="friends-list" id="requestsGrid"></div>
            </section>

            <!-- Friends list -->
            <section class="sanctuary-surface section-card">
                <div class="section-header">
                    <div>
                        <p class="section-kicker">Connected</p>
                        <h2 class="section-title">Your friends</h2>
                    </div>
                    <span id="friendsCount" class="fr-count-badge">0</span>
                </div>
                <div class="friends-list" id="friendsGrid"></div>
            </section>

        </div>
    </main>
</div>

<script src="assets/js/utils.js"></script>
<script src="assets/js/api.js"></script>
<script src="assets/js/store.js"></script>
<script src="assets/js/friends.js"></script>
</body>
</html>
