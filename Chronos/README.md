<div align="center">
    <h1>⚔️ Chronos Productivity Suite</h1>
    <p><i>A Brutalist, High-Performance Productivity & Collaboration Dashboard</i></p>
    
    <p>
        <img src="https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.0+"/>
        <img src="https://img.shields.io/badge/SQLite-003B57?style=for-the-badge&logo=sqlite&logoColor=white" alt="SQLite"/>
        <img src="https://img.shields.io/badge/Vanilla_JS-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="Vanilla JS"/>
        <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="Vanilla CSS"/>
    </p>
</div>

---

## 📑 Table of Contents
- [Overview](#-overview)
- [Core Features](#-core-features)
- [Tech Stack](#-tech-stack)
- [Installation & Setup](#-installation--setup)
- [Architecture Overview](#-architecture-overview)
- [Usage Guidelines](#-usage-guidelines)
- [Security Notes](#-security-notes)
- [License](#-license)

---

## 📌 Overview
**Chronos** is a bespoke, full-stack productivity suite built on raw, framework-less web technologies. Designed with a striking, cinematic **Brutalist UI** (inspired by the Sanctuary aesthetic), it merges form and function. With zero bloated frontend frameworks, Chronos runs exceptionally fast while supporting advanced capabilities like Pomodoro focusing, project tracking, real-time metrics, and encrypted collaborative functionality.

## ✨ Core Features
*   🛑 **Brutalist UX & Aesthetics**: Built-in cinematic film grain overlays, kinematic mouse-following cursors (`mix-blend-mode` effects), and stark zero-radius components layered over glassmorphic dark panes.
*   🔐 **Secure Authentication**: Includes native credential auth with rigorous rate-limiting, CSRF protection, and OAuth implementations for Google & GitHub.
*   📋 **Advanced Task Management**: Full CRUD operations for tasks. Organize via specific projects, tags, priorities, and strict deadlines.
*   ⏱️ **Focus Engine**: Professional Pomodoro implementation (`focus.php`) synced via local storage with integrated ambient YouTube audio components (`youtube/course.php`).
*   👥 **Collaborative Networking**: 'Friends' system built directly into the core. Send requests, manage collaborative projects, and monitor mutual productivity flow.
*   📊 **Analytics Dashboard**: Live clock, Pomodoro trackers, and a robust Github-style contribution heatmap driven off real task completions.
*   🌗 **Dynamic Theming**: Fluid system with localized `Dark`, `Light`, and `Brutalist` theme syncing.

## 🛠 Tech Stack
**Frontend:**
*   **HTML5 & CSS3** (Vanilla CSS with intricate Custom Properties, Grid/Flexbox architecture, Animations)
*   **Vanilla JavaScript** (ES6+, Fetch API, async/await, native DOM manipulation)

**Backend:**
*   **PHP 8.x** (Secure, highly modular API endpoints routing strict JSON responses)
*   **SQLite** (Zero-configuration, lightweight transactional database layer)
*   **Security:** Native PHP `password_hash()`, CSRF Tokenization per-session, and modular IP-based rate limiting.

## 🚀 Installation & Setup 

### Prerequisites
*   **PHP 8.0** or higher
*   **PDO SQLite Extension** enabled in your `php.ini`
*   A local development environment (e.g., XAMPP, MAMP, or native PHP CLI)

### 1. Clone & Position
Clone the repository and place it into your local development folder (e.g., `htdocs` for XAMPP):
```bash
git clone https://github.com/Nitin-Thakur-00/chronos.git
cd chronos
```

### 2. Configure Environment
Rename or create an `.env` file within `backend/config/` (or strictly rely on `backend/config/env.php`). Ensure directory write permissions exist so that the SQLite database (`chronos.sqlite`) can be automatically generated upon first load.

### 3. Start the Application
For instant local deployment without Apache/Nginx, leverage the built-in PHP development server:
```bash
php -S localhost:8000
```

### 4. Access
Open your web browser and navigate to:
```text
http://localhost:8000/index.php
```
*Note: Due to security rate limiting, failed login attempts (5 times within 15 minutes) will intentionally lock your IP.*

## 📂 Architecture Overview

```text
chronos/
├── assets/                 # Frontend static assets
│   ├── css/                # Theming logic (main.css, theme-dark.css, components.css, animations.css)
│   ├── js/                 # Dedicated modular JS scripts (api.js, utils.js, dashboard.js)
│   └── icons/
├── backend/                # Server-side Logic
│   ├── api/                # Core JSON endpoints (tasks, projects, timers, user, friends)
│   ├── auth/               # OAuth handlers and Session authentication logic
│   ├── config/             # DB schema initializations and env config
│   ├── helpers/            # Validation algorithms, Email, Session wrappers
│   └── middleware/         # CSRF validations & Rate limiters (ratelimit.php)
├── partials/               # Reusable PHP views (Sidebar module, Navbars)
└── *.php                   # Primary route entries (index, dashboard, focus, settings, etc.)
```

## 🎛 Usage Guidelines
*   **The Landing Page:** Maintained with its original rounded "Monster Window" layout, isolating the heavy brutalist elements strictly to the inner application layers.
*   **The Cursor:** Moving your mouse on the Dashboard activates the responsive trailing cursor interface.
*   **Focus Audio:** The Focus tab contains integrations capable of streaming LoFi or ambient sounds natively via YouTube APIs.

## 🛡 Security Notes
This platform utilizes extensive `session_regenerate_id()` triggers and stringent CSRF checks. If testing API endpoints manually (e.g., via Postman or `curl`), you **must** obtain a valid session cookie and attach the `X-CSRF-Token` header.

## 🤝 Contributing
Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 🙏 Acknowledgments
*   **Sanctuary UI Context**: Inspiration drawn from elite Brutalist architectures and cinematic film-grain integration on web interfaces.
*   The raw vanilla CSS and JS communities for continually pushing the bound of what is possible without heavy frameworks.

## 📜 License
Distributed under the MIT License. See `LICENSE` for more information.
