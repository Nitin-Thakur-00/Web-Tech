<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet"/>

<!-- SANCTUARY BRUTALIST EFFECTS -->
<div class="noise"></div>
<div class="cursor"></div>
<div class="cursor-follower"></div>

<style>
/* ── Material Symbols ── */
.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-style: normal;
    font-size: 20px;
    line-height: 1;
    letter-spacing: normal;
    text-transform: none;
    display: inline-block;
    white-space: nowrap;
    word-wrap: normal;
    direction: ltr;
    -webkit-font-smoothing: antialiased;
}
.fill-icon { font-variation-settings: 'FILL' 1; }

/* ════════════════════════════════════════
   SIDEBAR — Nocturnal Alchemist Theme
   ════════════════════════════════════════ */
#appSidebar {
    position: fixed;
    left: 0; top: 0;
    height: 100dvh;
    width: 256px;
    display: flex;
    flex-direction: column;
    z-index: 50;
    background: var(--color-background, #0a0a0a);
    border-right: 1px solid var(--glass-border, rgba(244,63,78,0.22));
    box-shadow: 4px 0 48px rgba(0,0,0,0.7), inset -1px 0 0 rgba(244,63,78,0.05);
    transition: width 0.3s cubic-bezier(0.4,0,0.2,1);
    will-change: width;
    overflow: hidden;
}

/* ── Collapsed state ── */
#appSidebar.sidebar-collapsed { width: 68px; }
#appSidebar.sidebar-collapsed .sb-label,
#appSidebar.sidebar-collapsed .sb-brand-text,
#appSidebar.sidebar-collapsed .sb-user-text,
#appSidebar.sidebar-collapsed .sb-footer-label { display: none !important; }
#appSidebar.sidebar-collapsed .sb-header { justify-content: center; padding: 1.5rem 0.625rem; }
#appSidebar.sidebar-collapsed .sb-nav-link {
    justify-content: center;
    padding: 0.6rem;
    border-left-color: transparent !important;
}
#appSidebar.sidebar-collapsed .sb-nav-link.active {
    background: rgba(244,63,78,0.18);
    border-radius: 0.625rem;
}
#appSidebar.sidebar-collapsed .sb-footer { padding: 0.5rem; }
#appSidebar.sidebar-collapsed .sb-footer-btn { justify-content: center; padding: 0.6rem; }
#appSidebar.sidebar-collapsed .sb-user-card { justify-content: center; padding: 0.625rem; }
#collapseBtn { transition: transform 0.3s ease; }
#appSidebar.sidebar-collapsed #collapseBtn { transform: rotate(180deg); }

