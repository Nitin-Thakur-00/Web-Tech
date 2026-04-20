<?php
require_once __DIR__ . '/backend/config/env.php';
require_once __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/middleware/csrf.php';
require_once __DIR__ . '/backend/helpers/session.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHRONOS - Access</title>
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <link rel="stylesheet" href="assets/css/theme-light.css">
    <link rel="stylesheet" href="assets/css/theme-dark.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
        /* REVERT BRUTALIST EFFECTS FOR INDEX PAGE ONLY */
        :root, html[data-theme='dark'] {
            --color-primary: #f43f5e;
            --color-primary-rgb: 244, 63, 78;
            --color-secondary: #881337;
            --color-background: #0a0a0a;
            --color-surface: #121212;
            --radius-sm: 0.75rem;
            --radius-md: 1rem;
            --radius-lg: 1.5rem;
            --radius-xl: 1.75rem;
            --radius-pill: 999px;
            --font-heading: 'Space Grotesk', sans-serif;
            --font-body: 'Inter', sans-serif;
            --btn-primary-bg: #f43f5e;
            --btn-primary-hover: #fb7185;
            --btn-primary-text: #ffffff;
        }
        
        body { font-family: var(--font-body) !important; color: #e5e5e5; }
        
        .btn {
            border-radius: 12px !important;
            font-family: var(--font-body) !important;
        }
        
        .tab {
            border-radius: 12px !important;
        }
        .auth-wrapper {
            display: flex; flex-wrap: wrap; justify-content: center; align-items: center;
            gap: 70px; z-index: 10; max-width: 1200px; width: 90%; position: relative;
            margin: 0 auto; min-height: 100vh; padding: 40px 0; box-sizing: border-box;
        }

        /* Recessed 3D Window */
        .creatures-window {
            width: 580px; height: 360px;
            background: var(--color-surface-glass);
            border-radius: 35px;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(30px) saturate(160%);
            -webkit-backdrop-filter: blur(30px) saturate(160%);
            box-shadow: 0 30px 60px rgba(0,0,0,0.5), inset 0 0 20px rgba(255,255,255,0.05);
            position: relative; overflow: hidden; flex-shrink: 0;
            display: flex; justify-content: center;
        }

        /* Sky Environments */
        .sky-dark, .sky-light {
            position: absolute; inset: 0; z-index: 0;
            pointer-events: none; transition: opacity 0.8s ease;
        }
        .sky-dark {
            background-color: rgba(0, 0, 0, 0.4);
        }
        .sky-light {
            background: linear-gradient(180deg, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0) 100%);
        }
        [data-theme='light'] .sky-dark { opacity: 0; }
        [data-theme='dark'] .sky-light { opacity: 0; }
        
        [data-theme='light'] .creatures-window {
            box-shadow: inset 0 5px 25px rgba(0,0,0,0.05), var(--glass-shadow);
        }

        .moon {
            width: 65px; height: 65px; background: transparent; border-radius: 50%;
            box-shadow: 15px 15px 0 0 rgba(255,255,255,0.9);
            position: absolute; top: 25px; right: 80px;
            filter: drop-shadow(0 0 10px rgba(255,255,255,0.4));
        }
        
        .star-field {
            position: absolute; inset: 0; z-index: 1;
            background-image: 
                radial-gradient(2px 2px at 20px 30px, #eee, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 50px 70px, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 100px 10px, #ddd, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 140px 140px, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 180px 80px, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 220px 110px, #ddd, rgba(0,0,0,0)),
                radial-gradient(3px 3px at 260px 40px, #fff, rgba(0,0,0,0));
            background-repeat: repeat;
            background-size: 300px 200px;
            animation: twinkle 4s infinite alternate ease-in-out;
        }
        @keyframes twinkle { 0% { opacity: 0.3; } 100% { opacity: 1; } }

        .sun {
            width: 80px; height: 80px; background: #FFD700; border-radius: 50%;
            position: absolute; top: 35px; left: 50px;
            box-shadow: 0 0 50px rgba(255, 215, 0, 0.7), inset 0 0 15px rgba(255, 140, 0, 0.5);
            animation: pulseSun 8s infinite alternate ease-in-out;
        }
        @keyframes pulseSun {
            0% { box-shadow: 0 0 50px rgba(255, 215, 0, 0.7), inset 0 0 15px rgba(255, 140, 0, 0.5); }
            100% { box-shadow: 0 0 70px rgba(255, 215, 0, 0.9), inset 0 0 20px rgba(255, 140, 0, 0.7); }
        }

        .cloud {
            width: 110px; height: 35px; background: rgba(255, 255, 255, 0.2);
            border-radius: 40px; position: absolute; border: 1px solid var(--glass-border);
            backdrop-filter: blur(5px);
            animation: floatCloud 25s linear infinite alternate;
        }
        @keyframes floatCloud { 0% { transform: translateX(-15px); } 100% { transform: translateX(25px); } }
        .cloud::before, .cloud::after { content: ''; position: absolute; background: rgba(255, 255, 255, 0.95); border-radius: 50%; }
        .cloud::before { width: 55px; height: 55px; top: -25px; left: 20px; }
        .cloud::after { width: 40px; height: 40px; top: -15px; right: 20px; }

        /* Creatures */
        .creatures-container {
            display: flex; align-items: flex-end; justify-content: space-evenly;
            width: 100%; height: 100%; position: absolute;
            bottom: -15px; padding: 0 20px; box-sizing: border-box; z-index: 10;
        }

        .creature {
            display: flex; justify-content: center; align-items: flex-start;
            padding-top: 25px; gap: 4px; position: relative;
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.3s ease;
            box-shadow: inset -5px -5px 15px rgba(0,0,0,0.1), inset 5px 5px 15px rgba(255,255,255,0.5), 0 10px 30px rgba(0,0,0,0.3);
            z-index: 1;
        }
        .creature:hover { transform: translateY(0%) scale(1.05) !important; z-index: 15; filter: brightness(1.1); }

        .c1 { width: 130px; height: 210px; background: linear-gradient(135deg, #ff9a9e, #fecfef); border-radius: 50px 50px 15px 15px; transform: translateY(60%); }
        .c2 { width: 140px; height: 260px; background: linear-gradient(135deg, #a1c4fd, #c2e9fb); border-radius: 60px 60px 15px 15px; transform: translateY(66%); }
        .c3 { width: 150px; height: 290px; background: linear-gradient(135deg, #cd9cf2, #f6f3ff); border-radius: 70px 70px 15px 15px; transform: translateY(53%); }
        .c4 { width: 130px; height: 190px; background: linear-gradient(135deg, #fbc2eb, #a6c1ee); border-radius: 50px 50px 15px 15px; transform: translateY(58%); }

        [data-theme='light'] .c1 { background: linear-gradient(135deg, #de545b, #c46fab); }
        [data-theme='light'] .c2 { background: linear-gradient(135deg, #447ce0, #4dafd9); }
        [data-theme='light'] .c3 { background: linear-gradient(135deg, #993ce8, #8679ba); }
        [data-theme='light'] .c4 { background: linear-gradient(135deg, #c961ac, #4f7dc9); }

        .eye {
            width: 52px; height: 52px; flex-shrink: 0; background: #fff; border-radius: 50%;
            position: relative; overflow: hidden; margin: 0 2px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2), inset 0 2px 5px rgba(0,0,0,0.1);
        }
        .pupil {
            width: 20px; height: 20px; background: #191e24; border-radius: 50%;
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        }

        /* Form Integration */
        .integrated-auth {
            width: 420px; padding: 15px;
            display: flex; flex-direction: column; justify-content: center;
            position: relative; z-index: 20;
        }

        .app-title {
            font-size: 58px; font-weight: 900; letter-spacing: -2px; margin: 0 0 10px 0;
            text-transform: uppercase; text-align: center;
            color: var(--color-primary);
            filter: drop-shadow(0 0 20px var(--color-primary));
            transition: all 0.3s;
        }
        .app-title:hover { letter-spacing: 2px; transform: scale(1.05); }
        .app-subtitle {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            color: var(--color-text-secondary);
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 40px;
        }

        .tabs {
            display: flex; gap: 8px; margin-bottom: 35px;
            padding: 8px; background: var(--color-surface);
            border-radius: 16px; border: 1px solid var(--glass-border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .tab {
            flex: 1; text-align: center; padding: 14px;
            font-size: 14px; font-weight: 800; color: var(--color-text-secondary);
            cursor: pointer; transition: 0.3s; border-radius: 12px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .tab.active {
            background: var(--btn-primary-bg); color: var(--btn-primary-text);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }
        .form-container { display: none; }
        .form-container.active-form { display: block; animation: fadeIn 0.4s forwards; }

        .input-group { margin-bottom: 22px; position: relative; }
        .input-label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; color: var(--color-primary); text-align: left; padding-left: 5px; }
        .auth-input {
            width: 100%; padding: 18px 20px; background: var(--color-surface); border: 1px solid var(--color-border);
            color: var(--color-text-primary); border-radius: 14px; font-family: inherit; font-size: 15px; transition: 0.3s; box-sizing: border-box;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
        }
        .auth-input:focus { border-color: var(--color-primary); box-shadow: 0 0 20px rgba(99,102,241,0.2); outline: none; }
        .form-footer { text-align: center; margin-top: 25px; font-size: 13px; color: var(--color-text-secondary); }
        .form-footer span { color: var(--color-primary); font-weight: 700; cursor: pointer; }
        .form-footer span:hover { text-decoration: underline; }

        .oauth-buttons { display: flex; gap: 10px; margin-top: 15px; }
        .btn-oauth { flex:1; background: var(--color-surface); color: var(--color-text-primary); border: 1px solid var(--color-border); padding: 12px; font-weight: bold; border-radius: 14px; text-align:center; transition: 0.3s; }
        .btn-oauth:hover { background: rgba(99,102,241,0.1); border-color: var(--color-primary); }

        .theme-switch {
            position: absolute; top: 35px; right: 40px; width: 68px; height: 34px; z-index: 100;
        }
        .theme-switch input { display:none; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--color-surface); transition: 0.4s; border-radius: 34px; border: 1px solid var(--glass-border); }
        .slider::before {
            position: absolute; content: ""; height: 24px; width: 24px; left: 4px; bottom: 4px; border-radius: 50%; z-index: 2; transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); background-color: transparent; box-shadow: inset -6px -4px 0 2px #d1d6dc;
        }
        [data-theme='light'] .slider::before {
            transform: translateX(34px) rotate(360deg); background-color: #FFD700; box-shadow: 0 0 10px #ff8c00, 0 0 25px #FFD700;
        }
    </style>
</head>
<body>
    <div class="bg-mesh"></div>

    <label class="theme-switch" for="theme-slider">
        <input type="checkbox" id="theme-slider">
        <div class="slider" id="btnThemeToggle"></div>
    </label>

    <div class="auth-wrapper">
        <!-- Monster Window -->
        <div class="creatures-window">
            <div class="sky-dark">
                <div class="moon"></div>
                <div class="star-field"></div>
            </div>
            <div class="sky-light">
                <div class="sun"></div>
                <div class="cloud" style="top: 50px; left: 130px;"></div>
                <div class="cloud" style="top: 120px; left: 280px; transform: scale(0.7);"></div>
            </div>
            
            <div class="creatures-container">
                <div class="creature c1">
                    <div class="eye"><div class="pupil"></div></div>
                    <div class="eye"><div class="pupil"></div></div>
                </div>
                <div class="creature c2">
                    <div class="eye"><div class="pupil"></div></div>
                    <div class="eye"><div class="pupil"></div></div>
                </div>
                <div class="creature c3">
                    <div class="eye"><div class="pupil"></div></div>
                    <div class="eye"><div class="pupil"></div></div>
                </div>
                <div class="creature c4">
                    <div class="eye"><div class="pupil"></div></div>
                    <div class="eye"><div class="pupil"></div></div>
                </div>
            </div>
        </div>

        <!-- Integrated Auth Controller -->
        <div class="integrated-auth">
            <h1 class="app-title">CHRONOS</h1>
            <p class="app-subtitle">Productivity Suite</p>
            <div class="tabs glass" style="display:flex;">
                <div class="tab active" data-target="login">Log In</div>
                <div class="tab" data-target="register">Register</div>
            </div>

            <!-- Login -->
            <div id="loginFormContainer" class="form-container active-form">
                <form id="loginForm">
                    <div class="input-group">
                        <label class="input-label">Email Address</label>
                        <input type="email" id="loginEmail" class="auth-input" placeholder="example@email.com" required>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Password</label>
                        <input type="password" id="loginPassword" class="auth-input" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary ripple-target" style="width:100%;font-size:16px;">Log In</button>
                    
                    <div style="text-align:center;margin:15px 0;font-size:12px;color:var(--color-text-secondary);">OR CONNECT WITH</div>
                    <div class="oauth-buttons">
                        <a href="backend/auth/google-oauth.php" class="btn-oauth">Google</a>
                        <a href="backend/auth/github-oauth.php" class="btn-oauth">GitHub</a>
                    </div>
                    
                    <div class="form-footer">
                        New here? <span class="tab-switch" data-target="register">Register now.</span>
                    </div>
                </form>
            </div>

            <!-- Registration -->
            <div id="registerFormContainer" class="form-container">
                <form id="registerForm">
                    <div class="input-group">
                        <label class="input-label">Username</label>
                        <input type="text" id="regUsername" class="auth-input" placeholder="hero123" required>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Email Address</label>
                        <input type="email" id="regEmail" class="auth-input" placeholder="example@email.com" required>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Create a Password</label>
                        <input type="password" id="regPassword" class="auth-input" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary ripple-target" style="width:100%;font-size:16px;">Create Account</button>
                    
                    <div class="oauth-buttons" style="margin-top:20px;">
                        <a href="backend/auth/google-oauth.php" class="btn-oauth">Google</a>
                        <a href="backend/auth/github-oauth.php" class="btn-oauth">GitHub</a>
                    </div>
                    
                    <div class="form-footer">
                        Already have an account? <span class="tab-switch" data-target="login">Log in now.</span>
                    </div>
                </form>
            </div>
        </div>
        
        <div style="width: 100%; text-align: center; margin-top: 15px; font-size: 10px; opacity: 0.4; color: var(--color-text-secondary); letter-spacing: 1.5px; text-transform: uppercase;">
            Made By Nitin Thakur
        </div>
    </div>

    <script src="assets/js/utils.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/eyes.js"></script>
    <script>
        // Tab switching
        const tabs = document.querySelectorAll('.tab, .tab-switch');
        const loginContainer = document.getElementById('loginFormContainer');
        const registerContainer = document.getElementById('registerFormContainer');
        
        tabs.forEach(t => t.addEventListener('click', () => {
            const trg = t.dataset.target;
            document.querySelectorAll('.tab').forEach(tb => tb.classList.remove('active'));
            document.querySelector(`.tab[data-target="${trg}"]`).classList.add('active');
            
            if (trg === 'login') {
                registerContainer.classList.remove('active-form');
                loginContainer.classList.add('active-form');
            } else {
                loginContainer.classList.remove('active-form');
                registerContainer.classList.add('active-form');
            }
        }));

        // Theme toggle mechanics binding directly to data-theme payload
        const checkbox = document.getElementById('theme-slider');
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if(savedTheme === 'light') checkbox.checked = true;
        document.documentElement.setAttribute('data-theme', savedTheme);

        checkbox.addEventListener('change', (e) => {
            const newTheme = e.target.checked ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Authenticating...';
            btn.disabled = true;
            try {
                const res = await API.login({
                    email: document.getElementById('loginEmail').value,
                    password: document.getElementById('loginPassword').value
                });
                if (res.success) {
                    // Update CSRF meta tag with the newly regenerated post-login token
                    if (res.data?.csrf_token) {
                        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        if (csrfMeta) csrfMeta.setAttribute('content', res.data.csrf_token);
                    }
                    window.location.href = res.data?.redirect || 'dashboard.php';
                }
            } catch(err) { 
                btn.innerHTML = originalText; 
                btn.disabled = false; 
            }
        });

        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Creating...';
            btn.disabled = true;
            try {
                const res = await API.register({
                    username: document.getElementById('regUsername').value,
                    email: document.getElementById('regEmail').value,
                    password: document.getElementById('regPassword').value
                });
                if(res.success) {
                    showToast('Registration successful! Please login.', 'success');
                    document.querySelector('.tab[data-target="login"]').click();
                }
            } catch(err) { 
                // Error handled by API.request
                btn.innerHTML = originalText; 
                btn.disabled = false; 
            }
        });
    </script>
</body>
</html>