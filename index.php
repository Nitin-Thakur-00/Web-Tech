<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Tech Lab Portfolio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=JetBrains+Mono:wght@500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #ffffff; /* Pure White Neon */
            --secondary: #404040; /* Medium Grey */
            --accent: #a3a3a3;
            --bg: #000000; /* Pure Black */
            --glass: rgba(255, 255, 255, 0.03);
            --border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top right, #1a1a1a, #000000);
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-x: hidden;
        }

        /* Animated Background Elements */
        .bg-glow {
            position: fixed;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
            top: -10vw;
            right: -10vw;
            pointer-events: none;
            z-index: -1;
        }

        header {
            text-align: center;
            padding: 80px 20px;
        }

        h1 {
            font-size: clamp(2.5rem, 8vw, 4rem);
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -2px;
            background: linear-gradient(to right, #fff, #888, #555);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.2));
        }

        p.subtitle {
            color: #737373;
            font-family: 'JetBrains Mono', monospace;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .container {
            display: flex;
            gap: 40px;
            padding: 40px;
            max-width: 1200px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .card {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--border);
            border-radius: 30px;
            padding: 40px;
            width: 400px;
            text-decoration: none;
            color: inherit;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, transparent, #ffffff, transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .card:hover {
            transform: translateY(-15px) scale(1.02);
            border-color: #ffffff;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8), 0 0 40px rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
        }

        .card:hover::before {
            transform: translateX(100%);
        }

        .card h2 {
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
        }

        .card p {
            color: #a3a3a3;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .tag {
            display: inline-block;
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            align-self: flex-start;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.3);
        }

        .btn-visit {
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .btn-visit span {
            width: 30px;
            height: 2px;
            background: #ffffff;
            transition: width 0.3s ease;
            box-shadow: 0 0 10px #ffffff;
        }

        .card:hover .btn-visit span {
            width: 50px;
        }

        footer {
            margin-top: auto;
            padding: 40px;
            color: #404040;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @media (max-width: 768px) {
            .card {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="bg-glow"></div>

    <header>
        <h1>Web Tech Portfolio</h1>
        <p class="subtitle">Experiments 9 & 10 | MySQL & PHP Migration</p>
    </header>

    <div class="container">
        <!-- Exp_9 Card -->
        <a href="Exp_9/index.php" class="card">
            <div class="tag">Exp_09</div>
            <h2>Tech Glass Calculator</h2>
            <p>A Calculator with persistent SQL history, keyboard support, and a sleek glassmorphism interface.</p>
            <div class="btn-visit">Visit Project <span></span></div>
        </a>

        <!-- Exp_10 Card -->
        <a href="Exp_10/index.php" class="card">
            <div class="tag">Exp_10</div>
            <h2>Ideathon Registration</h2>
            <p>Modern event registration portal with team management, multi-domain selection, and a live database-backed
                history table.</p>
            <div class="btn-visit">Visit Project <span></span></div>
        </a>
    </div>

    <footer>
        &copy; 2026 Developed for Web Technologies Lab
    </footer>
</body>

</html>