/* ── Header ── */
.sb-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.75rem 1.25rem 1.25rem;
    flex-shrink: 0;
}
.sb-brand-text { display: flex; align-items: center; gap: 0.5rem; }
.sb-brand-name {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
    font-weight: 900;
    letter-spacing: -0.04em;
    color: var(--color-primary, #f43f5e);
    filter: drop-shadow(0 0 10px rgba(244,63,78,0.5));
    white-space: nowrap;
    line-height: 1;
}
.sb-collapse-btn {
    width: 26px; height: 26px;
    border-radius: 6px;
    border: 1px solid var(--glass-border, rgba(244,63,78,0.22));
    background: transparent;
    cursor: pointer;
    color: var(--color-text-secondary, #a3a3a3);
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
    line-height: 1;
}
.sb-collapse-btn:hover {
    background: rgba(244,63,78,0.1);
    color: var(--color-primary, #f43f5e);
    border-color: rgba(244,63,78,0.4);
}

/* ── Navigation ── */
.sb-nav {
    flex: 1;
    padding: 0.375rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 2px;
    overflow-y: auto;
    scrollbar-width: none;
}
.sb-nav::-webkit-scrollbar { display: none; }
.sb-section-label {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--color-text-tertiary, #525252);
    padding: 0.75rem 0.75rem 0.25rem;
}
.sb-nav-link {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.625rem 0.75rem;
    border-radius: 0.625rem;
    border-left: 2px solid transparent;
    color: var(--color-text-secondary, #a3a3a3);
    text-decoration: none;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    white-space: nowrap;
    transition: all 0.2s;
    position: relative;
}
.sb-nav-link:hover {
    background: rgba(244,63,78,0.06);
    color: var(--color-text-primary, #e5e5e5);
    border-left-color: rgba(244,63,78,0.25);
    transform: translateX(2px);
}
.sb-nav-link.active {
    background: rgba(244,63,78,0.12);
    color: var(--color-primary, #f43f5e);
    border-left-color: var(--color-primary, #f43f5e);
    box-shadow: inset 10px 0 24px rgba(244,63,78,0.07);
}
.sb-nav-link .sb-icon {
    font-size: 19px;
    flex-shrink: 0;
    line-height: 1;
}

/* ── Divider ── */
.sb-divider {
    height: 1px;
    background: var(--glass-border, rgba(244,63,78,0.22));
    margin: 0.375rem 0.75rem;
    flex-shrink: 0;
}

/* ── Footer actions ── */
.sb-footer {
    flex-shrink: 0;
    padding: 0.375rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.sb-footer-btn {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    width: 100%;
    padding: 0.625rem 0.75rem;
    border-radius: 0.625rem;
    border: none;
    background: transparent;
    cursor: pointer;
    color: var(--color-text-secondary, #a3a3a3);
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    white-space: nowrap;
    transition: all 0.2s;
    text-align: left;
}
.sb-footer-btn:hover {
    background: rgba(244,63,78,0.06);
    color: var(--color-primary, #f43f5e);
}

/* ── User card ── */
.sb-user-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0.375rem 0.75rem 1rem;
    padding: 0.625rem 0.875rem;
    border-radius: 0.75rem;
    background: rgba(244,63,78,0.06);
    border: 1px solid var(--glass-border, rgba(244,63,78,0.22));
    flex-shrink: 0;
    transition: border-color 0.2s;
}
.sb-user-card:hover { border-color: rgba(244,63,78,0.35); }
.sb-user-avatar {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: rgba(244,63,78,0.15);
    border: 1px solid rgba(244,63,78,0.3);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: var(--color-primary, #f43f5e);
}
.sb-user-text { min-width: 0; flex: 1; }
.sb-user-name {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.7rem;
    font-weight: 800;
    color: var(--color-primary, #f43f5e);
    letter-spacing: -0.02em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}
.sb-user-sub {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.58rem;
    color: var(--color-text-tertiary, #525252);
    letter-spacing: 0.05em;
    margin-top: 1px;
}

/* ── Content area offset ── */
#mainContentWrapper { transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1); }

/* ── Mobile ── */
#hamburgerBtn { display: none; }
@media (max-width: 768px) {
    #appSidebar {
        transform: translateX(-100%);
        width: 256px !important;
        z-index: 9999;
        transition: transform 0.3s cubic-bezier(0.4,0,0.2,1), width 0s;
    }
    #appSidebar.sidebar-open-mobile { transform: translateX(0); }
    #sidebarOverlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.65);
        z-index: 9998;
        backdrop-filter: blur(4px);
    }
    #sidebarOverlay.active { display: block; }
    #mainContentWrapper { margin-left: 0 !important; }
    #hamburgerBtn { display: flex !important; }
    #hamburgerBtn.sidebar-is-open { display: none !important; z-index: 0; }
    #collapseBtn { display: none !important; } /* Cannot collapse on mobile */
}
</style>

<script>
(function(){
    var t = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
    document.documentElement.classList.toggle('dark', t === 'dark');
}());
</script>

<div id="sidebarOverlay"></div>

<!-- Mobile hamburger -->
<button id="hamburgerBtn" type="button" aria-label="Open menu"
    style="position:fixed;top:1rem;left:1rem;z-index:9990;width:40px;height:40px;border-radius:8px;
           display:none;align-items:center;justify-content:center;
           background:var(--color-surface,#121212);border:1px solid var(--glass-border,rgba(244,63,78,0.22));
           color:var(--color-text-primary,#e5e5e5);cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,0.5);">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
</button>

<!-- ══════════════════════════════════════
     SIDEBAR
══════════════════════════════════════ -->
<aside id="appSidebar">

    <!-- Brand + Collapse -->
    <div class="sb-header">
        <div class="sb-brand-text">
            <span class="sb-brand-name">Chronos</span>
        </div>
        <button id="collapseBtn" class="sb-collapse-btn" aria-label="Collapse sidebar">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="sb-nav" aria-label="Main navigation">
        <?php
        $navItems = [
            ['dashboard', 'dashboard',     'Dashboard'],
            ['todo',      'assignment',    'Tasks'],
            ['focus',     'timer',         'Focus'],
            ['projects',  'folder_special','Projects'],
            ['friends',   'group',         'Friends'],
            ['settings',  'settings',      'Settings'],
        ];
        foreach ($navItems as [$page, $icon, $label]):
            $active = ($currentPage === $page);
        ?>
        <a href="<?= $page ?>.php"
           class="sb-nav-link<?= $active ? ' active' : '' ?>"
           title="<?= $label ?>"
           <?= $active ? 'aria-current="page"' : '' ?>>
            <span class="material-symbols-outlined sb-icon<?= $active ? ' fill-icon' : '' ?>"><?= $icon ?></span>
            <span class="sb-label"><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sb-divider"></div>

    <!-- Footer actions -->
    <div class="sb-footer">
        <button id="themeToggle" class="sb-footer-btn" aria-label="Toggle theme" title="Toggle theme">
            <svg id="sbIconMoon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.8A8.9 8.9 0 1 1 11.2 3 7.1 7.1 0 0 0 21 12.8z"/>
            </svg>
            <svg id="sbIconSun" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                <circle cx="12" cy="12" r="5"/>
                <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
            <span class="sb-footer-label">Toggle Theme</span>
        </button>

        <form action="backend/auth/logout.php" method="post" style="margin:0;padding:0;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <button type="submit" class="sb-footer-btn" title="Sign Out">
                <span class="material-symbols-outlined" style="font-size:16px;">logout</span>
                <span class="sb-footer-label">Sign Out</span>
            </button>
        </form>
    </div>

    <!-- User card -->
    <div class="sb-user-card">
        <div class="sb-user-avatar">
            <span class="material-symbols-outlined fill-icon" style="font-size:16px;">person</span>
        </div>
        <div class="sb-user-text">
            <p id="sidebarUsername" class="sb-user-name">User</p>
            <p id="sidebarUserSub" class="sb-user-sub">CHRONOS_V8</p>
        </div>
    </div>
</aside>

<script>
(function () {
    /* ── Theme icons ── */
    function syncIcons(dark) {
        var m = document.getElementById('sbIconMoon');
        var s = document.getElementById('sbIconSun');
        if (m) m.style.display = dark  ? '' : 'none';
        if (s) s.style.display = !dark ? '' : 'none';
    }
    syncIcons(document.documentElement.classList.contains('dark'));

    var themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var next = !document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark', next);
            document.documentElement.setAttribute('data-theme', next ? 'dark' : 'light');
            localStorage.setItem('theme', next ? 'dark' : 'light');
            syncIcons(next);
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: next ? 'dark' : 'light' } }));
        });
    }

    /* ── Collapse ── */
    var sidebar = document.getElementById('appSidebar');
    var colBtn  = document.getElementById('collapseBtn');
    var KEY     = 'sidebarCollapsed';

    function getContent() { return document.getElementById('mainContentWrapper'); }

    function setSidebarState(collapsed, animate) {
        if (!sidebar) return;
        var content = getContent();
        if (!animate) {
            sidebar.style.transition  = 'none';
            if (content) content.style.transition = 'none';
        }
        sidebar.classList.toggle('sidebar-collapsed', collapsed);
        if (content) content.style.marginLeft = collapsed ? '68px' : '256px';
        localStorage.setItem(KEY, collapsed ? '1' : '0');
        if (!animate) {
            void sidebar.offsetWidth;
            sidebar.style.transition  = '';
            if (content) content.style.transition = '';
        }
    }

    function restoreState() {
        if (window.innerWidth >= 768) {
            setSidebarState(localStorage.getItem(KEY) === '1', false);
        } else {
            var c = getContent();
            if (c) c.style.marginLeft = '0';
        }
    }

    restoreState();
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', restoreState);

    if (colBtn) colBtn.addEventListener('click', function () {
        setSidebarState(!sidebar.classList.contains('sidebar-collapsed'), true);
    });

    /* ── Mobile ── */
    var hamBtn  = document.getElementById('hamburgerBtn');
    var overlay = document.getElementById('sidebarOverlay');
    function openMob()  { 
        sidebar && sidebar.classList.add('sidebar-open-mobile'); 
        overlay && overlay.classList.add('active'); 
        hamBtn && hamBtn.classList.add('sidebar-is-open'); 
        document.body.style.overflow = 'hidden'; 
    }
    function closeMob() { 
        sidebar && sidebar.classList.remove('sidebar-open-mobile'); 
        overlay && overlay.classList.remove('active'); 
        hamBtn && hamBtn.classList.remove('sidebar-is-open'); 
        document.body.style.overflow = ''; 
    }

    if (hamBtn)  hamBtn.addEventListener('click', openMob);
    if (overlay) overlay.addEventListener('click', closeMob);
    if (sidebar) sidebar.querySelectorAll('.sb-nav-link').forEach(function(a) {
        a.addEventListener('click', function() { if (window.innerWidth < 768) closeMob(); });
    });

    /* ── User info ── */
    window.addEventListener('userLoaded', function (e) {
        var u = e.detail || {};
        var el1 = document.getElementById('sidebarUsername');
        var el2 = document.getElementById('sidebarUserSub');
        var avBox = document.querySelector('.sb-user-card .sb-user-avatar');
        
        if (el1) el1.textContent = u.full_name || u.username || 'User';
        if (el2) el2.textContent = (u.username || 'CHRONOS_V8').toUpperCase();
        
        if (avBox) {
            if (u.profile_pic) {
                var noCache = u.profile_pic.startsWith('http') ? u.profile_pic : u.profile_pic + '?t=' + Date.now();
                avBox.innerHTML = '<img style="width:100%;height:100%;object-fit:cover;border-radius:inherit;" src="' + noCache + '" alt="">';
                avBox.style.padding = '0';
            } else {
                avBox.innerHTML = '<span class="material-symbols-outlined fill-icon" style="font-size:16px;">person</span>';
            }
        }
    });

    /* ── Resize ── */
    window.addEventListener('resize', function () {
        if (window.innerWidth < 768) {
            var c = getContent(); if (c) c.style.marginLeft = '0';
        } else {
            setSidebarState(sidebar && sidebar.classList.contains('sidebar-collapsed'), false);
        }
    });
}());
</script>
