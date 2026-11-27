<?php
require_once __DIR__ . '/config.php';
$isLoggedIn = isAdminLoggedIn();
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeshCore Administravimo Pultas</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.ico">
    <link rel="apple-touch-icon" href="../favicon-32x32.png">

    <!-- Šriftai ir stiliai -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-base: #141414;
            --bg-surface: #1e1e1e;
            --bg-header: #252525;
            --bg-card: #252525;
            --bg-card-hover: #2c2c2c;
            --border-color: #383838;
            --border-light: #444444;

            --accent-cyan: #00d2ff;
            --accent-blue: #007bff;
            --accent-green: #28a745;
            --accent-orange: #fd7e14;
            --accent-amber: #ffc107;
            --accent-red: #dc3545;

            --text-main: #ffffff;
            --text-secondary: #cccccc;
            --text-muted: #888888;
            --text-dim: #666666;

            --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --font-mono: SFMono-Regular, Menlo, Monaco, Consolas, "JetBrains Mono", monospace;
            --radius-sm: 4px;
            --radius-md: 6px;
            --radius-lg: 8px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-base);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        /* Navigacijos viršutinė juosta */
        header.admin-navbar {
            height: 52px;
            background: #333333;
            border-bottom: 1px solid #222222;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 30px;
            height: 30px;
            background: #252525;
            border: 1px solid var(--accent-cyan);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 10px rgba(0, 210, 255, 0.3);
        }

        .brand-logo svg {
            width: 18px;
            height: 18px;
            fill: var(--accent-cyan);
        }

        .brand-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.01em;
        }

        .brand-badge {
            font-size: 0.68rem;
            color: var(--accent-cyan);
            background: rgba(0, 210, 255, 0.1);
            border: 1px solid var(--accent-cyan);
            padding: 2px 6px;
            border-radius: var(--radius-sm);
            font-family: var(--font-mono);
            font-weight: 600;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            background: rgba(0, 210, 255, 0.1);
            border: 1px solid var(--accent-cyan);
            color: var(--accent-cyan);
            padding: 6px 14px;
            border-radius: var(--radius-md);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn:hover {
            background: rgba(0, 210, 255, 0.25);
            color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 210, 255, 0.3);
        }

        .btn-secondary {
            background: #252525;
            border-color: var(--border-light);
            color: var(--text-secondary);
        }

        .btn-secondary:hover {
            background: #333333;
            border-color: var(--text-muted);
            color: #ffffff;
            box-shadow: none;
        }

        .btn-danger {
            background: rgba(220, 53, 69, 0.15);
            border-color: var(--accent-red);
            color: #ff6b81;
        }

        .btn-danger:hover {
            background: rgba(220, 53, 69, 0.3);
            color: #ffffff;
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.3);
        }

        .btn-success {
            background: rgba(40, 167, 69, 0.15);
            border-color: var(--accent-green);
            color: #2ed573;
        }

        .btn-success:hover {
            background: rgba(40, 167, 69, 0.3);
            color: #ffffff;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
        }

        .btn-sm {
            padding: 3px 8px;
            font-size: 0.72rem;
            border-radius: var(--radius-sm);
        }

        .btn-icon {
            width: 26px;
            height: 26px;
            min-width: 26px;
            padding: 0;
            font-size: 0.82rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            flex-shrink: 0;
        }

        /* Prisijungimo ekranas */
        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 420px;
            padding: 32px 28px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
            text-align: center;
        }

        .login-card .logo-box {
            width: 52px;
            height: 52px;
            background: #1e1e1e;
            border: 1px solid var(--accent-cyan);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 0 16px rgba(0, 210, 255, 0.3);
        }

        .login-card .logo-box svg {
            width: 28px;
            height: 28px;
            fill: var(--accent-cyan);
        }

        .login-card h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .login-card p {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            background: #1e1e1e;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 10px 12px;
            font-size: 0.85rem;
            color: #ffffff;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 10px rgba(0, 210, 255, 0.25);
            background: #222222;
        }

        .input-clearable-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .input-clearable-wrapper .form-control {
            width: 100% !important;
            padding-right: 28px !important;
        }

        .input-clear-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            color: #aaaaaa;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            transition: all 0.15s ease;
            z-index: 2;
        }

        .input-clear-btn:hover {
            color: #ffffff;
            background: rgba(239, 68, 68, 0.5);
            border-color: #ef4444;
        }

        /* Administravimo pulto pagrindinis turinys */
        .admin-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 1540px;
            width: 100%;
            margin: 0 auto;
            padding: 18px 20px;
            gap: 16px;
        }

        /* Suvestinės statistikos blokas */
        .stats-banner {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
        }

        .stat-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            position: relative;
            overflow: hidden;
        }

        .stat-box::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--accent-cyan);
        }

        .stat-box.green::before { background: var(--accent-green); }
        .stat-box.orange::before { background: var(--accent-orange); }
        .stat-box.red::before { background: var(--accent-red); }
        .stat-box.blue::before { background: var(--accent-blue); }

        .stat-box .val {
            font-size: 1.35rem;
            font-weight: 700;
            font-family: var(--font-mono);
            color: #ffffff;
            line-height: 1.2;
        }

        .stat-box .lbl {
            font-size: 0.70rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        /* Kortelių skirtukai (Tabs) */
        .tab-nav {
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 2px;
        }

        .tab-btn {
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            color: var(--text-muted);
            padding: 10px 18px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .tab-btn:hover {
            color: #ffffff;
        }

        .tab-btn.active {
            color: var(--accent-cyan);
            border-bottom-color: var(--accent-cyan);
        }

        .tab-content {
            display: none;
            flex-direction: column;
            gap: 14px;
        }

        .tab-content.active {
            display: flex;
        }

        /* Filtravimo juosta */
        .filter-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .filter-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .filter-chip {
            background: rgba(0, 210, 255, 0.12);
            border: 1px solid var(--accent-cyan);
            color: var(--accent-cyan);
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .filter-chip .close-chip {
            cursor: pointer;
            font-weight: bold;
        }

        /* Lentelės stilius */
        .table-wrapper {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow-x: auto;
            box-shadow: 0 4px 18px rgba(0,0,0,0.3);
        }

        table.admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.80rem;
            text-align: left;
        }

        table.admin-table th {
            background: #1e1e1e;
            color: var(--text-muted);
            padding: 10px 14px;
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
            text-transform: uppercase;
            font-size: 0.68rem;
            letter-spacing: 0.04em;
        }

        table.admin-table th.sortable {
            cursor: pointer;
            user-select: none;
            transition: color 0.15s ease, background 0.15s ease;
        }

        table.admin-table th.sortable:hover {
            color: var(--accent-cyan);
            background: #252525;
        }

        table.admin-table th.sortable .sort-icon {
            display: inline-block;
            margin-left: 5px;
            font-size: 0.70rem;
            color: var(--text-dim);
            transition: color 0.15s ease;
        }

        table.admin-table th.sortable.sorted-asc .sort-icon,
        table.admin-table th.sortable.sorted-desc .sort-icon {
            color: var(--accent-cyan);
            font-weight: bold;
        }

        table.admin-table th.sortable.sorted-asc,
        table.admin-table th.sortable.sorted-desc {
            color: #ffffff;
            background: rgba(0, 210, 255, 0.06);
        }

        table.admin-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #303030;
            vertical-align: middle;
            color: var(--text-secondary);
        }

        table.admin-table tbody tr:hover {
            background: #2a2a2a;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: var(--radius-sm);
            font-size: 0.68rem;
            font-weight: 700;
            font-family: var(--font-mono);
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .badge-blue { background: var(--accent-blue); color: #ffffff; }
        .badge-cyan { background: rgba(0, 210, 255, 0.15); color: var(--accent-cyan); border: 1px solid var(--accent-cyan); }
        .badge-green { background: rgba(40, 167, 69, 0.15); color: #2ed573; border: 1px solid var(--accent-green); }
        .badge-red { background: rgba(220, 53, 69, 0.15); color: #ff6b81; border: 1px solid var(--accent-red); }
        .badge-amber { background: rgba(255, 193, 7, 0.15); color: var(--accent-amber); border: 1px solid var(--accent-amber); }
        .badge-purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid #a855f7; }
        .badge-gray { background: #333333; color: var(--text-muted); border: 1px solid var(--border-light); }
        .row-node-disabled { opacity: 0.75; background: rgba(220, 53, 69, 0.04); }

        .copy-btn {
            background: transparent;
            border: none;
            color: var(--text-dim);
            cursor: pointer;
            padding: 2px 4px;
            font-size: 0.72rem;
            transition: color 0.15s;
        }

        .copy-btn:hover {
            color: var(--accent-cyan);
        }

        /* Puslapiavimas */
        .pagination-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            border-top: 1px solid var(--border-color);
            background: #222222;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* Modalas */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(6px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-card {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 650px;
            max-height: 90vh;
            max-height: 90dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 14px 45px rgba(0, 0, 0, 0.8);
            margin: auto;
        }

        .modal-card.modal-lg {
            max-width: 850px;
        }

        .modal-card form {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }

        .modal-header {
            padding: 14px 20px;
            background: #1e1e1e;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .modal-header h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #ffffff;
        }

        .modal-close {
            background: transparent;
            border: none;
            font-size: 1.4rem;
            color: var(--text-muted);
            cursor: pointer;
            line-height: 1;
        }

        .modal-close:hover {
            color: #ffffff;
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.25) transparent;
        }

        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }

        .modal-footer {
            padding: 12px 20px;
            background: #1e1e1e;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-shrink: 0;
        }

        /* JSON Pre peržiūra */
        pre.json-viewer {
            background: #141414;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 12px;
            color: #00d2ff;
            font-family: var(--font-mono);
            font-size: 0.76rem;
            max-height: 380px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        /* Notifikacijos pranešimas */
        .toast-msg {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #252525;
            border: 1px solid var(--accent-cyan);
            color: #ffffff;
            padding: 10px 18px;
            border-radius: var(--radius-md);
            font-size: 0.82rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.6);
            z-index: 3000;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .toast-msg.success { border-color: var(--accent-green); }
        .toast-msg.error { border-color: var(--accent-red); }

        /* Mobiliems įrenginiams */
        @media (max-width: 768px) {
            .admin-container { padding: 10px; }
            .tab-btn { padding: 8px 12px; font-size: 0.78rem; }
            .stats-banner { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

    <!-- Viršutinė antraštė -->
    <header class="admin-navbar">
        <div class="brand-container" style="cursor: pointer;" onclick="switchTab('nodes')" title="Eiti į pagrindinį puslapį">
            <div class="brand-logo">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                </svg>
            </div>
            <span class="brand-title">MeshCore Admin</span>
            <span class="brand-badge">Eterio Pultas</span>
        </div>

        <div class="nav-links">
            <a href="../" target="_blank" class="btn btn-secondary btn-sm" title="Atidaryti viešąjį žemėlapį">
                <span>🗺️ Žemėlapis</span>
            </a>
            <?php if ($isLoggedIn): ?>
                <button class="btn btn-sm" onclick="refreshCurrentTab()" title="Atnaujinti duomenis">
                    <span>🔄 Atnaujinti</span>
                </button>
                <button class="btn btn-danger btn-sm" onclick="handleLogout()">
                    <span>Atsijungti</span>
                </button>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!$isLoggedIn): ?>
        <!-- 1. Prisijungimo forma -->
        <div class="login-wrapper">
            <div class="login-card">
                <div class="logo-box">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                    </svg>
                </div>
                <h2>Administravimo Pultas</h2>
                <p>Prisijunkite norėdami valdyti kartotuvus, peržiūrėti telemetriją ir analizuoti RAW paketus.</p>
                
                <form id="loginForm" onsubmit="handleLogin(event)">
                    <div class="form-group">
                        <label for="adminPassword">Administratoriaus Slaptažodis</label>
                        <input type="password" id="adminPassword" class="form-control" placeholder="Įveskite slaptažodį..." required autofocus>
                    </div>
                    <div id="loginError" style="color: var(--accent-red); font-size: 0.8rem; margin-bottom: 12px; display: none;"></div>
                    <button type="submit" class="btn" style="width: 100%; justify-content: center; padding: 10px;">
                        <span>Prisijungti</span>
                    </button>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- 2. Pagrindinė administravimo erdvė -->
        <div class="admin-container">
            <!-- Suvestinės statistika -->
            <div class="stats-banner" id="statsBanner">
                <div class="stat-box">
                    <div class="val" id="statNodesCount">-</div>
                    <div class="lbl">Visi Mazgai</div>
                </div>
                <div class="stat-box green">
                    <div class="val" id="statMonitoredCount">-</div>
                    <div class="lbl">Stebimi (Update=1)</div>
                </div>
                <div class="stat-box blue">
                    <div class="val" id="statRawTotal">-</div>
                    <div class="lbl">RAW Paketai</div>
                </div>
                <div class="stat-box orange">
                    <div class="val" id="statQueuePending">-</div>
                    <div class="lbl">Eilėje Laukia</div>
                </div>
                <div class="stat-box red">
                    <div class="val" id="statErrorsCount">-</div>
                    <div class="lbl">Klaidos Eteryje</div>
                </div>
                <div class="stat-box">
                    <div class="val" id="statTelemetryCount">-</div>
                    <div class="lbl">Telemetrijos Įrašai</div>
                </div>
                <div class="stat-box cyan" style="cursor: pointer;" onclick="switchTab('bots')" title="Eiti į botų valdymą">
                    <div class="val" id="statBotsCount">-</div>
                    <div class="lbl">ESP32 Botai</div>
                </div>
            </div>

            <!-- Skirtukų navigacija -->
            <div class="tab-nav">
                <button class="tab-btn active" id="tabBtn-nodes" onclick="switchTab('nodes')">
                    <span>🛰️ Mazgai</span>
                </button>
                <button class="tab-btn" id="tabBtn-bots" onclick="switchTab('bots')">
                    <span>🤖 Botai</span>
                </button>
                <button class="tab-btn" id="tabBtn-telemetry" onclick="switchTab('telemetry')">
                    <span>📊 Telemetrijos Įrašai</span>
                </button>
                <button class="tab-btn" id="tabBtn-raw" onclick="switchTab('raw')">
                    <span>📦 RAW Paketai & Debugger</span>
                </button>
                <button class="tab-btn" id="tabBtn-apilogs" onclick="switchTab('apilogs')">
                    <span>🌐 API Užklausos</span>
                </button>
                <button class="tab-btn" id="tabBtn-tools" onclick="switchTab('tools')">
                    <span>⚡ Eilė & Įrankiai</span>
                </button>
                <button class="tab-btn" id="tabBtn-settings" onclick="switchTab('settings')">
                    <span>⚙️ Nustatymai</span>
                </button>
            </div>

            <!-- SKIRTUKAS 1: MAZGAI & NUSTATYMAI -->
            <div class="tab-content active" id="tab-nodes">
                <div class="filter-card">
                    <div class="filter-group">
                        <div class="input-clearable-wrapper" style="width: 240px;">
                            <input type="text" id="nodesSearch" class="form-control" placeholder="Ieškoti pagal vardą, ID ar raktą..." oninput="updateAdminClearBtn('nodesSearch'); onNodesSearchInput();" onkeydown="if(event.key==='Enter') onNodesSearchEnter()">
                            <button type="button" class="input-clear-btn" id="nodesSearchClearBtn" onclick="clearNodesSearch()" title="Išvalyti paiešką">✕</button>
                        </div>
                        
                        <select id="nodesBotFilter" class="form-control" style="width: 170px;" onchange="onNodesFilterChange()">
                            <option value="">Visi botai</option>
                        </select>

                        <select id="nodesStatusFilter" class="form-control" style="width: 160px;" onchange="onNodesFilterChange()">
                            <option value="">Visos būsenos</option>
                            <option value="active">Tik aktyvūs</option>
                            <option value="disabled">Tik išjungti (Disabled)</option>
                        </select>

                        <select id="nodesTypeFilter" class="form-control" style="width: 160px;" onchange="onNodesFilterChange()">
                            <option value="">Visi tipai</option>
                            <option value="repeater">Tik Kartotuvai</option>
                            <option value="chat">Tik Pokalbiai</option>
                            <option value="room">Tik Kambariai</option>
                        </select>

                        <select id="nodesUpdateFilter" class="form-control" style="width: 180px;" onchange="onNodesFilterChange()">
                            <option value="">Visi (Stebimi ir kiti)</option>
                            <option value="1">Tik stebimi (update=1)</option>
                            <option value="0">Tik nestebimi (update=0)</option>
                        </select>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="resetAllRequestedDates()" title="Išvalyti visų (arba pasirinkto boto) mazgų requested_date datą į NULL (atstatyti eilę į pradžią)">
                            <span>⚡ Resetinti eilę</span>
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="resetAllFailCounts()" title="Nuresetinti visų (arba pasirinkto boto) mazgų nesėkmingų bandymų (fail) skaičių į 0">
                            <span>🧹 Resetinti fail (0)</span>
                        </button>
                        <span id="nodesCountBadge" class="badge badge-gray">0 mazgų</span>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="admin-table" id="nodesTable">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="handleSortTable('nodes', 'name')" data-sort="name">Mazgas <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('nodes', 'status')" data-sort="status">Būsena <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('nodes', 'type')" data-sort="type">Tipas <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('nodes', 'bot_id')" data-sort="bot_id">Šaltinis (Botas) <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('nodes', 'update')" data-sort="update">Stebimas (Update) <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('nodes', 'password')" data-sort="password">Slaptažodis <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('nodes', 'coords')" data-sort="coords">Koordinatės <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('nodes', 'last_seen_at')" data-sort="last_seen_at">Paskutinį kartą matytas (LT laiku) <span class="sort-icon"></span></th>
                                <th style="text-align: right;">Veiksmai</th>
                            </tr>
                        </thead>
                        <tbody id="nodesTableBody">
                            <tr><td colspan="9" style="text-align: center; padding: 24px;">Kraunami mazgai...</td></tr>
                        </tbody>
                    </table>

                    <div class="pagination-row">
                        <span id="nodesPageInfo">Puslapis 1</span>
                        <div style="display: flex; gap: 6px;">
                            <button class="btn btn-secondary btn-sm" id="btnPrevNodes" onclick="changeNodesPage(-1)">← Ankstesnis</button>
                            <button class="btn btn-secondary btn-sm" id="btnNextNodes" onclick="changeNodesPage(1)">Kitas →</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SKIRTUKAS: BOTAI (ESP32 VARTAI) -->
            <div class="tab-content" id="tab-bots">
                <div class="filter-card">
                    <div class="filter-group">
                        <div class="input-clearable-wrapper" style="width: 260px;">
                            <input type="text" id="botsSearch" class="form-control" placeholder="Ieškoti pagal ID, pavadinimą ar raktą..." oninput="updateAdminClearBtn('botsSearch'); onBotsSearchInput();">
                            <button type="button" class="input-clear-btn" id="botsSearchClearBtn" onclick="clearBotsSearch()" title="Išvalyti paiešką">✕</button>
                        </div>
                        <select id="botsStatusFilter" class="form-control" style="width: 175px;" onchange="renderBotsTable()">
                            <option value="">Visos būsenos</option>
                            <option value="enabled">Tik aktyvūs (Enabled)</option>
                            <option value="disabled">Tik išjungti (Disabled)</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span id="botsCountBadge" class="badge badge-gray">0 botų</span>
                        <button class="btn btn-sm" onclick="openCreateBotModal()">
                            <span>➕ Naujas Botas</span>
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="admin-table" id="botsTable">
                        <thead>
                            <tr>
                                <th>Boto ID</th>
                                <th>Pavadinimas (Name)</th>
                                <th>Viešasis Raktas (Public Key)</th>
                                <th>Būsena</th>
                                <th>Apklausos Grafikas</th>
                                <th>GPS Koordinatės</th>
                                <th>Priskirti Mazgai</th>
                                <th>Paskutinį kartą matytas (LT)</th>
                                <th style="text-align: right;">Veiksmai</th>
                            </tr>
                        </thead>
                        <tbody id="botsTableBody">
                            <tr><td colspan="9" style="text-align: center; padding: 24px;">Kraunami botai...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SKIRTUKAS 2: TELEMETRIJOS ĮRAŠAI -->
            <div class="tab-content" id="tab-telemetry">
                <div class="filter-card">
                    <div class="filter-group">
                        <div id="telemetryNodeFilterTag" style="display: none;" class="filter-chip">
                            <span>Mazgas: <b id="telemetryActiveNodeText"></b></span>
                            <span class="close-chip" onclick="clearTelemetryNodeFilter()" title="Išvalyti mazgo filtrą">&times;</span>
                        </div>

                        <div class="input-clearable-wrapper" style="width: 180px;">
                            <input type="text" id="telemetryNodeInput" class="form-control" placeholder="Mazgo ID (pvz. b9dd)..." oninput="updateAdminClearBtn('telemetryNodeInput')" onchange="updateAdminClearBtn('telemetryNodeInput'); onTelemetryFilterChange();" onkeydown="if(event.key==='Enter') onTelemetryFilterChange()">
                            <button type="button" class="input-clear-btn" id="telemetryNodeInputClearBtn" onclick="clearTelemetryNodeFilter()" title="Išvalyti mazgo filtrą">✕</button>
                        </div>
                        
                        <select id="telemetryTypeFilter" class="form-control" style="width: 170px;" onchange="onTelemetryFilterChange()">
                            <option value="">Visi (TELEMETRY & STATUS)</option>
                            <option value="STATUS">STATUS</option>
                            <option value="TELEMETRY">TELEMETRY</option>
                        </select>

                        <button class="btn btn-secondary btn-sm" onclick="onTelemetryFilterChange()">Filtruoti</button>
                    </div>

                    <div>
                        <span id="telemetryCountBadge" class="badge badge-gray">0 įrašų</span>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="admin-table" id="telemetryTable">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="handleSortTable('telemetry', 'id')" data-sort="id">ID <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('telemetry', 'node_id')" data-sort="node_id">Mazgas / Pavadinimas <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('telemetry', 'type')" data-sort="type">Tipas <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('telemetry', 'battery_v')" data-sort="battery_v">Baterija <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('telemetry', 'temperature_c')" data-sort="temperature_c">Temperatūra <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('telemetry', 'rssi')" data-sort="rssi">Signalo Kokybė <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('telemetry', 'uptime')" data-sort="uptime">Atnaujinta Prieš <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('telemetry', 'packets')" data-sort="packets">Paketai RX/TX <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('telemetry', 'recorded_at')" data-sort="recorded_at">Užregistruota (LT laiku) <span class="sort-icon"></span></th>
                                <th style="text-align: right;">Veiksmai</th>
                            </tr>
                        </thead>
                        <tbody id="telemetryTableBody">
                            <tr><td colspan="10" style="text-align: center; padding: 24px;">Kraunami telemetrijos įrašai...</td></tr>
                        </tbody>
                    </table>
                    
                    <div class="pagination-row">
                        <span id="telemetryPageInfo">Puslapis 1</span>
                        <div style="display: flex; gap: 6px;">
                            <button class="btn btn-secondary btn-sm" id="btnPrevTel" onclick="changeTelemetryPage(-1)">← Ankstesnis</button>
                            <button class="btn btn-secondary btn-sm" id="btnNextTel" onclick="changeTelemetryPage(1)">Kitas →</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SKIRTUKAS 3: RAW PAKETAI & DEBUGGER -->
            <div class="tab-content" id="tab-raw">
                <div class="filter-card">
                    <div class="filter-group">
                        <div id="rawNodeFilterTag" style="display: none;" class="filter-chip">
                            <span>Mazgas: <b id="rawActiveNodeText"></b></span>
                            <span class="close-chip" onclick="clearRawNodeFilter()" title="Išvalyti mazgo filtrą">&times;</span>
                        </div>

                        <div class="input-clearable-wrapper" style="width: 220px;">
                            <input type="text" id="rawSearchInput" class="form-control" placeholder="Ieškoti hex, varde ar klaidoje..." oninput="updateAdminClearBtn('rawSearchInput'); onRawSearchInput();" onkeydown="if(event.key==='Enter') onRawSearchEnter()">
                            <button type="button" class="input-clear-btn" id="rawSearchInputClearBtn" onclick="clearRawNodeFilter()" title="Išvalyti paiešką">✕</button>
                        </div>
                        
                        <select id="rawStatusFilter" class="form-control" style="width: 170px;" onchange="onRawFilterChange()">
                            <option value="">Visos būsenos</option>
                            <option value="errors">⚠️ Tik KLAIDOS</option>
                            <option value="pending">⏳ Eilėje (neapdoroti)</option>
                            <option value="processed">✅ Apdoroti</option>
                        </select>

                        <select id="rawDirectionFilter" class="form-control" style="width: 140px;" onchange="onRawFilterChange()">
                            <option value="">Visos kryptys</option>
                            <option value="RX">Tik RX (gauti)</option>
                            <option value="TX">Tik TX (išsiųsti)</option>
                        </select>

                        <select id="rawTypeFilter" class="form-control" style="width: 175px;" onchange="onRawFilterChange()">
                            <option value="">Visi paketų tipai</option>
                            <option value="4">📢 ADVERT (Skelbimas)</option>
                            <option value="7">🔐 LOGIN (ANON_REQ)</option>
                            <option value="0">📡 REQ (Užklausa)</option>
                            <option value="1">📥 RESPONSE (Atsakas)</option>
                            <option value="8">🗺️ PATH (Kelias / Atsakas)</option>
                            <option value="2">💬 TXT_MSG (Žinutė)</option>
                            <option value="5">👥 GRP_TXT (Kanalas)</option>
                            <option value="3">✓ ACK (Patvirtinimas)</option>
                            <option value="9">🧭 TRACE (Trasavimas)</option>
                        </select>
                    </div>

                    <div>
                        <span id="rawCountBadge" class="badge badge-gray">0 paketų</span>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="admin-table" id="rawTable">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="handleSortTable('raw', 'id')" data-sort="id">ID <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('raw', 'bot_id')" data-sort="bot_id">Botas <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('raw', 'direction')" data-sort="direction">Kryptis <span class="sort-icon"></span></th>
                                <th>Tipas</th>
                                <th class="sortable" onclick="handleSortTable('raw', 'sender')" data-sort="sender">Siuntėjas <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('raw', 'target')" data-sort="target">Taikinys <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('raw', 'length')" data-sort="length">Ilgis / Radijas <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('raw', 'status')" data-sort="status">Būsena <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('raw', 'error')" data-sort="error">Klaida (jei yra) <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('raw', 'created_at')" data-sort="created_at">Gauta Data (LT laiku) <span class="sort-icon"></span></th>
                                <th style="text-align: right;">Veiksmai</th>
                            </tr>
                        </thead>
                        <tbody id="rawTableBody">
                            <tr><td colspan="11" style="text-align: center; padding: 24px;">Kraunami RAW paketai...</td></tr>
                        </tbody>
                    </table>

                    <div class="pagination-row">
                        <span id="rawPageInfo">Puslapis 1</span>
                        <div style="display: flex; gap: 6px;">
                            <button class="btn btn-secondary btn-sm" id="btnPrevRaw" onclick="changeRawPage(-1)">← Ankstesnis</button>
                            <button class="btn btn-secondary btn-sm" id="btnNextRaw" onclick="changeRawPage(1)">Kitas →</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SKIRTUKAS: API UŽKLAUSŲ ŽURNALAS (API LOGS) -->
            <div class="tab-content" id="tab-apilogs">
                <!-- Statistikos suvestinė (paspaudus greitai nufiltruoja) -->
                <div class="stats-banner" style="margin-bottom: 14px;">
                    <div class="stat-box" style="cursor: pointer;" onclick="quickFilterApiLogs('all')" title="Rodyti visas API užklausas">
                        <div class="val" id="apiLogStatTotal">-</div>
                        <div class="lbl">Viso Užklausų</div>
                    </div>
                    <div class="stat-box cyan" style="cursor: pointer;" onclick="toggleTopIpsPanel()" title="Peržiūrėti aktyviausius IP adresus">
                        <div class="val" id="apiLogStatUniqueIps">-</div>
                        <div class="lbl">🌐 Unikalūs IP (Top)</div>
                    </div>
                    <div class="stat-box green" style="cursor: pointer;" onclick="quickFilterApiLogs('ESP32')" title="Filtruoti tik ESP32 botų užklausas">
                        <div class="val" id="apiLogStatEsp32">-</div>
                        <div class="lbl">🤖 ESP32 Botai</div>
                    </div>
                    <div class="stat-box blue" style="cursor: pointer;" onclick="quickFilterApiLogs('Browser')" title="Filtruoti tik naršyklių užklausas">
                        <div class="val" id="apiLogStatBrowser">-</div>
                        <div class="lbl">🖥️ Naršyklės</div>
                    </div>
                    <div class="stat-box orange" style="cursor: pointer;" onclick="quickFilterApiLogs('suspicious')" title="Filtruoti botus, skriptus, crawlerius ir klientus be User-Agent">
                        <div class="val" id="apiLogStatBotsScripts">-</div>
                        <div class="lbl">🕵️ Svetimi Botai / Skriptai</div>
                    </div>
                    <div class="stat-box red" style="cursor: pointer;" onclick="quickFilterApiLogs('errors')" title="Filtruoti tik klaidas (HTTP 4xx / 5xx)">
                        <div class="val" id="apiLogStatErrors">-</div>
                        <div class="lbl">⚠️ Klaidos (4xx/5xx)</div>
                    </div>
                </div>

                <!-- Aktyviausi IP adresai (Top IP greita juosta) -->
                <div id="apiLogsTopIpsPanel" class="filter-card" style="display: block; margin-bottom: 14px; padding: 12px 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                        <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-secondary);">
                            🔥 Aktyviausi IP adresai (paspauskite ant IP, kad nufiltruotumėte visas jo užklausas):
                        </span>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleTopIpsPanel()" style="font-size: 0.7rem; padding: 2px 8px;">Slėpti / Rodyti</button>
                    </div>
                    <div id="apiLogsTopIpsList" style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <span style="font-size: 0.78rem; color: var(--text-dim);">Kraunama...</span>
                    </div>
                </div>

                <!-- Paieškos ir filtravimo juosta -->
                <div class="filter-card">
                    <div class="filter-group">
                        <div class="input-clearable-wrapper" style="width: 240px;">
                            <input type="text" id="apiLogsSearchInput" class="form-control" placeholder="Ieškoti IP, path, User-Agent, JSON..." oninput="updateAdminClearBtn('apiLogsSearchInput'); onApiLogsSearchInput();" onkeydown="if(event.key==='Enter') onApiLogsFilterChange()">
                            <button type="button" class="input-clear-btn" id="apiLogsSearchInputClearBtn" onclick="clearApiLogsSearch()" title="Išvalyti paiešką">✕</button>
                        </div>

                        <div class="input-clearable-wrapper" style="width: 155px;">
                            <input type="text" id="apiLogsIpInput" class="form-control" placeholder="IP filtras..." oninput="updateAdminClearBtn('apiLogsIpInput'); onApiLogsSearchInput();" onkeydown="if(event.key==='Enter') onApiLogsFilterChange()">
                            <button type="button" class="input-clear-btn" id="apiLogsIpInputClearBtn" onclick="clearApiLogsIpFilter()" title="Išvalyti IP filtrą">✕</button>
                        </div>

                        <select id="apiLogsEndpointFilter" class="form-control" style="width: 165px;" onchange="onApiLogsFilterChange()">
                            <option value="all">Visi path (Endpointai)</option>
                            <option value="/api/repeater">/api/repeater</option>
                            <option value="/api/raw">/api/raw</option>
                            <option value="/api/nodes">/api/nodes</option>
                            <option value="/api/node/list">/api/node/list</option>
                            <option value="/api/node/{key}">/api/node/{key}</option>
                            <option value="/api/repyters">/api/repyters</option>
                            <option value="/api/telemetry">/api/telemetry</option>
                            <option value="/api/bots">/api/bots</option>
                            <option value="/api/status">/api/status</option>
                        </select>

                        <select id="apiLogsClientTypeFilter" class="form-control" style="width: 195px;" onchange="onApiLogsFilterChange()">
                            <option value="all">Visi klientai</option>
                            <option value="non_esp32">🔍 Visi išskyrus ESP32</option>
                            <option value="suspicious">🕵️ Įtartini (Botai / Skriptai)</option>
                            <option value="ESP32">🤖 ESP32 Botas</option>
                            <option value="Browser">🖥️ Naršyklė (Browser)</option>
                            <option value="Crawler/Bot">🕷️ Crawler / Botas</option>
                            <option value="CLI/Script">💻 CLI / Skriptas (curl/python)</option>
                            <option value="Empty-UA">❓ Tuščias User-Agent</option>
                            <option value="Unknown">❓ Nežinomas klientas</option>
                        </select>

                        <select id="apiLogsBotFilter" class="form-control" style="width: 140px;" onchange="onApiLogsFilterChange()">
                            <option value="all">Visi Bot ID</option>
                            <option value="__NONE__">Be Bot ID</option>
                            <option value="KLP">KLP</option>
                            <option value="KUN">KUN</option>
                        </select>

                        <select id="apiLogsMethodFilter" class="form-control" style="width: 115px;" onchange="onApiLogsFilterChange()">
                            <option value="all">GET & POST</option>
                            <option value="GET">Tik GET</option>
                            <option value="POST">Tik POST</option>
                        </select>

                        <select id="apiLogsStatusFilter" class="form-control" style="width: 130px;" onchange="onApiLogsFilterChange()">
                            <option value="all">Visi statusai</option>
                            <option value="2xx">✅ 200 OK (2xx)</option>
                            <option value="err">⚠️ Klaidos (4xx/5xx)</option>
                            <option value="4xx">🚫 4xx (400/404)</option>
                            <option value="5xx">💥 5xx (500)</option>
                        </select>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="resetApiLogsFilters()" title="Atstatyti visus filtrus">
                            <span>🔄 Visi</span>
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearApiLogs('older_24h')" title="Ištrinti senesnius nei 24 val. įrašus">
                            <span>🧹 Valyti &gt;24h</span>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="clearApiLogs('all')" title="Išvalyti visą API užklausų žurnalą">
                            <span>🗑️ Išvalyti viską</span>
                        </button>
                        <span id="apiLogsCountBadge" class="badge badge-gray">0 užklausų</span>
                    </div>
                </div>

                <!-- API Užklausų lentelė -->
                <div class="table-wrapper">
                    <table class="admin-table" id="apiLogsTable">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="handleSortTable('apilogs', 'id')" data-sort="id">ID <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('apilogs', 'created_at')" data-sort="created_at">Laikas (LT) <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('apilogs', 'ip_address')" data-sort="ip_address">IP Adresas <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('apilogs', 'client_type')" data-sort="client_type">Klientas / User-Agent <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('apilogs', 'endpoint')" data-sort="endpoint">Metodas & Path (URL) <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('apilogs', 'status_code')" data-sort="status_code">Statusas / Trukmė <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('apilogs', 'response_size')" data-sort="response_size">Grąžintas Atsakymas (Preview) <span class="sort-icon"></span></th>
                                <th style="text-align: right;">Veiksmai</th>
                            </tr>
                        </thead>
                        <tbody id="apiLogsTableBody">
                            <tr><td colspan="8" style="text-align: center; padding: 24px;">Kraunamos API užklausos...</td></tr>
                        </tbody>
                    </table>

                    <div class="pagination-row">
                        <span id="apiLogsPageInfo">Puslapis 1</span>
                        <div style="display: flex; gap: 6px;">
                            <button class="btn btn-secondary btn-sm" id="btnPrevApiLogs" onclick="changeApiLogsPage(-1)">← Ankstesnis</button>
                            <button class="btn btn-secondary btn-sm" id="btnNextApiLogs" onclick="changeApiLogsPage(1)">Kitas →</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SKIRTUKAS 4: EILĖ & ĮRANKIAI -->
            <div class="tab-content" id="tab-tools">
                <!-- 1. KARTOTUVŲ TELEMETRIJOS UŽKLAUSŲ EILĖ (/api/repeater) -->
                <div class="filter-card" style="display: block; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <h4 style="color: #ffffff; font-size: 1.1rem; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                                <span>📋 Kartotuvų Telemetrijos Užklausų Eilė</span>
                                <span id="queueCountBadge" class="badge badge-cyan">Kraunama...</span>
                            </h4>
                            <p style="color: var(--text-muted); font-size: 0.84rem; max-width: 820px;">
                                Seka, kuria ESP32 botas rotuoja ir užklausia kartotuvus per <code>/api/repeater</code>. Pirmenybę turi mazgai su išvalyta (NULL) <code>requested_date</code>, po to – seniausiai užklausti.
                            </p>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a id="btnQueueRepeaterLink" href="../api/repeater?bot_id=KLP" target="_blank" class="btn btn-secondary btn-sm" style="text-decoration: none;" title="Pasižiūrėti sekantį kartotuvą JSON formatu">
                                <span>🎯 /api/repeater</span>
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="resetAllRequestedDates()" title="Išvalyti visų mazgų requested_date datą į NULL">
                                <span>⚡ Nuresetinti visų eilę</span>
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="resetAllFailCounts()" title="Nuresetinti visų mazgų nesėkmingų bandymų (fail) skaičių į 0">
                                <span>🧹 Nuresetinti visų fail (0)</span>
                            </button>
                            <button type="button" class="btn btn-sm" onclick="loadNodeQueue(true)">
                                <span>🔄 Atnaujinti</span>
                            </button>
                        </div>
                    </div>

                    <!-- Eilės statistikos suvestinė -->
                    <div class="stats-banner" style="margin-top: 14px; margin-bottom: 14px;">
                        <div class="stat-box">
                            <div class="val" id="qStatTotal">-</div>
                            <div class="lbl">Viso Eilėje</div>
                        </div>
                        <div class="stat-box green">
                            <div class="val" id="qStatPending">-</div>
                            <div class="lbl">🚀 Prioritetas (NULL)</div>
                        </div>
                        <div class="stat-box cyan">
                            <div class="val" id="qStatInProgress">-</div>
                            <div class="lbl">⏳ Laukiama eteryje</div>
                        </div>
                        <div class="stat-box blue">
                            <div class="val" id="qStatSuccess">-</div>
                            <div class="lbl">✅ Telemetrija Gauta</div>
                        </div>
                        <div class="stat-box purple">
                            <div class="val" id="qStatRateLimited">-</div>
                            <div class="lbl">⏳ Atnaujinta neseniai (Pauzė)</div>
                        </div>
                        <div class="stat-box orange">
                            <div class="val" id="qStatTimeout">-</div>
                            <div class="lbl">❌ Neatsakė / Timeout</div>
                        </div>
                        <div class="stat-box red">
                            <div class="val" id="qStatBlocked">-</div>
                            <div class="lbl">⛔ Sustabdyti (Fail limitas)</div>
                        </div>
                    </div>

                    <!-- Filtravimo valdymo juosta -->
                    <div class="filter-group" style="flex-wrap: wrap; gap: 10px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                        <div class="input-clearable-wrapper" style="width: 240px;">
                            <input type="text" id="queueSearchInput" class="form-control" placeholder="Ieškoti mazgo ar ID..." oninput="updateAdminClearBtn('queueSearchInput'); onQueueFilterChange();" onkeydown="if(event.key==='Enter'){clearTimeout(queueFilterTimeout);loadNodeQueue(false);}">
                            <button type="button" class="input-clear-btn" id="queueSearchInputClearBtn" onclick="clearQueueFilter()" title="Išvalyti paiešką">✕</button>
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px;">
                            <label style="font-size: 0.78rem; color: var(--text-muted);">Šaltinis (Botas):</label>
                            <select id="queueBotFilter" class="form-control" style="width: 160px;" onchange="onQueueFilterChange()">
                                <option value="">Visi šaltiniai (botai)</option>
                            </select>
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px;">
                            <label style="font-size: 0.78rem; color: var(--text-muted);">Apimtis:</label>
                            <select id="queueMonitoredFilter" class="form-control" style="width: 190px;" onchange="onQueueFilterChange()">
                                <option value="1">Tik stebimi (update=1)</option>
                                <option value="0">Visi kartotuvai</option>
                            </select>
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px;">
                            <label style="font-size: 0.78rem; color: var(--text-muted);">Rezultatas:</label>
                            <select id="queueStatusFilter" class="form-control" style="width: 210px;" onchange="onQueueStatusFilterChange()">
                                <option value="">Visi rezultatai</option>
                                <option value="never">🚀 Prioritetas (NULL)</option>
                                <option value="success">✅ Gauta telemetrija</option>
                                <option value="rate_limited">⏳ Atnaujinta neseniai (Pauzė)</option>
                                <option value="in_progress">⏳ Laukiama...</option>
                                <option value="timeout">❌ Neatsakė / Timeout</option>
                                <option value="retry_cooldown">⏳ Timeout (Pauzė)</option>
                                <option value="blocked">⛔ Sustabdyti (Fail limitas)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- LENTELĖ SU EILE -->
                <div class="table-wrapper" style="margin-bottom: 24px;">
                    <table class="admin-table" id="queueTable">
                        <thead>
                            <tr>
                                <th class="sortable" style="width: 50px; text-align: center;" onclick="handleSortTable('queue', 'index')" data-sort="index"># <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('queue', 'name')" data-sort="name">Mazgas <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('queue', 'bot_id')" data-sort="bot_id">Šaltinis (Botas) <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('queue', 'requested_date')" data-sort="requested_date">Paskutinė Užklausa (LT laiku) <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('queue', 'status')" data-sort="status">Paskutinio Bandymo Rezultatas <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="handleSortTable('queue', 'telemetry')" data-sort="telemetry">Paskutiniai Rodmenys & Telemetrija (LT laiku) <span class="sort-icon"></span></th>
                                <th style="text-align: right;">Veiksmai</th>
                            </tr>
                        </thead>
                        <tbody id="nodeQueueTableBody">
                            <tr><td colspan="7" style="text-align: center; padding: 24px;">Kraunama kartotuvų užklausų eilė...</td></tr>
                        </tbody>
                    </table>

                    <div class="pagination-row">
                        <span id="queuePageInfo">Puslapis 1</span>
                        <div style="display: flex; gap: 6px;">
                            <button class="btn btn-secondary btn-sm" id="btnPrevQueue" onclick="changeQueuePage(-1)">← Ankstesnis</button>
                            <button class="btn btn-secondary btn-sm" id="btnNextQueue" onclick="changeQueuePage(1)">Kitas →</button>
                        </div>
                    </div>
                </div>

                <!-- 2. RAW PAKETŲ EILĖS APDOROJIMAS & WORKERIS -->
                <div class="filter-card">
                    <div>
                        <h4 style="color: #ffffff; font-size: 1rem; margin-bottom: 4px;">⚡ RAW Paketų Eilės Apdorojimas Realiu Laiku</h4>
                        <p style="color: var(--text-muted); font-size: 0.8rem;">Rankinis workerio paleidimas: apdoroja neapdorotus RAW paketus tiesiai per naršyklę.</p>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button class="btn" onclick="runQueueBatch(20)">Paleisti 20 paketų</button>
                        <button class="btn btn-secondary" onclick="runQueueBatch(50)">Paleisti 50 paketų</button>
                    </div>
                </div>

                <div class="table-wrapper" style="padding: 16px; margin-bottom: 20px;">
                    <div style="font-size: 0.85rem; font-weight: 600; color: #ffffff; margin-bottom: 8px;">Apdorojimo rezultatas:</div>
                    <pre id="queueOutput" class="json-viewer" style="height: 140px;">Paspauskite mygtuką aukščiau, kad paleistumėte eilės apdorojimą...</pre>
                </div>

                <!-- 3. API NUORODOS -->
                <div class="filter-card" style="display: block;">
                    <h4 style="color: #ffffff; font-size: 1rem; margin-bottom: 8px;">🔗 Sistemos API Nuorodos & ESP32 Integracija</h4>
                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.82rem;">
                        <div>• <b>/api/nodes</b>: <a href="../api/nodes" target="_blank" style="color: var(--accent-cyan);">Atidaryti viešą mazgų sąrašą (JSON)</a></div>
                        <div>• <b>/api/repeater</b>: <a href="../api/repeater" target="_blank" style="color: var(--accent-cyan);">Atidaryti 1 sekantį kartotuvą eilėje (JSON)</a> (Grąžina viršutinį ir pažymi requested_date)</div>
                        <div>• <b>/api/repyters</b>: <a href="../api/repyters" target="_blank" style="color: var(--accent-cyan);">Atidaryti ESP32 stebimų kartotuvų sąrašą (JSON)</a> (Grąžina visus, kur update=true)</div>
                        <div>• <b>/process_queue.php</b>: <a href="../process_queue.php?limit=20" target="_blank" style="color: var(--accent-cyan);">Atidaryti HTTP Cron Workerį</a> (Galima įsidėti į cPanel Cron kas minutę)</div>
                    </div>
                </div>
            </div>

            <!-- SKIRTUKAS 5: NUSTATYMAI -->
            <div class="tab-content" id="tab-settings">
                <div class="filter-card" style="display: block;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <h4 style="color: #ffffff; font-size: 1.1rem; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                                <span>🌍 Mazgų Geografinės Filtracijos Nustatymai</span>
                                <span id="settingFilterStatusBadge" class="badge badge-gray">Kraunama...</span>
                            </h4>
                            <p style="color: var(--text-muted); font-size: 0.84rem; max-width: 800px;">
                                Valdykite papildomą filtravimo sluoksnį (layerį), kuris prieš formuojant <code>/api/nodes</code> JSON atrinks tik tuos taškus, kurie geografiškai patenka į Lietuvos ribas.
                            </p>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <a href="../api/nodes" target="_blank" class="btn btn-secondary btn-sm" style="text-decoration: none;">
                                <span>🔗 Tikrinti /api/nodes</span>
                            </a>
                            <button type="button" class="btn btn-sm" onclick="loadSettings(true)">
                                <span>🔄 Atnaujinti</span>
                            </button>
                        </div>
                    </div>

                    <!-- Statistika apie taškų pasiskirstymą -->
                    <div class="stats-banner" style="margin-top: 16px; margin-bottom: 16px;">
                        <div class="stat-box">
                            <div class="val" id="stTotalNodes">-</div>
                            <div class="lbl">Visi Mazgai DB</div>
                        </div>
                        <div class="stat-box green">
                            <div class="val" id="stLtNodes">-</div>
                            <div class="lbl">Lietuvoje (Koordinatės)</div>
                        </div>
                        <div class="stat-box orange">
                            <div class="val" id="stOutsideNodes">-</div>
                            <div class="lbl">Už Lietuvos Ribų</div>
                        </div>
                        <div class="stat-box">
                            <div class="val" id="stNoCoordsNodes">-</div>
                            <div class="lbl">Be Koordinačių (0,0)</div>
                        </div>
                    </div>
                </div>

                <div class="table-wrapper" style="padding: 24px; max-width: 900px;">
                    <form id="systemSettingsForm" onsubmit="handleSaveSettings(event)">
                        <h4 style="color: #ffffff; font-size: 1rem; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                            Filtravimo Taisyklės
                        </h4>

                        <!-- Pagrindinis jungiklis -->
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                            <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                <input type="checkbox" id="settingFilterLtEnabled" style="width: 20px; height: 20px; margin-top: 2px;">
                                <div>
                                    <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                                        Įjungti taškų filtravimą tik Lietuvoje
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        Kai aktyvuota, <code>/api/nodes</code>, <code>/api/repyters</code> ir <code>/api/repeater</code> (telemetrijos atnaujinimo eilės parinkimas ESP32) taiko geografinį filtrą ir atmeta taškus, kurie yra už nustatytų Lietuvos koordinačių rėmų.
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Taškai be koordinačių pasirinkimas -->
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                            <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                <input type="checkbox" id="settingFilterLtIncludeNoCoords" style="width: 20px; height: 20px; margin-top: 2px;">
                                <div>
                                    <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                                        Įtraukti mazgus be koordinačių
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        Jei pažymėta, mazgai, neturintys nustatytų GPS koordinačių (lat/lon yra 0 arba tušti), taip pat bus pateikiami sąraše. Jei atžymėta – bus rodomi tik patvirtinti taškai su Lietuvos koordinatėmis.
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Lietuvos geografinės ribos -->
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                                <div>
                                    <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">Lietuvos Geografinių Koordinačių Ribos (Bounding Box)</div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">Numatytosios Lietuvos ribos su minimalia tolerancija pasienyje.</div>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="resetDefaultLtBounds()">Atstatyti numatytąsias</button>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 12px;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="settingLtLatMin">Minimali Platuma (Lat Min °N)</label>
                                    <input type="number" step="0.0001" id="settingLtLatMin" class="form-control" placeholder="53.8500" required>
                                    <small style="color: var(--text-dim); font-size: 0.72rem;">Pietinis taškas (~53.85°)</small>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="settingLtLatMax">Maksimali Platuma (Lat Max °N)</label>
                                    <input type="number" step="0.0001" id="settingLtLatMax" class="form-control" placeholder="56.4500" required>
                                    <small style="color: var(--text-dim); font-size: 0.72rem;">Šiaurinis taškas (~56.45°)</small>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="settingLtLonMin">Minimali Ilguma (Lon Min °E)</label>
                                    <input type="number" step="0.0001" id="settingLtLonMin" class="form-control" placeholder="20.9000" required>
                                    <small style="color: var(--text-dim); font-size: 0.72rem;">Vakarinis taškas (~20.90°)</small>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="settingLtLonMax">Maksimali Ilguma (Lon Max °E)</label>
                                    <input type="number" step="0.0001" id="settingLtLonMax" class="form-control" placeholder="26.8500" required>
                                    <small style="color: var(--text-dim); font-size: 0.72rem;">Rytinis taškas (~26.85°)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Telemetrijos Užklausų Eilės & Stebėjimo Nustatymai -->
                        <h4 style="color: #ffffff; font-size: 1rem; margin-top: 24px; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                            📡 Telemetrijos Užklausų Eilės & Stebėjimo Nustatymai
                        </h4>

                        <!-- Source Route / Path maršrutizavimo nustatymas -->
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                            <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                <input type="checkbox" id="settingEnableSourceRouting" style="width: 20px; height: 20px; margin-top: 2px;">
                                <div>
                                    <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                                        Įjungti Source Route (Path) naudojimą ESP32 botui
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        Kai įjungta, botui leidžiama siųsti užklausas per tikslinius maršrutus (DIRECT kelią per tarpinius kartotuvus).<br>
                                        <strong>Kai išjungta:</strong> botui nurodoma visas užklausas siųsti tik per <strong>FLOOD</strong> (per visą tinklą), nenaudojant jokių DIRECT kelių.
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Automatinis naujų kartotuvų pridėjimas stebėjimui -->
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                            <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                <input type="checkbox" id="settingAutoAddNewRepeaters" style="width: 20px; height: 20px; margin-top: 2px;">
                                <div>
                                    <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                                        Naujus taškus pridėti stebėjimui automatiškai
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        Jei įjungta, visi naujai aptikti kartotuvai (repeaters), gauti per tinklo advert pranešimus, bus automatiškai pridedami prie stebimų sąrašo (<code>update = true</code>) ir įtraukiami į telemetrijos atnaujinimo eilę.
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                                <div style="max-width: 580px;">
                                    <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                                        Kreipimosi dėl telemetrijos intervalas (po sėkmingo gavimo)
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        Kas kiek valandų gali būti vėl imama telemetrija iš mazgo, kai telemetrija buvo <b>sėkmingai gauta</b> (galioja <code>/api/repeater</code> ir <code>/api/repyters</code>, jei botui nenustatytas individualus intervalas).
                                        <br><span style="color: var(--accent-cyan);">Numatyta reikšmė: <b>1</b> val. (galima įvesti pvz. <code>6</code>, <code>12</code>, <code>24</code> arba <code>0.5</code>). Įveskite <b>0</b>, jei norite rotuoti be laiko tarpo.</span>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="number" min="0" max="720" step="any" id="settingMinTelemetryIntervalHours" class="form-control" style="width: 100px; font-weight: 700; text-align: center; font-size: 1rem;" placeholder="1" required>
                                    <span style="font-size: 0.82rem; color: var(--text-muted);">val.</span>
                                </div>
                            </div>
                        </div>

                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                                <div style="max-width: 580px;">
                                    <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                                        Pakartotinio bandymo intervalas po nesėkmės (fail)
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        Po kiek valandų vėl bandyti pasiimti telemetriją iš mazgo, jei paskutinis bandymas buvo <b>nesėkmingas (timeout / fail)</b>.
                                        <br>Kai po pakartotinio bandymo telemetrija sėkmingai gaunama, toliau mazgui vėl taikomas pagrindinis telemetrijos intervalas.
                                        <br><span style="color: var(--accent-cyan);">Numatyta reikšmė: <b>1</b> val. (galima nustatyti ir kiekvienam botui individualiai).</span>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="number" min="0.05" max="720" step="any" id="settingMinRetryIntervalHours" class="form-control" style="width: 100px; font-weight: 700; text-align: center; font-size: 1rem;" placeholder="1" required>
                                    <span style="font-size: 0.82rem; color: var(--text-muted);">val.</span>
                                </div>
                            </div>
                        </div>

                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                                <div style="max-width: 580px;">
                                    <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                                        Statistikos grafiko periodas žemėlapyje
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        Kelių dienų temperatūros ir elektros įtampos/srovės kitimo kreivė atvaizduojama žemėlapyje paspaudus ant mazgo kortelės.
                                        <br><span style="color: var(--accent-cyan);">Numatyta reikšmė: <b>7</b> d. (savaitės statistika). Galite nurodyti pvz. 1, 3, 7, 14 ar 30 dienų.</span>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="number" min="1" max="365" step="1" id="settingTelemetryChartDays" class="form-control" style="width: 100px; font-weight: 700; text-align: center; font-size: 1rem;" placeholder="7" required>
                                    <span style="font-size: 0.82rem; color: var(--text-muted);">d.</span>
                                </div>
                            </div>
                        </div>

                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                                <div style="max-width: 580px;">
                                    <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                                        Mazgų rodymo žemėlapyje senaties limitas (dienomis)
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        Praėjus nurodytam dienų skaičiui, kai mazgo telemetrija nebuvo atnaujinta, mazgas automatiškai neberodomas žemėlapyje.
                                        <br><span style="color: var(--accent-cyan);">Numatyta reikšmė: <b>0</b> (išjungta, rodomi visi mazgai). Galite nurodyti pvz. <b>3</b>, <b>7</b>, <b>14</b> ar <b>30</b> dienų.</span>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="number" min="0" max="3650" step="1" id="settingMaxTelemetryAgeDays" class="form-control" style="width: 100px; font-weight: 700; text-align: center; font-size: 1rem;" placeholder="0" required>
                                    <span style="font-size: 0.82rem; color: var(--text-muted);">d.</span>
                                </div>
                            </div>
                        </div>

                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                                <div style="max-width: 580px;">
                                    <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                                        Maksimalus nesėkmingų bandymų (fail) limitas
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        Kiek kartų iš eilės neatsakius mazgui į telemetrijos užklausą (timeout), jis nustoja būti imamas į <code>/api/repeater</code> ir <code>/api/repyters</code> atnaujinimų sąrašą.
                                        <br><span style="color: var(--accent-cyan);">Numatyta reikšmė: <b>20</b>. Įveskite <b>0</b>, jei norite limitą išjungti (užklausti be apribojimų).</span>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="number" min="0" max="1000" step="1" id="settingMaxTelemetryFails" class="form-control" style="width: 100px; font-weight: 700; text-align: center; font-size: 1rem;" placeholder="20" required>
                                    <span style="font-size: 0.82rem; color: var(--text-muted);">fail</span>
                                </div>
                            </div>

                            <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                <div style="font-size: 0.78rem; color: var(--text-dim);">
                                    Reikia atblokuoti visus sustabdytus mazgus ir nuresetinti jų nesėkmingų bandymų skaičių į 0?
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="resetAllFailCounts()">
                                    <span>🧹 Nuresetinti visų mazgų fail (0)</span>
                                </button>
                            </div>
                        </div>

                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                                <div style="max-width: 580px;">
                                    <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                                        Viešo API kešavimo TTL (minutėmis)
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        Nustato, kiek laiko <code>/api/node/list</code> ir <code>/node/{public_key}</code> atsakymai saugomi spartinančiojoje atmintinėje (failų keše), sumažinant duomenų bazės apkrovą.
                                        <br><span style="color: var(--accent-cyan);">Numatyta reikšmė: <b>5</b> min. Įveskite <b>0</b>, jei norite kešavimą išjungti (visada realiu laiku iš DB).</span>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="number" min="0" max="1440" step="1" id="settingApiCacheTtlMins" class="form-control" style="width: 100px; font-weight: 700; text-align: center; font-size: 1rem;" placeholder="5" required>
                                    <span style="font-size: 0.82rem; color: var(--text-muted);">min.</span>
                                </div>
                            </div>

                            <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                <div style="font-size: 0.78rem; color: var(--text-dim);">
                                    Reikia iškart atnaujinti viešus endpointus ir ištrinti visus kešo failus?
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="clearApiCache()">
                                    <span>🗑️ Išvalyti API kešą</span>
                                </button>
                            </div>
                        </div>

                        <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                                <div style="max-width: 580px;">
                                    <div style="font-size: 0.95rem; font-weight: 600; color: #ffffff;">
                                        Mazgo telemetrijos įrašų kiekis viešam API
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                        Kiek paskutinių telemetrijos įrašų atiduoda endpointas <code>/api/node/{bot_id}</code> (arba <code>/node/{public_key}</code>).
                                        <br><span style="color: var(--accent-cyan);">Numatyta reikšmė: <b>50</b> įrašų. Galite nurodyti pvz. <b>20</b>, <b>50</b>, <b>100</b>, <b>200</b> ar <b>500</b>.</span>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="number" min="1" max="5000" step="1" id="settingApiNodeTelemetryLimit" class="form-control" style="width: 100px; font-weight: 700; text-align: center; font-size: 1rem;" placeholder="50" required>
                                    <span style="font-size: 0.82rem; color: var(--text-muted);">įr.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Išsaugojimas ir pranešimas -->
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-top: 24px;">
                            <div id="settingsFeedback" style="font-size: 0.85rem; font-weight: 500;"></div>
                            <button type="submit" class="btn" id="btnSaveSettings" style="min-width: 160px; justify-content: center;">
                                <span>💾 Išsaugoti Nustatymus</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODALAS: MAZGO NUSTATYMAI -->
        <div class="modal-overlay" id="editNodeModal">
            <div class="modal-card">
                <div class="modal-header">
                    <h3 id="editNodeTitle">Mazgo Nustatymai</h3>
                    <button class="modal-close" onclick="closeModal('editNodeModal')">&times;</button>
                </div>
                <form id="editNodeForm" onsubmit="handleSaveNode(event)">
                    <div class="modal-body">
                        <input type="hidden" id="editNodePublicKey">

                        <div class="form-group">
                            <label>Viešasis Raktas (Public Key)</label>
                            <input type="text" id="editNodeKeyDisplay" class="form-control" style="font-family: var(--font-mono); font-size: 0.75rem;" readonly>
                        </div>

                        <div class="form-group">
                            <label for="editNodeName">Mazgo Pavadinimas (Name)</label>
                            <input type="text" id="editNodeName" class="form-control" placeholder="pvz. LT-LM Tilžė B9DD" required>
                        </div>

                        <div class="form-group">
                            <label for="editNodePassword">Valdymo Slaptažodis (ESP32 Botui)</label>
                            <input type="text" id="editNodePassword" class="form-control" placeholder="Slaptažodis kartotuvo telemetrijai siųsti (gali būti tuščias)">
                            <div style="font-size: 0.70rem; color: var(--text-dim); margin-top: 4px;">Šis slaptažodis perduodamas į /api/repeater JSON toks, koks įrašytas DB (jei tuščias – siunčiamas tuščias kodas).</div>
                        </div>

                        <div class="form-group">
                            <label for="editNodeBotId">Priskirtas Botas (ESP32 Gateway)</label>
                            <select id="editNodeBotId" class="form-control">
                                <!-- Užpildoma dinamiškai iš botų sąrašo -->
                            </select>
                            <div style="margin-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500; color: var(--text-main, #f1f2f6);">
                                    <input type="checkbox" id="editNodeBotLocked">
                                    <span>🔒 Užrakinti boto priskyrimą</span>
                                </label>
                                <div style="font-size: 0.70rem; color: var(--text-dim); margin-top: 3px; margin-left: 24px;">
                                    Neleisti automatiškai perrašyti boto pagal gautus ADVERT paketus ar GPS koordinates (fiksuotas priskyrimas).
                                </div>
                            </div>
                            <div style="font-size: 0.70rem; color: var(--text-dim); margin-top: 4px;">ESP32 stotis / vartai, kuriems priskirtas šis mazgas.</div>
                        </div>

                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label for="editNodeLastPath" style="margin-bottom: 0;">Maršrutizavimo Režimas (Path / Route)</label>
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.8rem; margin-bottom: 0;">
                                    <input type="checkbox" id="editNodeEnablePath" onchange="toggleNodePathMode(this.checked)">
                                    <span id="editNodeEnablePathLabel" style="font-weight: 600; color: #2ed573;">ĮJUNGTA (Source Route)</span>
                                </label>
                            </div>
                            <div id="editNodePathInputContainer">
                                <input type="text" id="editNodeLastPath" class="form-control" placeholder="pvz. B9DD4607, DIRECT arba tuščia (automatinis mokymasis)">
                                <div style="font-size: 0.70rem; color: var(--text-dim); margin-top: 4px;">Konkretūs tarpiniai kartotuvai (pvz. B9DD4607). Palikite tuščią, kad ESP32 išmoktų dinamiškai.</div>
                            </div>
                            <div id="editNodePathDisabledNotice" style="display: none; padding: 10px 12px; background: rgba(167, 139, 250, 0.1); border: 1px dashed #a78bfa; border-radius: var(--radius-md); font-size: 0.75rem; color: #d8b4fe;">
                                🌐 <strong>IŠJUNGTA:</strong> Šiam mazgui maršrutizavimo kelias nenaudojamas. Visos užklausos bus siunčiamos tik per <strong>FLOOD</strong> (per visą tinklą).
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label for="editNodeLat">GPS Platuma (Latitude)</label>
                                <input type="number" step="0.0000001" id="editNodeLat" class="form-control" placeholder="pvz. 55.7052">
                            </div>
                            <div class="form-group">
                                <label for="editNodeLon">GPS Ilguma (Longitude)</label>
                                <input type="number" step="0.0000001" id="editNodeLon" class="form-control" placeholder="pvz. 21.1458">
                            </div>
                        </div>

                        <div style="display: flex; gap: 20px; padding: 10px 0;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="editNodeUpdate" style="width: 16px; height: 16px;">
                                <span style="font-weight: 600; color: #2ed573;">Aktyviai stebėti / atnaujinti per botą (update=1)</span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="editNodeIsRepeater" style="width: 16px; height: 16px;">
                                <span>Mazgas yra kartotuvas</span>
                            </label>
                        </div>

                        <!-- Mazgo išjungimo (disable) valdymas -->
                        <div style="margin: 8px 0; padding: 12px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md);">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" id="editNodeIsDisabled" style="width: 18px; height: 18px; accent-color: #ef4444;">
                                <div>
                                    <span style="font-weight: 700; color: #ef4444; font-size: 0.9rem;">Išjungti mazgą (Disable)</span>
                                    <div style="font-size: 0.72rem; color: var(--text-dim); margin-top: 2px;">Išjungtas mazgas nebus imamas į eilę ir nebus rodomas žemėlapyje.</div>
                                </div>
                            </label>
                        </div>

                        <!-- Fail count ir eilės valdymas mazgui -->
                        <div id="editNodeFailSection" style="margin-top: 10px; padding: 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <div>
                                <div style="font-size: 0.86rem; font-weight: 600; color: #ffffff;" id="editNodeFailLabel">
                                    Nesėkmingi bandymai (fail): 0
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-dim);" id="editNodeFailSub">
                                    Jei pasiekia nustatytą limitą, mazgas sustabdomas nuo automatinių užklausų.
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="resetActiveEditNodeQueue()" title="Nukreipti šį mazgą į eilės pradžią (atstatyti requested_date)" style="white-space: nowrap;">
                                    <span>⚡ Resetinti eilę</span>
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" id="btnResetActiveNodeFail" onclick="resetActiveEditNodeFail()" style="white-space: nowrap;">
                                    <span>🧹 Resetinti fail (0)</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editNodeModal')">Atšaukti</button>
                        <button type="submit" class="btn">Išsaugoti Pakeitimus</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODALAS: BOTO NUSTATYMAI -->
        <div class="modal-overlay" id="editBotModal">
            <div class="modal-card">
                <div class="modal-header">
                    <h3 id="editBotTitle">Boto Nustatymai</h3>
                    <button class="modal-close" onclick="closeModal('editBotModal')">&times;</button>
                </div>
                <form id="editBotForm" onsubmit="handleSaveBot(event)">
                    <div class="modal-body">
                        <input type="hidden" id="editBotOriginalId">
                        <input type="hidden" id="editBotIsNew">

                        <div class="form-group">
                            <label for="editBotId">Boto Identifikatorius (ID)</label>
                            <input type="text" id="editBotId" class="form-control" placeholder="pvz. KLP, VILNIUS, KAUNAS" required style="font-family: var(--font-mono); font-weight: 700; text-transform: uppercase;">
                            <div style="font-size: 0.70rem; color: var(--text-dim); margin-top: 4px;">Naudojamas ESP32 vartų užklausose kaip bot_id (pvz. ?bot_id=KLP).</div>
                        </div>

                        <div class="form-group">
                            <label for="editBotName">Boto Pavadinimas (Name)</label>
                            <input type="text" id="editBotName" class="form-control" placeholder="pvz. Klaipėdos ESP32 Vartai" required>
                        </div>

                        <div class="form-group">
                            <label for="editBotPublicKey">Viešasis Raktas (Public Key)</label>
                            <input type="text" id="editBotPublicKey" class="form-control" placeholder="64 heksadecimalūs simboliai (Ed25519) arba tuščia" style="font-family: var(--font-mono); font-size: 0.75rem;">
                            <div style="font-size: 0.70rem; color: var(--text-dim); margin-top: 4px;">ESP32 modulio Ed25519 32 baitų viešasis raktas (64 hex).</div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="form-group">
                                <label for="editBotLat">GPS Platuma (Latitude)</label>
                                <input type="number" step="0.0000001" id="editBotLat" class="form-control" placeholder="pvz. 55.705207">
                            </div>
                            <div class="form-group">
                                <label for="editBotLon">GPS Ilguma (Longitude)</label>
                                <input type="number" step="0.0000001" id="editBotLon" class="form-control" placeholder="pvz. 21.145874">
                            </div>
                        </div>

                        <div style="margin: 4px 0 14px 0; padding: 12px; background: rgba(162, 155, 254, 0.06); border: 1px solid rgba(162, 155, 254, 0.22); border-radius: var(--radius-md);">
                            <div style="font-weight: 700; color: #a29bfe; font-size: 0.86rem; margin-bottom: 10px;">⏱️ Individualus Telemetrijos Apklausos Grafikas</div>

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label for="editBotIntervalHours">Kreipimosi dėl telemetrijos intervalas (valandomis)</label>
                                <input type="number" min="0" max="720" step="any" id="editBotIntervalHours" class="form-control" placeholder="Tuščia = naudoti bendrą sistemos intervalą">
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px;">
                                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotIntervalHours').value = ''">Globalus</button>
                                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotIntervalHours').value = '1'">Kas 1 val.</button>
                                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotIntervalHours').value = '3'">Kas 3 val.</button>
                                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotIntervalHours').value = '6'">Kas 6 val.</button>
                                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotIntervalHours').value = '12'">Kas 12 val.</button>
                                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotIntervalHours').value = '24'">Kas 24 val.</button>
                                </div>
                                <div style="font-size: 0.70rem; color: var(--text-dim); margin-top: 4px;">Taikoma po sėkmingai gautos telemetrijos. Pvz., <code>6</code> = kas 6 val., <code>12</code> = kas 12 val.</div>
                            </div>

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label for="editBotRetryIntervalHours">Pakartotinio bandymo intervalas po nesėkmės / fail (valandomis)</label>
                                <input type="number" min="0" max="720" step="any" id="editBotRetryIntervalHours" class="form-control" placeholder="Tuščia = naudoti bendrą sistemos fail intervalą">
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px;">
                                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotRetryIntervalHours').value = ''">Globalus</button>
                                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotRetryIntervalHours').value = '0.5'">Po 0.5 val.</button>
                                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotRetryIntervalHours').value = '1'">Po 1 val.</button>
                                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotRetryIntervalHours').value = '2'">Po 2 val.</button>
                                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotRetryIntervalHours').value = '3'">Po 3 val.</button>
                                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotRetryIntervalHours').value = '6'">Po 6 val.</button>
                                </div>
                                <div style="font-size: 0.70rem; color: var(--text-dim); margin-top: 4px;">Po kiek valandų kartoti užklausą, jei mazgas neatsakė (gautas <code>fail</code>). Kai atsako – vėl galioja pagrindinis intervalas.</div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div class="form-group" style="margin-bottom: 4px;">
                                    <label for="editBotHourStart">Darbo laikas NUO (valanda, LT)</label>
                                    <select id="editBotHourStart" class="form-control">
                                        <option value="">Visą parą (24/7)</option>
                                        <option value="0">00:00 (0h)</option>
                                        <option value="1">01:00 (1h)</option>
                                        <option value="2">02:00 (2h)</option>
                                        <option value="3">03:00 (3h)</option>
                                        <option value="4">04:00 (4h)</option>
                                        <option value="5">05:00 (5h)</option>
                                        <option value="6">06:00 (6h)</option>
                                        <option value="7">07:00 (7h)</option>
                                        <option value="8">08:00 (8h)</option>
                                        <option value="9">09:00 (9h)</option>
                                        <option value="10">10:00 (10h)</option>
                                        <option value="11">11:00 (11h)</option>
                                        <option value="12">12:00 (12h)</option>
                                        <option value="13">13:00 (13h)</option>
                                        <option value="14">14:00 (14h)</option>
                                        <option value="15">15:00 (15h)</option>
                                        <option value="16">16:00 (16h)</option>
                                        <option value="17">17:00 (17h)</option>
                                        <option value="18">18:00 (18h)</option>
                                        <option value="19">19:00 (19h)</option>
                                        <option value="20">20:00 (20h)</option>
                                        <option value="21">21:00 (21h)</option>
                                        <option value="22">22:00 (22h)</option>
                                        <option value="23">23:00 (23h)</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 4px;">
                                    <label for="editBotHourEnd">Darbo laikas IKI (valanda, LT)</label>
                                    <select id="editBotHourEnd" class="form-control">
                                        <option value="">Visą parą (24/7)</option>
                                        <option value="0">00:00 (0h)</option>
                                        <option value="1">01:00 (1h)</option>
                                        <option value="2">02:00 (2h)</option>
                                        <option value="3">03:00 (3h)</option>
                                        <option value="4">04:00 (4h)</option>
                                        <option value="5">05:00 (5h)</option>
                                        <option value="6">06:00 (6h)</option>
                                        <option value="7">07:00 (7h)</option>
                                        <option value="8">08:00 (8h)</option>
                                        <option value="9">09:00 (9h)</option>
                                        <option value="10">10:00 (10h)</option>
                                        <option value="11">11:00 (11h)</option>
                                        <option value="12">12:00 (12h)</option>
                                        <option value="13">13:00 (13h)</option>
                                        <option value="14">14:00 (14h)</option>
                                        <option value="15">15:00 (15h)</option>
                                        <option value="16">16:00 (16h)</option>
                                        <option value="17">17:00 (17h)</option>
                                        <option value="18">18:00 (18h)</option>
                                        <option value="19">19:00 (19h)</option>
                                        <option value="20">20:00 (20h)</option>
                                        <option value="21">21:00 (21h)</option>
                                        <option value="22">22:00 (22h)</option>
                                        <option value="23">23:00 (23h)</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px;">
                                <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotHourStart').value=''; document.getElementById('editBotHourEnd').value='';">🟢 Visą parą (24/7)</button>
                                <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotHourStart').value='23'; document.getElementById('editBotHourEnd').value='6';">🌙 23:00 – 06:00</button>
                                <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotHourStart').value='1'; document.getElementById('editBotHourEnd').value='5';">🌙 01:00 – 05:00</button>
                                <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.72rem;" onclick="document.getElementById('editBotHourStart').value='0'; document.getElementById('editBotHourEnd').value='6';">🌙 00:00 – 06:00</button>
                            </div>
                            <div style="font-size: 0.70rem; color: var(--text-dim); margin-top: 4px;">Palaiko ir perėjimą per vidurnaktį (pvz. nuo <code>23:00</code> iki <code>06:00</code>). Ne darbo valandomis šiam botui per <code>/api/repeater</code> kartotuvai nebus duodami.</div>
                        </div>

                        <div class="form-group">
                            <label for="editBotDescription">Pastabos / Aprašymas</label>
                            <textarea id="editBotDescription" class="form-control" rows="2" placeholder="Papildoma informacija apie vietą, anteną ar įrangą..."></textarea>
                        </div>

                        <div style="margin: 8px 0; padding: 12px; background: rgba(0, 210, 255, 0.05); border: 1px solid rgba(0, 210, 255, 0.2); border-radius: var(--radius-md);">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" id="editBotEnabled" style="width: 18px; height: 18px; accent-color: var(--accent-cyan);" checked>
                                <div>
                                    <span style="font-weight: 700; color: var(--accent-cyan); font-size: 0.9rem;">Botas Įjungtas / Aktyvus</span>
                                    <div style="font-size: 0.72rem; color: var(--text-dim); margin-top: 2px;">Jei išjungtas, šiam botui nebus atiduodamos kartotuvų telemetrijos užklausos.</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editBotModal')">Atšaukti</button>
                        <button type="submit" class="btn">Išsaugoti Botą</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODALAS: RAW PAKETO PERŽIŪRA IR DEBUGERIS -->
        <div class="modal-overlay" id="packetModal">
            <div class="modal-card modal-lg">
                <div class="modal-header">
                    <h3 id="packetModalTitle">RAW Paketo Peržiūra #0</h3>
                    <button class="modal-close" onclick="closeModal('packetModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <div id="packetMetaRow" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;"></div>
                        <div style="display: flex; gap: 8px;">
                            <button class="btn btn-sm btn-success" id="btnCopyDebugInModal" onclick="copyDebugInfo(activePacketId)" title="Nukopijuoti visą paketo procesą ir duomenis derinimui su AI / Dev">📋 Nukopijuoti derinimui (AI / Dev)</button>
                            <button class="btn btn-sm" id="btnReprocessInModal" onclick="triggerReprocessFromModal()">🔄 Pakartoti Iškodavimą (Reprocess)</button>
                        </div>
                    </div>

                    <div id="packetErrorBox" style="display: none; background: rgba(220, 53, 69, 0.15); border: 1px solid var(--accent-red); color: #ff6b81; padding: 10px 14px; border-radius: var(--radius-sm); font-size: 0.8rem;"></div>

                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">RAW HEX:</span>
                            <button class="copy-btn" onclick="copyText(document.getElementById('modalRawHex').innerText)">📋 Kopijuoti HEX</button>
                        </div>
                        <pre id="modalRawHex" class="json-viewer" style="max-height: 80px;"></pre>
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">Iškoduotas Turinys (decoded_json):</span>
                            <button class="copy-btn" onclick="copyText(document.getElementById('modalDecodedJson').innerText)">📋 Kopijuoti JSON</button>
                        </div>
                        <pre id="modalDecodedJson" class="json-viewer"></pre>
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">Originalus HTTP POST (payload_json):</span>
                        </div>
                        <pre id="modalPayloadJson" class="json-viewer" style="max-height: 120px;"></pre>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('packetModal')">Uždaryti</button>
                </div>
            </div>
        </div>

        <!-- MODALAS: API UŽKLAUSOS IR ATSAKYMO DETALI PERŽIŪRA -->
        <div class="modal-overlay" id="apiLogModal">
            <div class="modal-card modal-lg">
                <div class="modal-header">
                    <h3 id="apiLogModalTitle">API Užklausa #0</h3>
                    <button class="modal-close" onclick="closeModal('apiLogModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="apiLogMetaRow" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 10px;"></div>

                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 10px 12px; margin-bottom: 10px; font-size: 0.8rem;">
                        <div style="margin-bottom: 4px;">
                            <strong style="color: var(--text-muted);">Pilnas URL (Path):</strong>
                            <code id="apiLogFullUri" style="color: var(--accent-cyan); font-family: var(--font-mono); word-break: break-all;"></code>
                        </div>
                        <div>
                            <strong style="color: var(--text-muted);">Kliento User-Agent:</strong>
                            <span id="apiLogUserAgent" style="color: #ffffff; font-family: var(--font-mono); font-size: 0.76rem; word-break: break-all;"></span>
                        </div>
                    </div>

                    <div id="apiLogRequestSection" style="display: none; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">📥 Gauta Užklausa (POST Request Body):</span>
                            <button class="copy-btn" onclick="copyText(document.getElementById('apiLogRequestBody').innerText)">📋 Kopijuoti Request</button>
                        </div>
                        <pre id="apiLogRequestBody" class="json-viewer" style="max-height: 160px;"></pre>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">📤 Grąžintas API Atsakymas (Response Body):</span>
                            <button class="copy-btn" onclick="copyText(document.getElementById('apiLogResponseBody').innerText)">📋 Kopijuoti Atsakymą</button>
                        </div>
                        <pre id="apiLogResponseBody" class="json-viewer" style="max-height: 300px;"></pre>
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">🔍 Kliento HTTP Antraštės (Headers):</span>
                            <button class="copy-btn" onclick="copyText(document.getElementById('apiLogHeadersJson').innerText)">📋 Kopijuoti Antraštes</button>
                        </div>
                        <pre id="apiLogHeadersJson" class="json-viewer" style="max-height: 140px;"></pre>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btnFilterByModalIp" onclick="">🎯 Filtruoti šį IP</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('apiLogModal')">Uždaryti</button>
                </div>
            </div>
        </div>

        <!-- Pranešimų kortelė (Toast) -->
        <div class="toast-msg" id="toastMsg"></div>
    <?php endif; ?>

    <script>
        let currentTab = 'nodes';
        let nodesData = [];
        let activePacketId = null;
        let activePacketData = null;
        
        // Puslapiavimas (po 50 per puslapį kiekvienai lentelei)
        let nodesPage = 0;
        const nodesLimit = 50;

        let telPage = 0;
        const telLimit = 50;
        let telNodeFilter = '';

        let rawPage = 0;
        const rawLimit = 50;
        let rawNodeFilter = '';

        let queuePage = 0;
        const queueLimit = 50;

        let apiLogsPage = 0;
        const apiLogsLimit = 50;

        // Rūšiavimo būsenos valdymas (atsimenama kiekvienai lentelei individualiai per localStorage)
        function getSortState(tableKey, defaultCol, defaultDir) {
            try {
                const stored = localStorage.getItem('meshcore_sort_' + tableKey);
                if (stored) {
                    const parsed = JSON.parse(stored);
                    if (parsed && parsed.col && (parsed.dir === 'asc' || parsed.dir === 'desc')) {
                        return parsed;
                    }
                }
            } catch (e) {}
            return { col: defaultCol, dir: defaultDir };
        }

        function setSortState(tableKey, col, dir) {
            try {
                localStorage.setItem('meshcore_sort_' + tableKey, JSON.stringify({ col, dir }));
            } catch (e) {}
        }

        let nodesSort = getSortState('nodes', 'last_seen_at', 'desc');
        let telSort = getSortState('telemetry', 'recorded_at', 'desc');
        let rawSort = getSortState('raw', 'id', 'desc');
        let queueSort = getSortState('queue', 'index', 'asc');
        let apiLogsSort = getSortState('apilogs', 'id', 'desc');

        function updateTableSortHeaders(tableKey) {
            let currentSort;
            let tableId;
            if (tableKey === 'nodes') { currentSort = nodesSort; tableId = 'nodesTable'; }
            else if (tableKey === 'telemetry') { currentSort = telSort; tableId = 'telemetryTable'; }
            else if (tableKey === 'raw') { currentSort = rawSort; tableId = 'rawTable'; }
            else if (tableKey === 'queue') { currentSort = queueSort; tableId = 'queueTable'; }
            else if (tableKey === 'apilogs') { currentSort = apiLogsSort; tableId = 'apiLogsTable'; }
            else return;

            const table = document.getElementById(tableId);
            if (!table) return;

            const headers = table.querySelectorAll('th[data-sort]');
            headers.forEach(th => {
                const sortKey = th.getAttribute('data-sort');
                const iconSpan = th.querySelector('.sort-icon');
                th.classList.remove('sorted-asc', 'sorted-desc');

                if (sortKey === currentSort.col) {
                    th.classList.add(currentSort.dir === 'asc' ? 'sorted-asc' : 'sorted-desc');
                    if (iconSpan) iconSpan.textContent = (currentSort.dir === 'asc') ? '▲' : '▼';
                } else {
                    if (iconSpan) iconSpan.textContent = '⇅';
                }
            });
        }

        function handleSortTable(tableKey, col) {
            let currentSort;
            if (tableKey === 'nodes') currentSort = nodesSort;
            else if (tableKey === 'telemetry') currentSort = telSort;
            else if (tableKey === 'raw') currentSort = rawSort;
            else if (tableKey === 'queue') currentSort = queueSort;
            else if (tableKey === 'apilogs') currentSort = apiLogsSort;
            else return;

            let newDir = 'asc';
            if (currentSort.col === col) {
                newDir = (currentSort.dir === 'asc') ? 'desc' : 'asc';
            } else {
                const descFirst = ['id', 'recorded_at', 'created_at', 'last_seen_at', 'requested_date', 'telemetry', 'battery_v', 'temperature_c', 'rssi', 'uptime', 'length', 'duration_ms', 'response_size', 'status_code'];
                newDir = descFirst.includes(col) ? 'desc' : 'asc';
            }

            currentSort.col = col;
            currentSort.dir = newDir;
            setSortState(tableKey, col, newDir);

            if (tableKey === 'nodes') {
                nodesPage = 0;
                loadNodes(0);
            } else if (tableKey === 'telemetry') {
                telPage = 0;
                loadTelemetry(0);
            } else if (tableKey === 'raw') {
                rawPage = 0;
                loadRawPackets(0);
            } else if (tableKey === 'queue') {
                queuePage = 0;
                renderNodeQueueTable();
            } else if (tableKey === 'apilogs') {
                apiLogsPage = 0;
                loadApiLogs(0);
            }
        }

        // Istorijos ir modalų būsenos valdymas
        let activePacketModalId = null;
        let activeEditNodeKey = null;
        let pendingEditNodeKey = null;
        let activeEditBotId = null;
        let allBotsData = [];
        let modalOpenedInSession = false;
        let isApplyingUrlState = false;

        // Prisijungimo apdorojimas
        async function handleLogin(e) {
            e.preventDefault();
            const password = document.getElementById('adminPassword').value;
            const errBox = document.getElementById('loginError');
            errBox.style.display = 'none';

            try {
                const res = await fetch('api.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password })
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    errBox.innerText = data.error || 'Neteisingas slaptažodis!';
                    errBox.style.display = 'block';
                }
            } catch (err) {
                errBox.innerText = 'Serverio ryšio klaida!';
                errBox.style.display = 'block';
            }
        }

        // Atsijungimas
        async function handleLogout() {
            await fetch('api.php?action=logout', { method: 'POST' });
            location.reload();
        }

        // --- URL & ISTORIJOS (HISTORY) SINCHRONIZAVIMAS ---
        function getAppUrl(overrides = {}) {
            const params = new URLSearchParams();
            const tab = overrides.tab !== undefined ? overrides.tab : currentTab;
            params.set('tab', tab || 'nodes');

            const editNode = overrides.edit_node !== undefined ? overrides.edit_node : activeEditNodeKey;
            const packet = overrides.packet !== undefined ? overrides.packet : activePacketModalId;

            if (tab === 'nodes') {
                const search = overrides.search !== undefined ? overrides.search : (document.getElementById('nodesSearch')?.value || '').trim();
                const status = overrides.status !== undefined ? overrides.status : (document.getElementById('nodesStatusFilter')?.value || '');
                const type = overrides.type !== undefined ? overrides.type : (document.getElementById('nodesTypeFilter')?.value || '');
                const update = overrides.update !== undefined ? overrides.update : (document.getElementById('nodesUpdateFilter')?.value || '');
                const page = overrides.page !== undefined ? overrides.page : nodesPage;

                if (search) params.set('search', search);
                if (status) params.set('status', status);
                if (type) params.set('type', type);
                if (update) params.set('update', update);
                if (page > 0) params.set('page', page + 1);
            } else if (tab === 'telemetry') {
                const node = overrides.node !== undefined ? overrides.node : (document.getElementById('telemetryNodeInput')?.value || '').trim();
                const type = overrides.type !== undefined ? overrides.type : (document.getElementById('telemetryTypeFilter')?.value || '');
                const page = overrides.page !== undefined ? overrides.page : telPage;

                if (node) params.set('node', node);
                if (type) params.set('type', type);
                if (page > 0) params.set('page', page + 1);
            } else if (tab === 'raw') {
                const search = overrides.search !== undefined ? overrides.search : (document.getElementById('rawSearchInput')?.value || '').trim();
                const status = overrides.status !== undefined ? overrides.status : (document.getElementById('rawStatusFilter')?.value || '');
                const dir = overrides.dir !== undefined ? overrides.dir : (document.getElementById('rawDirectionFilter')?.value || '');
                const ptype = overrides.packet_type !== undefined ? overrides.packet_type : (document.getElementById('rawTypeFilter')?.value || '');
                const page = overrides.page !== undefined ? overrides.page : rawPage;

                if (search) params.set('search', search);
                if (status) params.set('status', status);
                if (dir) params.set('dir', dir);
                if (ptype !== '' && ptype !== null && ptype !== undefined) params.set('packet_type', ptype);
                if (page > 0) params.set('page', page + 1);
            } else if (tab === 'bots') {
                const search = overrides.search !== undefined ? overrides.search : (document.getElementById('botsSearch')?.value || '').trim();
                const status = overrides.status !== undefined ? overrides.status : (document.getElementById('botsStatusFilter')?.value || '');
                if (search) params.set('search', search);
                if (status) params.set('status', status);
            } else if (tab === 'apilogs') {
                const search = overrides.search !== undefined ? overrides.search : (document.getElementById('apiLogsSearchInput')?.value || '').trim();
                const ip = overrides.ip !== undefined ? overrides.ip : (document.getElementById('apiLogsIpInput')?.value || '').trim();
                const ep = overrides.endpoint !== undefined ? overrides.endpoint : (document.getElementById('apiLogsEndpointFilter')?.value || 'all');
                const ct = overrides.client_type !== undefined ? overrides.client_type : (document.getElementById('apiLogsClientTypeFilter')?.value || 'all');
                const page = overrides.page !== undefined ? overrides.page : apiLogsPage;
                if (search) params.set('search', search);
                if (ip) params.set('ip', ip);
                if (ep && ep !== 'all') params.set('endpoint', ep);
                if (ct && ct !== 'all') params.set('client_type', ct);
                if (page > 0) params.set('page', page + 1);
            } else if (tab === 'tools') {
                // Eilė ir įrankiai papildomų parametrų neturi
            }

            if (packet) params.set('packet', packet);
            if (editNode) params.set('edit_node', editNode);

            const qs = params.toString();
            return window.location.pathname + (qs ? '?' + qs : '');
        }

        function updateHistory(overrides = {}, mode = 'push') {
            if (isApplyingUrlState) return;
            const newUrl = getAppUrl(overrides);
            const currentUrl = window.location.pathname + window.location.search;
            if (newUrl === currentUrl && mode === 'push') return;

            const state = {
                tab: overrides.tab !== undefined ? overrides.tab : currentTab,
                activePacketModalId: overrides.packet !== undefined ? overrides.packet : activePacketModalId,
                activeEditNodeKey: overrides.edit_node !== undefined ? overrides.edit_node : activeEditNodeKey,
                activeEditBotId: overrides.edit_bot !== undefined ? overrides.edit_bot : activeEditBotId,
                hasModal: !!(overrides.packet || overrides.edit_node || overrides.edit_bot || activePacketModalId || activeEditNodeKey || activeEditBotId)
            };

            if (mode === 'push') {
                history.pushState(state, '', newUrl);
            } else if (mode === 'replace') {
                history.replaceState(state, '', newUrl);
            }
            updateDocumentTitle();
        }

        function updateDocumentTitle() {
            const titles = {
                nodes: '🛰️ Mazgai',
                bots: '🤖 Botai',
                telemetry: '📊 Telemetrija',
                raw: '📦 RAW Paketai',
                apilogs: '🌐 API Užklausos',
                tools: '⚡ Eilė ir Įrankiai',
                settings: '⚙️ Nustatymai'
            };
            let title = titles[currentTab] || 'MeshCore Admin';
            if (activePacketModalId) {
                title += ` (Paketas #${activePacketModalId})`;
            } else if (activeEditNodeKey) {
                title += ` (Mazgo nustatymai)`;
            } else if (activeEditBotId) {
                title += ` (Boto nustatymai)`;
            }
            document.title = `${title} — MeshCore Admin`;
        }

        function applyUrlState(isInitialLoad = false) {
            isApplyingUrlState = true;
            try {
                const params = new URLSearchParams(window.location.search);
                let tab = params.get('tab') || 'nodes';
                if (!['nodes', 'bots', 'telemetry', 'raw', 'apilogs', 'tools', 'settings'].includes(tab)) {
                    tab = 'nodes';
                }

                const packetId = params.get('packet');
                const editKey = params.get('edit_node') || params.get('edit');

                // 1. Modalo būsenos atstatymas
                if (packetId) {
                    if (activePacketModalId != packetId) {
                        inspectPacket(packetId, false);
                    }
                } else {
                    const pModal = document.getElementById('packetModal');
                    if (pModal && pModal.classList.contains('active')) {
                        closeModal('packetModal', false);
                    }
                }

                if (editKey) {
                    if (activeEditNodeKey != editKey) {
                        if (nodesData && nodesData.length > 0) {
                            openEditNode(editKey, false);
                        } else {
                            pendingEditNodeKey = editKey;
                        }
                    }
                } else {
                    const eModal = document.getElementById('editNodeModal');
                    if (eModal && eModal.classList.contains('active')) {
                        closeModal('editNodeModal', false);
                    }
                }

                // 2. Aktyvus skirtukas UI
                currentTab = tab;
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                const btn = document.getElementById(`tabBtn-${tab}`);
                const content = document.getElementById(`tab-${tab}`);
                if (btn) btn.classList.add('active');
                if (content) content.classList.add('active');

                // 3. Skirtuko duomenų atstatymas ir užkrovimas
                if (tab === 'nodes') {
                    const search = params.get('search') || params.get('q') || '';
                    const status = params.get('status') || '';
                    const type = params.get('type') || '';
                    const update = params.get('update') || '';
                    const pageParam = parseInt(params.get('page'), 10);
                    nodesPage = (!isNaN(pageParam) && pageParam > 0) ? (pageParam - 1) : 0;

                    if (document.getElementById('nodesSearch')) document.getElementById('nodesSearch').value = search;
                    if (document.getElementById('nodesStatusFilter')) document.getElementById('nodesStatusFilter').value = status;
                    if (document.getElementById('nodesTypeFilter')) document.getElementById('nodesTypeFilter').value = type;
                    if (document.getElementById('nodesUpdateFilter')) document.getElementById('nodesUpdateFilter').value = update;

                    loadNodes(nodesPage);
                } else if (tab === 'telemetry') {
                    const node = params.get('node') || '';
                    const type = params.get('type') || '';
                    const pageParam = parseInt(params.get('page'), 10);
                    telPage = (!isNaN(pageParam) && pageParam > 0) ? (pageParam - 1) : 0;
                    telNodeFilter = node;

                    if (document.getElementById('telemetryNodeInput')) document.getElementById('telemetryNodeInput').value = node;
                    if (document.getElementById('telemetryTypeFilter')) document.getElementById('telemetryTypeFilter').value = type;
                    const chip = document.getElementById('telemetryNodeFilterTag');
                    if (chip) {
                        if (node) {
                            document.getElementById('telemetryActiveNodeText').innerText = node;
                            chip.style.display = 'inline-flex';
                        } else {
                            chip.style.display = 'none';
                        }
                    }

                    loadTelemetry(telPage);
                } else if (tab === 'raw') {
                    const search = params.get('search') || '';
                    const status = params.get('status') || '';
                    const dir = params.get('dir') || '';
                    const ptype = params.get('packet_type') || '';
                    const pageParam = parseInt(params.get('page'), 10);
                    rawPage = (!isNaN(pageParam) && pageParam > 0) ? (pageParam - 1) : 0;
                    rawNodeFilter = search;

                    if (document.getElementById('rawSearchInput')) document.getElementById('rawSearchInput').value = search;
                    if (document.getElementById('rawStatusFilter')) document.getElementById('rawStatusFilter').value = status;
                    if (document.getElementById('rawDirectionFilter')) document.getElementById('rawDirectionFilter').value = dir;
                    if (document.getElementById('rawTypeFilter')) document.getElementById('rawTypeFilter').value = ptype;
                    const chip = document.getElementById('rawNodeFilterTag');
                    if (chip) {
                        if (search) {
                            document.getElementById('rawActiveNodeText').innerText = search;
                            chip.style.display = 'inline-flex';
                        } else {
                            chip.style.display = 'none';
                        }
                    }

                    loadRawPackets(rawPage);
                } else if (tab === 'bots') {
                    loadBots();
                } else if (tab === 'apilogs') {
                    const search = params.get('search') || '';
                    const ip = params.get('ip') || '';
                    const ep = params.get('endpoint') || 'all';
                    const ct = params.get('client_type') || 'all';
                    const pageParam = parseInt(params.get('page'), 10);
                    apiLogsPage = (!isNaN(pageParam) && pageParam > 0) ? (pageParam - 1) : 0;

                    if (document.getElementById('apiLogsSearchInput')) document.getElementById('apiLogsSearchInput').value = search;
                    if (document.getElementById('apiLogsIpInput')) document.getElementById('apiLogsIpInput').value = ip;
                    if (document.getElementById('apiLogsEndpointFilter')) document.getElementById('apiLogsEndpointFilter').value = ep;
                    if (document.getElementById('apiLogsClientTypeFilter')) document.getElementById('apiLogsClientTypeFilter').value = ct;
                    loadApiLogs(apiLogsPage);
                } else if (tab === 'tools') {
                    loadNodeQueue();
                } else if (tab === 'settings') {
                    loadSettings();
                }

                if (isInitialLoad && !params.get('tab')) {
                    updateHistory({ tab: currentTab }, 'replace');
                }
                updateAllAdminClearBtns();
                updateDocumentTitle();
            } finally {
                isApplyingUrlState = false;
            }
        }

        // Pagalbinės filtrų išvalymo (X) mygtukų funkcijos
        function updateAdminClearBtn(inputId) {
            const input = document.getElementById(inputId);
            const btn = document.getElementById(inputId + 'ClearBtn');
            if (btn) {
                btn.style.display = (input && input.value.trim().length > 0) ? 'inline-flex' : 'none';
            }
        }

        function updateAllAdminClearBtns() {
            ['nodesSearch', 'botsSearch', 'telemetryNodeInput', 'rawSearchInput', 'queueSearchInput', 'apiLogsSearchInput', 'apiLogsIpInput'].forEach(updateAdminClearBtn);
        }

        function clearNodesSearch() {
            const input = document.getElementById('nodesSearch');
            if (input) {
                input.value = '';
                input.focus();
            }
            updateAdminClearBtn('nodesSearch');
            nodesPage = 0;
            updateHistory({ tab: 'nodes', search: '', page: 0 }, 'push');
            loadNodes(0);
        }

        function clearBotsSearch() {
            const input = document.getElementById('botsSearch');
            if (input) {
                input.value = '';
                input.focus();
            }
            updateAdminClearBtn('botsSearch');
            updateHistory({ tab: 'bots', search: '', page: 0 }, 'push');
            renderBotsTable();
        }

        function clearQueueFilter() {
            const input = document.getElementById('queueSearchInput');
            if (input) {
                input.value = '';
                input.focus();
            }
            updateAdminClearBtn('queueSearchInput');
            clearTimeout(queueFilterTimeout);
            queuePage = 0;
            renderNodeQueueTable();
            loadNodeQueue(false);
        }

        // Skirtukų perjungimas
        function switchTab(tabName, pushState = true) {
            if (!['nodes', 'bots', 'telemetry', 'raw', 'apilogs', 'tools', 'settings'].includes(tabName)) {
                tabName = 'nodes';
            }

            if (activePacketModalId) closeModal('packetModal', false);
            if (activeEditNodeKey) closeModal('editNodeModal', false);
            if (activeEditBotId) closeModal('editBotModal', false);
            closeModal('apiLogModal', false);

            currentTab = tabName;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            const btn = document.getElementById(`tabBtn-${tabName}`);
            const content = document.getElementById(`tab-${tabName}`);
            if (btn) btn.classList.add('active');
            if (content) content.classList.add('active');

            if (pushState && !isApplyingUrlState) {
                updateHistory({ tab: tabName, packet: null, edit_node: null }, 'push');
            }
            updateDocumentTitle();

            updateTableSortHeaders(tabName === 'tools' ? 'queue' : tabName);

            if (tabName === 'nodes') loadNodes(nodesPage);
            else if (tabName === 'bots') loadBots();
            else if (tabName === 'telemetry') loadTelemetry(telPage);
            else if (tabName === 'raw') loadRawPackets(rawPage);
            else if (tabName === 'apilogs') loadApiLogs(apiLogsPage);
            else if (tabName === 'tools') loadNodeQueue();
            else if (tabName === 'settings') loadSettings();
        }

        // Filtravimo ir paieškos apdorotojai
        const debouncedLoadNodes = debounce(() => {
            nodesPage = 0;
            updateHistory({ tab: 'nodes', page: 0 }, 'replace');
            loadNodes(0);
        }, 300);

        function onNodesSearchInput() {
            updateAdminClearBtn('nodesSearch');
            debouncedLoadNodes();
        }

        function onNodesSearchEnter() {
            nodesPage = 0;
            updateHistory({ tab: 'nodes', page: 0 }, 'push');
            loadNodes(0);
        }

        function onNodesFilterChange() {
            nodesPage = 0;
            updateHistory({ tab: 'nodes', page: 0 }, 'push');
            loadNodes(0);
        }

        function onTelemetryFilterChange() {
            telPage = 0;
            const nodeVal = document.getElementById('telemetryNodeInput').value.trim();
            telNodeFilter = nodeVal;
            const chip = document.getElementById('telemetryNodeFilterTag');
            if (chip) {
                if (nodeVal) {
                    document.getElementById('telemetryActiveNodeText').innerText = nodeVal;
                    chip.style.display = 'inline-flex';
                } else {
                    chip.style.display = 'none';
                }
            }
            updateHistory({ tab: 'telemetry', node: nodeVal, page: 0 }, 'push');
            loadTelemetry(0);
        }

        const debouncedLoadRaw = debounce(() => {
            rawPage = 0;
            updateHistory({ tab: 'raw', page: 0 }, 'replace');
            loadRawPackets(0);
        }, 350);

        function onRawSearchInput() {
            updateAdminClearBtn('rawSearchInput');
            debouncedLoadRaw();
        }

        function onRawSearchEnter() {
            rawPage = 0;
            updateHistory({ tab: 'raw', page: 0 }, 'push');
            loadRawPackets(0);
        }

        function onRawFilterChange() {
            rawPage = 0;
            updateHistory({ tab: 'raw', page: 0 }, 'push');
            loadRawPackets(0);
        }

        function refreshCurrentTab() {
            loadStats();
            if (currentTab === 'nodes') loadNodes();
            else if (currentTab === 'bots') loadBots();
            else if (currentTab === 'telemetry') loadTelemetry(telPage);
            else if (currentTab === 'raw') loadRawPackets(rawPage);
            else if (currentTab === 'apilogs') loadApiLogs(apiLogsPage);
            showToast('Duomenys atnaujinti!');
        }

        // Statistikos užkrovimas
        async function loadStats() {
            try {
                const res = await fetch('api.php?action=stats');
                const data = await res.json();
                if (data.success) {
                    const n = data.nodes || {};
                    const r = data.raw || {};
                    const t = data.telemetry || {};

                    document.getElementById('statNodesCount').innerText = n.total_nodes || 0;
                    document.getElementById('statMonitoredCount').innerText = n.monitored_repeaters_count || 0;
                    document.getElementById('statRawTotal').innerText = r.total || 0;
                    document.getElementById('statQueuePending').innerText = r.pending || 0;
                    document.getElementById('statErrorsCount').innerText = r.errors || 0;
                    document.getElementById('statTelemetryCount').innerText = t.total_records || 0;

                    if (document.getElementById('statBotsCount') && data.bots) {
                        document.getElementById('statBotsCount').innerText = `${data.bots.active_bots || 0}/${data.bots.total_bots || 0}`;
                    }
                }
            } catch (e) {
                console.error('Klaida gaunant statistiką:', e);
            }
        }

        // 1. MAZGŲ SĄRAŠAS
        async function loadNodes(page = 0) {
            nodesPage = page;
            const search = document.getElementById('nodesSearch')?.value || '';
            const botId = document.getElementById('nodesBotFilter')?.value || '';
            const status = document.getElementById('nodesStatusFilter')?.value || '';
            const type = document.getElementById('nodesTypeFilter')?.value || '';
            const update = document.getElementById('nodesUpdateFilter')?.value || '';
            const offset = page * nodesLimit;

            const url = `api.php?action=nodes&search=${encodeURIComponent(search)}&bot_id=${encodeURIComponent(botId)}&status=${encodeURIComponent(status)}&type=${encodeURIComponent(type)}&update=${encodeURIComponent(update)}&limit=${nodesLimit}&offset=${offset}&sort_by=${encodeURIComponent(nodesSort.col)}&sort_dir=${nodesSort.dir}`;
            try {
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) {
                    nodesData = data.nodes || [];
                    document.getElementById('nodesCountBadge').innerText = `${data.total} mazgų`;
                    const pageInfo = document.getElementById('nodesPageInfo');
                    const btnPrev = document.getElementById('btnPrevNodes');
                    const btnNext = document.getElementById('btnNextNodes');
                    if (pageInfo) pageInfo.innerText = `Puslapis ${nodesPage + 1} iš ${Math.max(1, Math.ceil(data.total / nodesLimit))}`;
                    if (btnPrev) btnPrev.disabled = (nodesPage <= 0);
                    if (btnNext) btnNext.disabled = (offset + nodesLimit >= data.total);
                    renderNodesTable(nodesData);
                    updateTableSortHeaders('nodes');

                    if (pendingEditNodeKey) {
                        openEditNode(pendingEditNodeKey, false);
                        pendingEditNodeKey = null;
                    }
                }
            } catch (err) {
                console.error(err);
            }
        }

        function changeNodesPage(delta) {
            const newPage = Math.max(0, nodesPage + delta);
            if (newPage !== nodesPage) {
                nodesPage = newPage;
                updateHistory({ tab: 'nodes', page: nodesPage }, 'push');
                loadNodes(nodesPage);
            }
        }

        function renderNodesTable(nodes) {
            const tbody = document.getElementById('nodesTableBody');
            if (!nodes.length) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding: 24px; color: var(--text-dim);">Mazgų nerasta.</td></tr>';
                return;
            }

            tbody.innerHTML = nodes.map(n => {
                const name = n.node_name || n.node_id || 'Bevardis';
                const id = n.node_id || 'N/A';
                const key = n.public_key || '';
                const isRep = n.is_repeater == 1 || n.is_repeater === true;
                const isUp = n.update == 1 || n.update === true;
                const isDis = n.is_disabled == 1 || n.is_disabled === true || n.is_disabled === '1';
                const hasLoc = (n.latitude && n.longitude && Math.abs(n.latitude) > 0.001);
                const pass = n.password !== null && n.password !== undefined ? n.password : '';
                const botId = n.bot_id || 'KLP';
                const isBotLocked = (n.is_bot_locked == 1 || n.is_bot_locked === true || n.is_bot_locked === '1');
                const failCount = parseInt(n.telemetry_fail_count || 0, 10);

                const lastPath = (n.last_path || '').trim();
                const isFlood = (lastPath.toUpperCase() === 'FLOOD');

                return `
                    <tr class="${isDis ? 'row-node-disabled' : ''}">
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <span class="badge badge-blue" style="cursor: pointer;" onclick="openEditNode('${escapeHtml(key)}')" title="Paspauskite, kad atidarytumėte mazgo nustatymus (${escapeHtml(key)})">${id.toUpperCase()}</span>
                                <span onclick="openEditNode('${escapeHtml(key)}')" style="font-weight: 600; color: #ffffff; cursor: pointer; text-decoration: underline dotted rgba(167, 139, 250, 0.65); text-underline-offset: 3px;" title="Paspauskite, kad atidarytumėte mazgo „${escapeHtml(name)}“ nustatymus ⚙️">${escapeHtml(name)}</span>
                                ${isDis ? `<span class="badge badge-red" style="font-size: 0.68rem; font-weight: 700; padding: 1px 5px;" title="Mazgas išjungtas iš eilės ir žemėlapio">⛔ IŠJUNGTAS</span>` : ''}
                                ${isFlood ? `
                                    <span class="badge badge-purple" style="font-size: 0.68rem; font-weight: 700; padding: 1px 5px; cursor: pointer;" onclick="quickToggleNodePath('${escapeHtml(key)}', true)" title="Path išjungtas (FLOOD). Paspauskite, kad įjungtumėte Source Route">🌐 FLOOD</span>
                                ` : (lastPath !== '' ? `
                                    <span class="badge badge-cyan" style="font-size: 0.68rem; font-weight: 700; padding: 1px 5px; cursor: pointer;" onclick="quickToggleNodePath('${escapeHtml(key)}', false)" title="Maršrutas: [${escapeHtml(lastPath)}]. Paspauskite, kad išjungtumėte (perjungtumėte į FLOOD)">🎯 [${escapeHtml(lastPath)}]</span>
                                ` : `
                                    <span class="badge badge-gray" style="font-size: 0.68rem; font-weight: 600; padding: 1px 5px; cursor: pointer;" onclick="quickToggleNodePath('${escapeHtml(key)}', false)" title="Automatinis dinaminis kelias. Paspauskite, kad išjungtumėte (perjungtumėte į FLOOD)">🤖 AUTO</span>
                                `)}
                                ${failCount > 0 ? `<span class="badge badge-red" style="font-size: 0.68rem; font-weight: 700; padding: 1px 5px; cursor: pointer;" onclick="resetNodeFailCount('${id}', '${escapeHtml(name)}')" title="${failCount} nesėkmingi bandymai. Paspauskite, kad nuresetintumėte į 0.">⚠️ ${failCount} fail 🧹</span>` : ''}
                            </div>
                        </td>
                        <td>
                            ${isDis ? `
                                <span class="badge badge-red" style="font-weight: 700; cursor: pointer; padding: 2px 8px;" onclick="quickToggleNodeDisabled('${escapeHtml(key)}', false)" title="Mazgas IŠJUNGTAS (neimamas į eilę, nerodomas žemėlapyje). Paspauskite, kad įjungtumėte">⛔ Išjungtas</span>
                            ` : `
                                <span class="badge badge-green" style="font-weight: 600; cursor: pointer; padding: 2px 8px;" onclick="quickToggleNodeDisabled('${escapeHtml(key)}', true)" title="Mazgas AKTYVUS. Paspauskite, kad išjungtumėte">✅ Aktyvus</span>
                            `}
                        </td>
                        <td>
                            ${isRep ? '<span class="badge badge-cyan">KARTOTUVAS</span>' : '<span class="badge badge-gray">KLIENTAS</span>'}
                        </td>
                        <td>
                            <span class="badge badge-purple" style="cursor: pointer;" onclick="filterNodesByBot('${escapeHtml(botId)}')" title="ESP32 Šaltinis: ${escapeHtml(n.bot_name || botId)}${isBotLocked ? ' (UŽRAKINTAS - neperrašomas per adverto koordinates)' : ''}. Paspauskite filtruoti.">🤖 ${escapeHtml(botId)}${isBotLocked ? ' 🔒' : ''}</span>
                        </td>
                        <td>
                            ${isUp ? '<span class="badge badge-green">TAIP</span>' : '<span class="badge badge-gray">NE</span>'}
                        </td>
                        <td>
                            ${pass !== '' ? `<span class="badge badge-amber" title="${escapeHtml(pass)}">🔑 Nustatytas</span>` : '<span style="color:var(--text-dim);font-size:0.75rem;">Tuščias ("")</span>'}
                        </td>
                        <td>
                            ${hasLoc ? `<span style="color:var(--accent-cyan); font-family:var(--font-mono);">${parseFloat(n.latitude).toFixed(3)}, ${parseFloat(n.longitude).toFixed(3)}</span>` : '<span style="color:var(--text-dim);">Nėra</span>'}
                        </td>
                        <td>
                            <div style="font-size: 0.75rem;" title="Matytas tinkle (LT laiku): ${formatVilniusDate(n.last_seen_at)}">📡 ${formatRelativeTime(n.last_seen_at)}</div>
                            <div style="font-size: 0.70rem; color: #a78bfa; margin-top: 2px;" title="Telemetrija atnaujinta (LT laiku): ${formatVilniusDate(n.telemetry_updated_at)}">
                                ${n.telemetry_updated_at ? '⏱️ ' + formatRelativeTime(n.telemetry_updated_at) : '<span style="color:var(--text-dim);">⏱️ Nėra</span>'}
                            </div>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button class="btn btn-secondary btn-icon" onclick="openEditNode('${key}')" title="Keisti nustatymus">⚙️</button>
                            ${isDis ? `
                                <button class="btn btn-secondary btn-icon" style="margin-left: 3px; color: #2ed573;" onclick="quickToggleNodeDisabled('${escapeHtml(key)}', false)" title="Įjungti mazgą (Enable)">▶️</button>
                            ` : `
                                <button class="btn btn-secondary btn-icon" style="margin-left: 3px; color: #ff6b81;" onclick="quickToggleNodeDisabled('${escapeHtml(key)}', true)" title="Išjungti mazgą (Disable)">⏸️</button>
                            `}
                            <button class="btn btn-secondary btn-icon" style="margin-left: 3px;" onclick="resetSingleRequestedDate('${escapeHtml(key)}', '${escapeHtml(name)}')" title="Resetinti šio mazgo eilę (atstatyti requested_date į NULL, nukreipti į eilės pradžią)">⚡</button>
                            <button class="btn btn-secondary btn-icon" style="margin-left: 3px; ${failCount > 0 ? 'color: #ff6b81;' : ''}" onclick="resetNodeFailCount('${escapeHtml(key)}', '${escapeHtml(name)}')" title="Nuresetinti nesėkmingų bandymų (fail=${failCount}) skaičių į 0">🧹</button>
                            <button class="btn btn-secondary btn-icon" style="margin-left: 3px;" onclick="filterTelemetryByNode('${id}')" title="Atidaryti telemetrijos įrašus">📊</button>
                            <button class="btn btn-secondary btn-icon" style="margin-left: 3px;" onclick="filterRawByNode('${id}')" title="Atidaryti RAW paketus">📦</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function toggleNodePathMode(enabled, presetVal = null) {
            const label = document.getElementById('editNodeEnablePathLabel');
            const inputCont = document.getElementById('editNodePathInputContainer');
            const notice = document.getElementById('editNodePathDisabledNotice');
            const input = document.getElementById('editNodeLastPath');
            if (enabled) {
                if (label) { label.innerText = 'ĮJUNGTA (Source Route)'; label.style.color = '#2ed573'; }
                if (inputCont) inputCont.style.display = 'block';
                if (notice) notice.style.display = 'none';
                if (presetVal !== null && input) input.value = presetVal;
                else if (input && input.value.trim().toUpperCase() === 'FLOOD') input.value = '';
            } else {
                if (label) { label.innerText = 'IŠJUNGTA (Tik FLOOD)'; label.style.color = '#f87171'; }
                if (inputCont) inputCont.style.display = 'none';
                if (notice) notice.style.display = 'block';
                if (input) input.value = 'FLOOD';
            }
        }

        async function quickToggleNodePath(key, enable) {
            try {
                const res = await fetch('api.php?action=update_node', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ public_key: key, last_path: enable ? '' : 'FLOOD' })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(enable ? 'Source Route įjungtas šiam mazgui!' : 'Path išjungtas - mazgas perjungtas į FLOOD!', 'info');
                    await loadNodes();
                } else {
                    alert('Klaida: ' + (data.error || 'Nepavyko'));
                }
            } catch (e) {
                console.error(e);
            }
        }

        // Mazgo redagavimo langas (veikia iš bet kurio skirtuko – jei mazgo nėra aktyviame nodesData puslapyje, užkrauna iš API)
        async function openEditNode(publicKey, pushHistory = true) {
            if (!publicKey || publicKey === '-') return;
            const cleanTarget = String(publicKey).trim().toLowerCase();
            if (!cleanTarget) return;

            let node = (nodesData && nodesData.length > 0)
                ? nodesData.find(n =>
                    (n.public_key && n.public_key.toLowerCase() === cleanTarget) ||
                    (n.node_id && n.node_id.toLowerCase() === cleanTarget) ||
                    (n.node_name && n.node_name.toLowerCase() === cleanTarget)
                )
                : null;

            if (!node) {
                try {
                    const res = await fetch(`api.php?action=nodes&search=${encodeURIComponent(publicKey)}&limit=25`);
                    const data = await res.json();
                    if (data.success && Array.isArray(data.nodes) && data.nodes.length > 0) {
                        node = data.nodes.find(n => n.public_key && n.public_key.toLowerCase() === cleanTarget)
                            || data.nodes.find(n => n.node_id && n.node_id.toLowerCase() === cleanTarget && (n.is_repeater == 1 || n.is_repeater === true))
                            || data.nodes.find(n => n.node_id && n.node_id.toLowerCase() === cleanTarget)
                            || data.nodes.find(n => n.public_key && n.public_key.toLowerCase().startsWith(cleanTarget) && (n.is_repeater == 1 || n.is_repeater === true))
                            || data.nodes[0];
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            if (!node) {
                showToast(`Mazgas „${publicKey}“ nerastas duomenų bazėje`, 'warning');
                return;
            }

            activeEditNodeKey = node.public_key;
            if (pushHistory && !isApplyingUrlState) {
                modalOpenedInSession = true;
                updateHistory({ tab: currentTab || 'nodes', edit_node: node.public_key }, 'push');
            }

            document.getElementById('editNodePublicKey').value = node.public_key;
            document.getElementById('editNodeKeyDisplay').value = node.public_key;
            document.getElementById('editNodeName').value = node.node_name || '';
            document.getElementById('editNodePassword').value = (node.password !== null && node.password !== undefined) ? String(node.password) : '';
            
            const rawPath = (node.last_path || '').trim();
            const isFlood = (rawPath.toUpperCase() === 'FLOOD');
            const pathCheckbox = document.getElementById('editNodeEnablePath');
            if (pathCheckbox) {
                pathCheckbox.checked = !isFlood;
                toggleNodePathMode(!isFlood, isFlood ? '' : rawPath);
            } else {
                document.getElementById('editNodeLastPath').value = rawPath;
            }

            document.getElementById('editNodeLat').value = node.latitude || '';
            document.getElementById('editNodeLon').value = node.longitude || '';
            document.getElementById('editNodeUpdate').checked = (node.update == 1 || node.update === true);
            document.getElementById('editNodeIsRepeater').checked = (node.is_repeater == 1 || node.is_repeater === true);
            const isDis = (node.is_disabled == 1 || node.is_disabled === true || node.is_disabled === '1');
            const disCheckbox = document.getElementById('editNodeIsDisabled');
            if (disCheckbox) disCheckbox.checked = isDis;

            const failCnt = parseInt(node.telemetry_fail_count || 0, 10);
            const failLabel = document.getElementById('editNodeFailLabel');
            if (failLabel) {
                failLabel.innerHTML = `Nesėkmingi bandymai (fail): <span style="color: ${failCnt > 0 ? '#ff6b81' : '#2ed573'}; font-weight: 700;">${failCnt}</span>`;
            }

            document.getElementById('editNodeTitle').innerText = `Mazgo Nustatymai: ${node.node_name || node.node_id || ''}`;

            populateBotDropdowns();
            const botSel = document.getElementById('editNodeBotId');
            if (botSel) {
                botSel.value = (node.bot_id || 'KLP').toUpperCase();
            }
            const botLockedCheckbox = document.getElementById('editNodeBotLocked');
            if (botLockedCheckbox) {
                botLockedCheckbox.checked = (node.is_bot_locked == 1 || node.is_bot_locked === true || node.is_bot_locked === '1');
            }

            openModal('editNodeModal');
            updateDocumentTitle();
        }

        async function handleSaveNode(e) {
            e.preventDefault();
            const publicKey = document.getElementById('editNodePublicKey').value;
            const isPathEnabled = document.getElementById('editNodeEnablePath') ? document.getElementById('editNodeEnablePath').checked : true;
            let finalPath = document.getElementById('editNodeLastPath').value.trim();
            if (!isPathEnabled) {
                finalPath = 'FLOOD';
            }

            const payload = {
                public_key: publicKey,
                node_name: document.getElementById('editNodeName').value,
                password: document.getElementById('editNodePassword').value,
                bot_id: document.getElementById('editNodeBotId')?.value || 'KLP',
                is_bot_locked: document.getElementById('editNodeBotLocked')?.checked ? 1 : 0,
                last_path: finalPath,
                update: document.getElementById('editNodeUpdate').checked ? 1 : 0,
                is_disabled: document.getElementById('editNodeIsDisabled')?.checked ? 1 : 0,
                is_repeater: document.getElementById('editNodeIsRepeater').checked ? 1 : 0,
                latitude: document.getElementById('editNodeLat').value,
                longitude: document.getElementById('editNodeLon').value,
            };

            try {
                const res = await fetch('api.php?action=update_node', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Mazgo nustatymai išsaugoti sėkmingai!', 'success');
                    closeModal('editNodeModal');
                    loadNodes(nodesPage);
                    if (currentTab === 'raw') loadRawPackets(rawPage);
                    else if (currentTab === 'telemetry') loadTelemetry(telPage);
                    else if (currentTab === 'queue') loadNodeQueue();
                    loadStats();
                } else {
                    alert('Klaida: ' + (data.error || 'Nepavyko išsaugoti'));
                }
            } catch (err) {
                alert('Serverio ryšio klaida');
            }
        }

        async function quickToggleNodeDisabled(key, disable) {
            try {
                const res = await fetch('api.php?action=update_node', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ public_key: key, is_disabled: disable ? 1 : 0 })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(disable ? 'Mazgas išjungtas (nebus imamas į eilę ir nerodomas žemėlapyje)!' : 'Mazgas įjungtas (aktyvus)!', disable ? 'warning' : 'success');
                    await loadNodes(nodesPage);
                    loadStats();
                } else {
                    alert('Klaida: ' + (data.error || 'Nepavyko atnaujinti būsenos'));
                }
            } catch (e) {
                console.error(e);
            }
        }

        // 2. TELEMETRIJOS SĄRAŠAS
        function filterTelemetryByNode(nodeId) {
            telNodeFilter = nodeId;
            telPage = 0;
            document.getElementById('telemetryNodeInput').value = nodeId;
            updateAdminClearBtn('telemetryNodeInput');
            document.getElementById('telemetryActiveNodeText').innerText = nodeId;
            document.getElementById('telemetryNodeFilterTag').style.display = 'inline-flex';
            
            currentTab = 'telemetry';
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById('tabBtn-telemetry')?.classList.add('active');
            document.getElementById('tab-telemetry')?.classList.add('active');

            updateHistory({ tab: 'telemetry', node: nodeId, page: 0, packet: null }, 'push');
            loadTelemetry(0);
        }

        function clearTelemetryNodeFilter() {
            telNodeFilter = '';
            telPage = 0;
            const input = document.getElementById('telemetryNodeInput');
            if (input) {
                input.value = '';
                input.focus();
            }
            updateAdminClearBtn('telemetryNodeInput');
            document.getElementById('telemetryNodeFilterTag').style.display = 'none';
            updateHistory({ tab: 'telemetry', node: '', page: 0 }, 'push');
            loadTelemetry(0);
        }

        // ==========================================
        // 🤖 BOTŲ VALDYMAS (ESP32 GATEWAY BOTAI)
        // ==========================================
        async function loadBots() {
            try {
                const res = await fetch('api.php?action=bots');
                const data = await res.json();
                if (data && data.success && Array.isArray(data.bots)) {
                    allBotsData = data.bots;
                    populateBotDropdowns();
                    renderBotsTable();

                    const statCard = document.getElementById('statBotsCount');
                    if (statCard) {
                        const activeCount = allBotsData.filter(b => b.enabled == 1 || b.enabled === true).length;
                        statCard.innerText = `${activeCount}/${allBotsData.length}`;
                    }
                }
            } catch (err) {
                console.error('Klaida kraunant botus:', err);
            }
        }

        function populateBotDropdowns() {
            if (!allBotsData || !Array.isArray(allBotsData)) return;

            // 1. Mazgų filtro dropdown (nodesBotFilter)
            const nodeBotFilter = document.getElementById('nodesBotFilter');
            if (nodeBotFilter) {
                const currentVal = nodeBotFilter.value;
                nodeBotFilter.innerHTML = '<option value="">Visi botai</option>';
                allBotsData.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.bot_id;
                    opt.textContent = `${b.bot_id} (${b.name || 'Botas'})`;
                    nodeBotFilter.appendChild(opt);
                });
                if (currentVal) nodeBotFilter.value = currentVal;
            }

            // 2. Mazgo redagavimo modal dropdown (editNodeBotId)
            const editNodeBotSel = document.getElementById('editNodeBotId');
            if (editNodeBotSel) {
                const currentVal = editNodeBotSel.value;
                editNodeBotSel.innerHTML = '';
                allBotsData.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.bot_id;
                    opt.textContent = `${b.bot_id} - ${b.name || 'Botas'}${b.enabled == 0 ? ' [IŠJUNGTAS]' : ''}`;
                    editNodeBotSel.appendChild(opt);
                });
                if (currentVal) editNodeBotSel.value = currentVal;
            }

            // 3. Eilės filtro dropdown (queueBotFilter)
            const queueBotFilter = document.getElementById('queueBotFilter');
            if (queueBotFilter) {
                const currentVal = queueBotFilter.value;
                queueBotFilter.innerHTML = '<option value="">Visi botai</option>';
                allBotsData.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.bot_id;
                    opt.textContent = `Botas: ${b.bot_id} (${b.name || ''})`;
                    queueBotFilter.appendChild(opt);
                });
                if (currentVal) queueBotFilter.value = currentVal;
            }

            // 4. API logų filtro dropdown (apiLogsBotFilter)
            const apiLogsBotFilter = document.getElementById('apiLogsBotFilter');
            if (apiLogsBotFilter) {
                const currentVal = apiLogsBotFilter.value;
                apiLogsBotFilter.innerHTML = '<option value="all">Visi Bot ID</option><option value="__NONE__">Be Bot ID</option>';
                allBotsData.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.bot_id;
                    opt.textContent = `Botas: ${b.bot_id}`;
                    apiLogsBotFilter.appendChild(opt);
                });
                if (currentVal) apiLogsBotFilter.value = currentVal;
            }
        }

        function renderBotsTable() {
            const tbody = document.getElementById('botsTableBody');
            const badge = document.getElementById('botsCountBadge');
            if (!tbody) return;

            const searchInput = (document.getElementById('botsSearch')?.value || '').toLowerCase().trim();
            const statusFilter = document.getElementById('botsStatusFilter')?.value || '';

            let filtered = allBotsData.filter(bot => {
                const isEnabled = (bot.enabled == 1 || bot.enabled === true);
                if (statusFilter === 'enabled' && !isEnabled) return false;
                if (statusFilter === 'disabled' && isEnabled) return false;

                if (searchInput) {
                    const id = (bot.bot_id || '').toLowerCase();
                    const name = (bot.name || '').toLowerCase();
                    const pk = (bot.public_key || '').toLowerCase();
                    const desc = (bot.description || '').toLowerCase();
                    if (!id.includes(searchInput) && !name.includes(searchInput) && !pk.includes(searchInput) && !desc.includes(searchInput)) {
                        return false;
                    }
                }
                return true;
            });

            if (badge) {
                badge.innerText = `${filtered.length} iš ${allBotsData.length} botų`;
            }

            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 28px; color: var(--text-muted);">
                    Botų nerasta pagal pasirinktus paieškos kriterijus.
                </td></tr>`;
                return;
            }

            let html = '';
            const currentLtHour = parseInt(new Intl.DateTimeFormat('en-GB', { hour: '2-digit', hour12: false, timeZone: 'Europe/Vilnius' }).format(new Date()), 10);

            filtered.forEach(bot => {
                const isEnabled = (bot.enabled == 1 || bot.enabled === true);
                const statusBadge = isEnabled
                    ? `<span class="badge badge-green" style="cursor: pointer;" onclick="quickToggleBot('${escapeHtml(bot.bot_id)}', false)" title="Paspauskite, kad išjungtumėte">● Aktyvus</span>`
                    : `<span class="badge badge-red" style="cursor: pointer;" onclick="quickToggleBot('${escapeHtml(bot.bot_id)}', true)" title="Paspauskite, kad įjungtumėte">○ Išjungtas</span>`;

                const intMins = (bot.telemetry_interval_mins !== null && bot.telemetry_interval_mins !== undefined && parseInt(bot.telemetry_interval_mins, 10) > 0)
                    ? parseInt(bot.telemetry_interval_mins, 10)
                    : null;
                let intervalText = '<span style="color: var(--text-dim);">⏱️ Globalus</span>';
                if (intMins !== null) {
                    const hrs = Number((intMins / 60).toFixed(2));
                    intervalText = `<span class="badge badge-purple" title="Individualus telemetrijos intervalas: ${hrs} val.">⏱️ Kas ${hrs} val.</span>`;
                }

                const retryMins = (bot.retry_interval_mins !== null && bot.retry_interval_mins !== undefined && parseInt(bot.retry_interval_mins, 10) > 0)
                    ? parseInt(bot.retry_interval_mins, 10)
                    : null;
                let retryText = '<span style="color: var(--text-dim); font-size: 0.74rem; margin-left: 6px;" title="Naudojamas bendras sistemos pakartotinio bandymo po fail intervalas">🔄 Fail: globalus</span>';
                if (retryMins !== null) {
                    const rHrs = Number((retryMins / 60).toFixed(2));
                    retryText = `<span class="badge badge-amber" style="margin-left: 4px;" title="Pakartotinis bandymas po nesėkmės (fail): po ${rHrs} val.">🔄 Fail: po ${rHrs} val.</span>`;
                }

                const hStart = (bot.active_hour_start !== null && bot.active_hour_start !== undefined && bot.active_hour_start !== '')
                    ? parseInt(bot.active_hour_start, 10)
                    : null;
                const hEnd = (bot.active_hour_end !== null && bot.active_hour_end !== undefined && bot.active_hour_end !== '')
                    ? parseInt(bot.active_hour_end, 10)
                    : null;
                let hoursText = '<div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">🟢 24/7 (Visą parą)</div>';
                if (hStart !== null && hEnd !== null && hStart !== hEnd) {
                    const padH = (h) => String(h).padStart(2, '0') + ':00';
                    const inWin = (hStart < hEnd)
                        ? (currentLtHour >= hStart && currentLtHour < hEnd)
                        : (currentLtHour >= hStart || currentLtHour < hEnd);
                    const winIcon = inWin ? '🟢' : '🌙';
                    const winColor = inWin ? '#2ed573' : '#ffa502';
                    const winState = inWin ? 'Dabar dirba' : 'Laukia lango';
                    hoursText = `<div style="font-size: 0.76rem; color: ${winColor}; margin-top: 4px; font-family: var(--font-mono);" title="${winState} (dabartinė valanda: ${padH(currentLtHour)})">${winIcon} ${padH(hStart)} – ${padH(hEnd)}</div>`;
                }
                const scheduleHtml = `<div><div>${intervalText}${retryText}</div>${hoursText}</div>`;

                let coordsHtml = '<span style="color: var(--text-dim); font-size: 0.8rem;">Nenurodytos</span>';
                if (bot.latitude && bot.longitude) {
                    const lat = parseFloat(bot.latitude).toFixed(5);
                    const lon = parseFloat(bot.longitude).toFixed(5);
                    coordsHtml = `<a href="https://www.google.com/maps?q=${lat},${lon}" target="_blank" style="color: var(--accent-cyan); text-decoration: none; font-family: var(--font-mono); font-size: 0.82rem;" title="Atidaryti žemėlapyje">📍 ${lat}, ${lon}</a>`;
                }

                let pkHtml = '<span style="color: var(--text-dim); font-size: 0.8rem;">—</span>';
                if (bot.public_key) {
                    const shortPk = bot.public_key.substring(0, 10) + '...' + bot.public_key.substring(bot.public_key.length - 8);
                    pkHtml = `<span style="font-family: var(--font-mono); font-size: 0.78rem; color: #a4b0be; cursor: pointer;" onclick="copyText('${escapeHtml(bot.public_key)}')" title="Spustelėkite nukopijuoti">${escapeHtml(shortPk)}</span>`;
                }

                const totalNodes = parseInt(bot.total_nodes_count || 0, 10);
                const repCount = parseInt(bot.repeaters_count || 0, 10);
                const nodesBadge = `<span class="badge badge-cyan" style="cursor: pointer;" onclick="filterNodesByBot('${escapeHtml(bot.bot_id)}')" title="Rodyti šio boto mazgus">${totalNodes} mazg. (${repCount} kart.)</span>`;

                let lastSeen = '<span style="color: var(--text-dim);">—</span>';
                if (bot.last_seen_at) {
                    lastSeen = `<span style="font-size: 0.82rem; color: var(--text-muted);">${escapeHtml(bot.last_seen_at)}</span>`;
                }

                const descSub = bot.description ? `<div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 3px;">${escapeHtml(bot.description)}</div>` : '';

                html += `<tr>
                    <td>
                        <span class="badge badge-purple" style="font-family: var(--font-mono); font-weight: 700; font-size: 0.88rem;">${escapeHtml(bot.bot_id)}</span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #ffffff;">${escapeHtml(bot.name || bot.bot_id)}</div>
                        ${descSub}
                    </td>
                    <td>${pkHtml}</td>
                    <td>${statusBadge}</td>
                    <td>${scheduleHtml}</td>
                    <td>${coordsHtml}</td>
                    <td>${nodesBadge}</td>
                    <td>${lastSeen}</td>
                    <td style="text-align: right; white-space: nowrap;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="openEditBotModal('${escapeHtml(bot.bot_id)}')" title="Redaguoti boto nustatymus">
                            ✏️ Redaguoti
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteBot('${escapeHtml(bot.bot_id)}', '${escapeHtml(bot.name || bot.bot_id)}')" title="Ištrinti botą">
                            🗑️
                        </button>
                    </td>
                </tr>`;
            });

            tbody.innerHTML = html;
        }

        function openCreateBotModal() {
            activeEditBotId = null;
            document.getElementById('editBotTitle').innerText = '➕ Naujas Botas (ESP32 Gateway)';
            document.getElementById('editBotIsNew').value = '1';
            document.getElementById('editBotOriginalId').value = '';

            const idInput = document.getElementById('editBotId');
            idInput.value = '';
            idInput.disabled = false;

            document.getElementById('editBotName').value = '';
            document.getElementById('editBotPublicKey').value = '';
            document.getElementById('editBotLat').value = '';
            document.getElementById('editBotLon').value = '';
            document.getElementById('editBotIntervalHours').value = '';
            document.getElementById('editBotRetryIntervalHours').value = '';
            document.getElementById('editBotHourStart').value = '';
            document.getElementById('editBotHourEnd').value = '';
            document.getElementById('editBotDescription').value = '';
            document.getElementById('editBotEnabled').checked = true;

            openModal('editBotModal');
            updateDocumentTitle();
        }

        function openEditBotModal(botId) {
            const bot = allBotsData.find(b => b.bot_id.toUpperCase() === botId.toUpperCase());
            if (!bot) return;

            activeEditBotId = bot.bot_id;
            document.getElementById('editBotTitle').innerText = `🤖 Boto Nustatymai: ${bot.name || bot.bot_id}`;
            document.getElementById('editBotIsNew').value = '0';
            document.getElementById('editBotOriginalId').value = bot.bot_id;

            const idInput = document.getElementById('editBotId');
            idInput.value = bot.bot_id;
            idInput.disabled = false;

            document.getElementById('editBotName').value = bot.name || '';
            document.getElementById('editBotPublicKey').value = bot.public_key || '';
            document.getElementById('editBotLat').value = bot.latitude || '';
            document.getElementById('editBotLon').value = bot.longitude || '';
            const botIntMins = (bot.telemetry_interval_mins !== null && bot.telemetry_interval_mins !== undefined && parseInt(bot.telemetry_interval_mins, 10) > 0)
                ? parseInt(bot.telemetry_interval_mins, 10)
                : null;
            document.getElementById('editBotIntervalHours').value = botIntMins !== null ? Number((botIntMins / 60).toFixed(2)) : '';
            const botRetryMins = (bot.retry_interval_mins !== null && bot.retry_interval_mins !== undefined && parseInt(bot.retry_interval_mins, 10) > 0)
                ? parseInt(bot.retry_interval_mins, 10)
                : null;
            document.getElementById('editBotRetryIntervalHours').value = botRetryMins !== null ? Number((botRetryMins / 60).toFixed(2)) : '';
            document.getElementById('editBotHourStart').value = (bot.active_hour_start !== null && bot.active_hour_start !== undefined && bot.active_hour_start !== '') ? String(bot.active_hour_start) : '';
            document.getElementById('editBotHourEnd').value = (bot.active_hour_end !== null && bot.active_hour_end !== undefined && bot.active_hour_end !== '') ? String(bot.active_hour_end) : '';
            document.getElementById('editBotDescription').value = bot.description || '';
            document.getElementById('editBotEnabled').checked = (bot.enabled == 1 || bot.enabled === true);

            openModal('editBotModal');
            updateDocumentTitle();
        }

        async function handleSaveBot(e) {
            e.preventDefault();
            const isNew = document.getElementById('editBotIsNew').value === '1';
            const originalBotId = document.getElementById('editBotOriginalId').value;
            const botId = document.getElementById('editBotId').value.trim().toUpperCase();
            const name = document.getElementById('editBotName').value.trim();
            const publicKey = document.getElementById('editBotPublicKey').value.trim();
            const lat = document.getElementById('editBotLat').value;
            const lon = document.getElementById('editBotLon').value;
            const intervalHoursRaw = document.getElementById('editBotIntervalHours').value;
            const intervalHoursVal = intervalHoursRaw !== '' ? parseFloat(intervalHoursRaw) : NaN;
            const retryHoursRaw = document.getElementById('editBotRetryIntervalHours').value;
            const retryHoursVal = retryHoursRaw !== '' ? parseFloat(retryHoursRaw) : NaN;
            const hourStartRaw = document.getElementById('editBotHourStart').value;
            const hourEndRaw = document.getElementById('editBotHourEnd').value;
            const description = document.getElementById('editBotDescription').value.trim();
            const enabled = document.getElementById('editBotEnabled').checked;

            if (!botId) {
                showToast('Privaloma įvesti Boto ID!', 'error');
                return;
            }

            if ((hourStartRaw !== '' && hourEndRaw === '') || (hourStartRaw === '' && hourEndRaw !== '')) {
                showToast('Pasirinkite abi darbo valandas (NUO ir IKI) arba abi palikite „Visą parą (24/7)“!', 'error');
                return;
            }

            const payload = {
                is_new: isNew,
                original_bot_id: originalBotId,
                bot_id: botId,
                name: name,
                public_key: publicKey,
                latitude: lat ? parseFloat(lat) : null,
                longitude: lon ? parseFloat(lon) : null,
                telemetry_interval_mins: (!isNaN(intervalHoursVal) && intervalHoursVal > 0) ? Math.round(intervalHoursVal * 60) : null,
                retry_interval_mins: (!isNaN(retryHoursVal) && retryHoursVal > 0) ? Math.round(retryHoursVal * 60) : null,
                active_hour_start: hourStartRaw !== '' ? parseInt(hourStartRaw, 10) : null,
                active_hour_end: hourEndRaw !== '' ? parseInt(hourEndRaw, 10) : null,
                description: description,
                enabled: enabled
            };

            try {
                const res = await fetch('api.php?action=save_bot', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Nepavyko išsaugoti boto');
                }

                showToast(data.message || 'Botas sėkmingai išsaugotas!', 'success');
                closeModal('editBotModal', false);
                await loadBots();
                await loadStats();
                if (currentTab === 'nodes') {
                    await loadNodes(nodesPage);
                }
            } catch (err) {
                console.error('Klaida saugant botą:', err);
                showToast(err.message, 'error');
            }
        }

        async function quickToggleBot(botId, enable) {
            try {
                const res = await fetch('api.php?action=toggle_bot', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bot_id: botId })
                });
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Nepavyko pakeisti boto būsenos');
                }

                showToast(data.message || 'Būsena pakeista!', 'success');
                await loadBots();
                await loadStats();
            } catch (err) {
                console.error('Klaida perjungiant boto būseną:', err);
                showToast(err.message, 'error');
            }
        }

        async function deleteBot(botId, name) {
            if (!confirm(`Ar tikrai norite ištrinti botą '${botId}' (${name})?\n\nVisi šiam botui priskirti mazgai bus saugiai perleisti numatytajam 'KLP' botui.`)) {
                return;
            }

            try {
                const res = await fetch('api.php?action=delete_bot', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bot_id: botId })
                });
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Nepavyko ištrinti boto');
                }

                showToast(data.message || 'Botas ištrintas!', 'success');
                await loadBots();
                await loadStats();
                if (currentTab === 'nodes') {
                    await loadNodes(nodesPage);
                }
            } catch (err) {
                console.error('Klaida trinant botą:', err);
                showToast(err.message, 'error');
            }
        }

        function filterNodesByBot(botId) {
            const sel = document.getElementById('nodesBotFilter');
            if (sel) {
                sel.value = botId;
            }
            switchTab('nodes');
            onNodesFilterChange();
        }

        function onBotsSearchInput() {
            updateAdminClearBtn('botsSearch');
            renderBotsTable();
        }

        async function loadTelemetry(page = 0) {
            telPage = page;
            const node = document.getElementById('telemetryNodeInput').value.trim();
            const type = document.getElementById('telemetryTypeFilter').value;
            const offset = page * telLimit;

            const url = `api.php?action=telemetry&node_id=${encodeURIComponent(node)}&type=${encodeURIComponent(type)}&limit=${telLimit}&offset=${offset}&sort_by=${encodeURIComponent(telSort.col)}&sort_dir=${telSort.dir}`;
            try {
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) {
                    document.getElementById('telemetryCountBadge').innerText = `${data.total} įrašų`;
                    document.getElementById('telemetryPageInfo').innerText = `Puslapis ${telPage + 1} iš ${Math.max(1, Math.ceil(data.total / telLimit))}`;
                    document.getElementById('btnPrevTel').disabled = (telPage <= 0);
                    document.getElementById('btnNextTel').disabled = (offset + telLimit >= data.total);
                    renderTelemetryTable(data.records || []);
                    updateTableSortHeaders('telemetry');
                }
            } catch (err) {
                console.error(err);
            }
        }

        function changeTelemetryPage(delta) {
            const newPage = Math.max(0, telPage + delta);
            if (newPage !== telPage) {
                telPage = newPage;
                updateHistory({ tab: 'telemetry', page: telPage }, 'push');
                loadTelemetry(telPage);
            }
        }

        function renderTelemetryTable(records) {
            const tbody = document.getElementById('telemetryTableBody');
            if (!records.length) {
                tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; padding: 24px; color: var(--text-dim);">Telemetrijos įrašų nerasta.</td></tr>';
                return;
            }

            tbody.innerHTML = records.map(r => {
                const batt = r.battery_v !== null ? `${parseFloat(r.battery_v).toFixed(2)} V` : '-';
                const temp = r.temperature_c !== null ? `${parseFloat(r.temperature_c).toFixed(1)} °C` : '-';
                const updatedAgo = r.recorded_at ? formatRelativeTime(r.recorded_at) : '-';
                const displayName = r.node_name || r.node_id;
                const editKey = r.public_key || r.node_id || '';

                return `
                    <tr>
                        <td style="font-family: var(--font-mono); font-size: 0.72rem;">#${r.id}</td>
                        <td>
                            <div onclick="openEditNode('${escapeHtml(editKey)}')" style="cursor: pointer; display: inline-block;" title="Paspauskite, kad atidarytumėte mazgo „${escapeHtml(displayName)}“ nustatymus ⚙️">
                                <div style="font-weight: 600; color: #ffffff; text-decoration: underline dotted rgba(167, 139, 250, 0.65); text-underline-offset: 3px;">
                                    ${escapeHtml(displayName)} <span style="font-size: 0.72rem; opacity: 0.75;">⚙️</span>
                                </div>
                                ${r.node_name && r.node_id && r.node_name !== r.node_id ? `
                                    <div style="font-size: 0.70rem; color: #a78bfa; font-family: var(--font-mono);">
                                        ${escapeHtml(r.node_id)}
                                    </div>
                                ` : ''}
                            </div>
                        </td>
                        <td><span class="badge badge-gray">${escapeHtml(r.type)}</span></td>
                        <td><span style="color:#2ed573; font-family:var(--font-mono); font-weight:600;">${batt}</span></td>
                        <td><span style="color:var(--accent-orange); font-family:var(--font-mono);">${temp}</span></td>
                        <td style="font-family: var(--font-mono); font-size: 0.74rem;">
                            ${r.rssi ? `RSSI: ${r.rssi} dBm ` : ''}
                            ${r.snr ? `SNR: ${r.snr} dB` : ''}
                        </td>
                        <td style="color: var(--accent-cyan); font-weight: 500; font-size: 0.76rem;" title="${r.recorded_at ? formatVilniusDate(r.recorded_at) : ''}">${updatedAgo}</td>
                        <td style="font-family: var(--font-mono); font-size: 0.74rem;">
                            RX: ${r.packets_recv ?? '-'} / TX: ${r.packets_sent ?? '-'}
                        </td>
                        <td style="font-size: 0.74rem;">
                            <span title="Lietuvos laiku: ${formatVilniusDate(r.recorded_at)}">${formatVilniusDate(r.recorded_at)}</span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            ${r.raw_packet_id ? `
                                <button class="btn btn-success btn-sm" onclick="copyDebugInfo(${r.raw_packet_id})" title="Nukopijuoti RAW paketo derinimo duomenis į iškarpinę">📋 Debug</button>
                                <button class="btn btn-secondary btn-sm" onclick="inspectPacket(${r.raw_packet_id})">RAW #${r.raw_packet_id}</button>
                            ` : '-'}
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // 3. RAW PAKETAI & DEBUGGER
        function filterRawByNode(nodeId) {
            rawNodeFilter = nodeId;
            rawPage = 0;
            document.getElementById('rawSearchInput').value = nodeId;
            updateAdminClearBtn('rawSearchInput');
            document.getElementById('rawActiveNodeText').innerText = nodeId;
            document.getElementById('rawNodeFilterTag').style.display = 'inline-flex';
            
            currentTab = 'raw';
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById('tabBtn-raw')?.classList.add('active');
            document.getElementById('tab-raw')?.classList.add('active');

            updateHistory({ tab: 'raw', search: nodeId, page: 0, packet: null }, 'push');
            loadRawPackets(0);
        }

        function clearRawNodeFilter() {
            rawNodeFilter = '';
            rawPage = 0;
            const input = document.getElementById('rawSearchInput');
            if (input) {
                input.value = '';
                input.focus();
            }
            updateAdminClearBtn('rawSearchInput');
            document.getElementById('rawNodeFilterTag').style.display = 'none';
            updateHistory({ tab: 'raw', search: '', page: 0 }, 'push');
            loadRawPackets(0);
        }

        async function loadRawPackets(page = 0) {
            rawPage = page;
            const search = document.getElementById('rawSearchInput').value.trim();
            const status = document.getElementById('rawStatusFilter').value;
            const direction = document.getElementById('rawDirectionFilter').value;
            const packetType = document.getElementById('rawTypeFilter') ? document.getElementById('rawTypeFilter').value : '';
            const offset = page * rawLimit;

            const url = `api.php?action=raw_packets&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&direction=${encodeURIComponent(direction)}&packet_type=${encodeURIComponent(packetType)}&limit=${rawLimit}&offset=${offset}&sort_by=${encodeURIComponent(rawSort.col)}&sort_dir=${rawSort.dir}`;
            try {
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) {
                    document.getElementById('rawCountBadge').innerText = `${data.total} paketų`;
                    document.getElementById('rawPageInfo').innerText = `Puslapis ${rawPage + 1} iš ${Math.max(1, Math.ceil(data.total / rawLimit))}`;
                    document.getElementById('btnPrevRaw').disabled = (rawPage <= 0);
                    document.getElementById('btnNextRaw').disabled = (offset + rawLimit >= data.total);
                    renderRawTable(data.packets || []);
                    updateTableSortHeaders('raw');
                }
            } catch (err) {
                console.error(err);
            }
        }

        function changeRawPage(delta) {
            const newPage = Math.max(0, rawPage + delta);
            if (newPage !== rawPage) {
                rawPage = newPage;
                updateHistory({ tab: 'raw', page: rawPage }, 'push');
                loadRawPackets(rawPage);
            }
        }

        function formatRawPacketTypeBadge(p) {
            const rawHex = String(p.raw_hex_preview || p.raw_hex || '').trim();
            let ptCode = (p.packet_type_code !== undefined && p.packet_type_code !== null) ? parseInt(p.packet_type_code, 10) : null;
            let rtCode = (p.route_type_code !== undefined && p.route_type_code !== null) ? parseInt(p.route_type_code, 10) : null;
            let hops = (p.hop_count !== undefined && p.hop_count !== null) ? parseInt(p.hop_count, 10) : null;

            if ((ptCode === null || isNaN(ptCode)) && rawHex.length >= 2) {
                const hdrByte = parseInt(rawHex.substring(0, 2), 16);
                if (!isNaN(hdrByte)) {
                    ptCode = (hdrByte >> 2) & 0x0F;
                    rtCode = hdrByte & 0x03;
                    const pathOffset = (rtCode === 0 || rtCode === 3) ? 10 : 2;
                    if (rawHex.length >= pathOffset + 2) {
                        const pathByte = parseInt(rawHex.substring(pathOffset, pathOffset + 2), 16);
                        if (!isNaN(pathByte)) {
                            hops = pathByte & 0x3F;
                        }
                    }
                }
            }

            const typeMap = {
                0:  { label: '📡 REQ',      cls: 'badge-purple', title: 'REQ (Užklausa: Telemetrija / Status)' },
                1:  { label: '📥 RESPONSE', cls: 'badge-green',  title: 'RESPONSE (Atsakas / Telemetrija)' },
                2:  { label: '💬 TXT_MSG',  cls: 'badge-blue',   title: 'TXT_MSG (Tekstinė žinutė)' },
                3:  { label: '✓ ACK',       cls: 'badge-green',  title: 'ACK (Patvirtinimas)' },
                4:  { label: '📢 ADVERT',   cls: 'badge-cyan',   title: 'ADVERT (Mazgo skelbimas)' },
                5:  { label: '👥 GRP_TXT',  cls: 'badge-gray',   title: 'GRP_TXT (Kanalo / grupės žinutė)' },
                6:  { label: '📦 GRP_DATA', cls: 'badge-gray',   title: 'GRP_DATA (Grupės duomenys)' },
                7:  { label: '🔐 LOGIN',    cls: 'badge-amber',  title: 'ANON_REQ (Anoniminė užklausa / Login)' },
                8:  { label: '🗺️ PATH',     cls: 'badge-blue',   title: 'PATH (Maršruto / Login OK / Telemetrijos atsakas)' },
                9:  { label: '🧭 TRACE',    cls: 'badge-purple', title: 'TRACE (Kelio trasavimas)' },
                10: { label: '🧩 MULTI',    cls: 'badge-gray',   title: 'MULTIPART' },
                11: { label: '🎛️ CTRL',     cls: 'badge-gray',   title: 'CONTROL' },
                15: { label: '🔧 CUSTOM',   cls: 'badge-gray',   title: 'RAW_CUSTOM' }
            };

            if (ptCode === null || isNaN(ptCode)) {
                return '<span style="color:var(--text-dim);">-</span>';
            }

            const info = typeMap[ptCode] || { label: `TYPE_${ptCode}`, cls: 'badge-gray', title: `Payload tipas: ${ptCode}` };
            const isDirect = (rtCode === 2 || rtCode === 3);
            const routeText = isDirect ? 'DIRECT' : 'FLOOD';
            const hopText = (hops !== null && !isNaN(hops)) ? ` · ${hops} hop` : '';

            return `
                <div style="display: flex; flex-direction: column; gap: 2px; align-items: flex-start;">
                    <span class="badge ${info.cls}" style="font-size: 0.70rem; font-weight: 700; cursor: pointer;" onclick="document.getElementById('rawTypeFilter').value='${ptCode}'; onRawFilterChange();" title="${escapeHtml(info.title)} (paspauskite filtruoti)">${info.label}</span>
                    <span style="font-size: 0.66rem; color: var(--text-dim); font-family: var(--font-mono);">${routeText}${hopText}</span>
                </div>
            `;
        }

        function renderRawTable(packets) {
            const tbody = document.getElementById('rawTableBody');
            if (!packets.length) {
                tbody.innerHTML = '<tr><td colspan="11" style="text-align:center; padding: 24px; color: var(--text-dim);">RAW paketų nerasta.</td></tr>';
                return;
            }

            tbody.innerHTML = packets.map(p => {
                const dirBadge = (p.direction === 'TX') 
                    ? '<span class="badge badge-green">TX (Išsiųsta)</span>' 
                    : '<span class="badge badge-cyan">RX (Gauta)</span>';

                const typeBadge = formatRawPacketTypeBadge(p);

                let statusBadge = '<span class="badge badge-green">Apdorota</span>';
                if (p.error_message) {
                    statusBadge = '<span class="badge badge-red">KLAIDA</span>';
                } else if (p.processed == 0 || p.processed === false) {
                    statusBadge = '<span class="badge badge-amber">Eilėje</span>';
                }

                const senderName = p.sender_node_name || p.sender_name || (p.sender_public_key ? p.sender_public_key.substring(0, 8) : '-');
                const senderSub = (p.sender_public_key && (p.sender_node_name || p.sender_name))
                    ? p.sender_public_key.substring(0, 8)
                    : (p.sender_name && p.sender_node_name && p.sender_name !== p.sender_node_name ? p.sender_name : '');
                const senderEditKey = p.sender_public_key || (p.sender_name && p.sender_name !== '-' ? p.sender_name : '');

                const targetName = p.target_node_name || p.target_name || (p.target_public_key ? p.target_public_key.substring(0, 8) : '-');
                const targetSub = (p.target_public_key && (p.target_node_name || p.target_name))
                    ? p.target_public_key.substring(0, 8)
                    : (p.target_name && p.target_node_name && p.target_name !== p.target_node_name ? p.target_name : '');
                const targetEditKey = p.target_public_key || (p.target_name && p.target_name !== '-' ? p.target_name : '');

                return `
                    <tr>
                        <td style="font-family: var(--font-mono); font-size: 0.72rem;">#${p.id}</td>
                        <td><span class="badge badge-purple" style="font-size:0.7rem;">${escapeHtml(p.bot_id || 'KLP')}</span></td>
                        <td>${dirBadge}</td>
                        <td>${typeBadge}</td>
                        <td>
                            ${senderEditKey ? `
                                <div onclick="openEditNode('${escapeHtml(senderEditKey)}')" style="cursor: pointer; display: inline-block;" title="Paspauskite, kad atidarytumėte mazgo „${escapeHtml(senderName)}“ nustatymus ⚙️">
                                    <div style="font-weight: 600; color: #ffffff; text-decoration: underline dotted rgba(167, 139, 250, 0.65); text-underline-offset: 3px;">
                                        ${escapeHtml(senderName)} <span style="font-size: 0.72rem; opacity: 0.75;">⚙️</span>
                                    </div>
                                    ${senderSub ? `<div style="font-size: 0.70rem; color: #a78bfa; font-family: var(--font-mono);">${escapeHtml(senderSub)}</div>` : ''}
                                </div>
                            ` : `
                                <div style="font-weight: 600; color: var(--text-dim);">-</div>
                            `}
                        </td>
                        <td>
                            ${targetEditKey ? `
                                <div onclick="openEditNode('${escapeHtml(targetEditKey)}')" style="cursor: pointer; display: inline-block;" title="Paspauskite, kad atidarytumėte mazgo „${escapeHtml(targetName)}“ nustatymus ⚙️">
                                    <div style="font-weight: 600; color: #ffffff; text-decoration: underline dotted rgba(167, 139, 250, 0.65); text-underline-offset: 3px;">
                                        ${escapeHtml(targetName)} <span style="font-size: 0.72rem; opacity: 0.75;">⚙️</span>
                                    </div>
                                    ${targetSub ? `<div style="font-size: 0.70rem; color: #a78bfa; font-family: var(--font-mono);">${escapeHtml(targetSub)}</div>` : ''}
                                </div>
                            ` : `
                                <div style="font-weight: 600; color: var(--text-dim);">-</div>
                            `}
                        </td>
                        <td style="font-family: var(--font-mono); font-size: 0.72rem;">
                            ${p.length ? p.length + ' B' : '-'} 
                            ${p.rssi ? `| ${p.rssi} dBm` : ''}
                        </td>
                        <td>${statusBadge}</td>
                        <td>
                            ${p.error_message ? `<span style="color:#ff6b81; font-size:0.75rem;" title="${escapeHtml(p.error_message)}">${escapeHtml(p.error_message.substring(0, 45))}...</span>` : '<span style="color:var(--text-dim);">-</span>'}
                        </td>
                        <td style="font-size: 0.74rem;" title="Lietuvos laiku: ${formatVilniusDate(p.created_at)}">${formatVilniusDate(p.created_at)}</td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button class="btn btn-success btn-sm" onclick="copyDebugInfo(${p.id})" title="Nukopijuoti visą paketo procesą ir duomenis derinimui su AI / Dev">📋 Kopijuoti derinimui</button>
                            <button class="btn btn-secondary btn-sm" onclick="inspectPacket(${p.id})">🔍 Peržiūrėti</button>
                            <button class="btn btn-secondary btn-sm" onclick="reprocessPacket(${p.id})">🔄 Reprocess</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // RAW Paketo išsamus langas
        async function inspectPacket(id, pushHistory = true) {
            activePacketId = id;
            activePacketModalId = id;
            if (pushHistory && !isApplyingUrlState) {
                modalOpenedInSession = true;
                updateHistory({ packet: id }, 'push');
            }
            updateDocumentTitle();

            try {
                const res = await fetch(`api.php?action=raw_packet_detail&id=${id}`);
                const data = await res.json();
                if (data.success && data.packet) {
                    const p = data.packet;
                    activePacketData = p;
                    document.getElementById('packetModalTitle').innerText = `RAW Paketas #${p.id} (${p.direction || 'RX'})`;
                    
                    const senderDisp = p.sender_node_name || p.sender_name || (p.sender_public_key ? p.sender_public_key.substring(0, 8) : null);
                    const targetDisp = p.target_node_name || p.target_name || (p.target_public_key ? p.target_public_key.substring(0, 8) : null);
                    const senderModalKey = p.sender_public_key || p.sender_name || '';
                    const targetModalKey = p.target_public_key || p.target_name || '';

                    const metaRow = document.getElementById('packetMetaRow');
                    metaRow.innerHTML = `
                        <span class="badge badge-purple">🤖 Botas: ${escapeHtml(p.bot_id || 'KLP')}</span>
                        ${senderDisp ? `<span class="badge badge-gray" style="cursor: pointer;" onclick="openEditNode('${escapeHtml(senderModalKey)}')" title="Siuntėjas (paspauskite valdyti nustatymus ⚙️)">📤 ${escapeHtml(senderDisp)} ⚙️</span>` : ''}
                        ${targetDisp ? `<span class="badge badge-gray" style="cursor: pointer;" onclick="openEditNode('${escapeHtml(targetModalKey)}')" title="Taikinys (paspauskite valdyti nustatymus ⚙️)">📥 ${escapeHtml(targetDisp)} ⚙️</span>` : ''}
                        <span class="badge badge-blue">Ilgis: ${p.length || 0} B</span>
                        <span class="badge badge-gray">RSSI: ${p.rssi !== null && p.rssi !== undefined ? p.rssi : '-'} dBm</span>
                        <span class="badge badge-gray">SNR: ${p.snr !== null && p.snr !== undefined ? p.snr : '-'} dB</span>
                        <span class="badge badge-${p.is_encrypted ? 'amber' : 'green'}">${p.is_encrypted ? 'Šifruotas' : 'Viešas'}</span>
                    `;

                    const errBox = document.getElementById('packetErrorBox');
                    if (p.error_message) {
                        errBox.innerText = 'Klaidos pranešimas: ' + p.error_message;
                        errBox.style.display = 'block';
                    } else {
                        errBox.style.display = 'none';
                    }

                    document.getElementById('modalRawHex').innerText = p.raw_hex || '';
                    document.getElementById('modalDecodedJson').innerText = p.decoded_json ? (typeof p.decoded_json === 'object' ? JSON.stringify(p.decoded_json, null, 2) : p.decoded_json) : '(tuščia)';
                    document.getElementById('modalPayloadJson').innerText = p.payload_json ? (typeof p.payload_json === 'object' ? JSON.stringify(p.payload_json, null, 2) : p.payload_json) : '(tuščia)';

                    openModal('packetModal');
                } else {
                    alert('Nepavyko gauti paketo duomenų: ' + (data.error || 'Paketas nerastas'));
                    closeModal('packetModal', false);
                }
            } catch (err) {
                alert('Nepavyko gauti paketo duomenų: ' + (err.message || err));
                closeModal('packetModal', false);
            }
        }

        // Generuoja pilną, struktūrizuotą Markdown tekstą derinimui (AI / Dev modeliui)
        function generateDebugBundle(p) {
            if (!p) return '';

            const senderDisp = p.sender_node_name || p.sender_name || 'N/A';
            const targetDisp = p.target_node_name || p.target_name || 'N/A';

            const lines = [];
            lines.push(`### 🐞 MeshCore RAW Paketo Derinimo Duomenys (Paketas #${p.id})`);
            lines.push('');
            lines.push(`- **Paketo ID:** ${p.id}`);
            lines.push(`- **Bot ID (Šaltinis):** ${p.bot_id || 'KLP'}`);
            lines.push(`- **Kryptis:** ${p.direction || 'N/A'}`);
            lines.push(`- **Ilgis:** ${p.length ? p.length + ' B' : 'N/A'}`);
            lines.push(`- **RSSI / SNR:** ${p.rssi !== null && p.rssi !== undefined ? p.rssi + ' dBm' : 'N/A'} / ${p.snr !== null && p.snr !== undefined ? p.snr + ' dB' : 'N/A'}`);
            lines.push(`- **Šifruotas (is_encrypted):** ${p.is_encrypted ? 'TAIP (1)' : 'NE (0)'}`);
            lines.push(`- **Siuntėjas:** ${senderDisp}${p.sender_node_name && p.sender_name && p.sender_node_name !== p.sender_name ? ` (${p.sender_name})` : ''} (Raktas: \`${p.sender_public_key || 'N/A'}\`)`);
            lines.push(`- **Taikinys:** ${targetDisp}${p.target_node_name && p.target_name && p.target_node_name !== p.target_name ? ` (${p.target_name})` : ''} (Raktas: \`${p.target_public_key || 'N/A'}\`)`);
            if (p.my_public_key) {
                lines.push(`- **Mūsų mazgo raktas (my_public_key):** \`${p.my_public_key}\``);
            }
            if (p.shared_secret_hex) {
                lines.push(`- **Shared Secret Hex:** \`${p.shared_secret_hex}\``);
            }
            lines.push(`- **Gavimo data:** ${p.created_at || 'N/A'}`);
            lines.push(`- **Apdorojimo data:** ${p.processed_at || 'N/A'} (baigta: ${p.completed_at || 'N/A'})`);
            lines.push(`- **Apdorojimo statusas (processed):** ${p.processed}`);

            if (p.error_message) {
                lines.push('');
                lines.push(`> ⚠️ **KLAIDOS PRANEŠIMAS (error_message):**`);
                lines.push(`> \`\`\``);
                lines.push(`> ${p.error_message}`);
                lines.push(`> \`\`\``);
            }

            lines.push('');
            lines.push('#### 1. RAW HEX:');
            lines.push('```hex');
            lines.push(p.raw_hex || '');
            lines.push('```');

            lines.push('');
            lines.push('#### 2. Dekoduotas JSON (decoded_json):');
            lines.push('```json');
            lines.push(typeof p.decoded_json === 'object' ? JSON.stringify(p.decoded_json, null, 2) : (p.decoded_json || '{}'));
            lines.push('```');

            if (p.payload_json) {
                lines.push('');
                lines.push('#### 3. Originalus HTTP POST Payload (payload_json):');
                lines.push('```json');
                lines.push(typeof p.payload_json === 'object' ? JSON.stringify(p.payload_json, null, 2) : p.payload_json);
                lines.push('```');
            }

            if (p.telemetry_records && p.telemetry_records.length > 0) {
                lines.push('');
                lines.push('#### 4. Susiję telemetrijos įrašai:');
                lines.push('```json');
                lines.push(JSON.stringify(p.telemetry_records, null, 2));
                lines.push('```');
            }

            return lines.join('\n');
        }

        // Pagalbinė funkcija saugiam teksto nukopijavimui
        async function copyTextToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return await navigator.clipboard.writeText(text);
            }
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            return new Promise((resolve, reject) => {
                try {
                    const successful = document.execCommand('copy');
                    successful ? resolve() : reject(new Error('Iškarpinės prieiga negalima'));
                } catch (e) {
                    reject(e);
                } finally {
                    textArea.remove();
                }
            });
        }

        // Nukopijuoja visą paketo derinimo informaciją į kompiuterio iškarpinę (Clipboard)
        async function copyDebugInfo(id) {
            if (!id) return;
            try {
                let packet = (activePacketData && activePacketData.id == id) ? activePacketData : null;
                if (!packet) {
                    showToast(`Gaunami paketo #${id} duomenys...`);
                    const res = await fetch(`api.php?action=raw_packet_detail&id=${id}`);
                    const data = await res.json();
                    if (data.success && data.packet) {
                        packet = data.packet;
                    } else {
                        alert('Nepavyko gauti paketo duomenų: ' + (data.error || 'Nežinoma klaida'));
                        return;
                    }
                }

                const debugText = generateDebugBundle(packet);
                await copyTextToClipboard(debugText);
                showToast(`📋 Paketo #${id} derinimo duomenys nukopijuoti į iškarpinę! Galite tiesiogiai įklijuoti (Paste) į pokalbį.`, 'success');
            } catch (err) {
                alert('Klaida kopijuojant į iškarpinę: ' + (err.message || err));
            }
        }

        async function reprocessPacket(id) {
            try {
                showToast(`Apdorojamas paketas #${id}...`);
                const res = await fetch('api.php?action=reprocess_packet', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(`Paketas #${id} sėkmingai apdorotas!`, 'success');
                    loadRawPackets(rawPage);
                    loadStats();
                } else {
                    alert('Klaida apdorojant: ' + (data.error || 'Nežinoma'));
                }
            } catch (err) {
                alert('Klaida siunčiant užklausą');
            }
        }

        function triggerReprocessFromModal() {
            if (activePacketId) {
                reprocessPacket(activePacketId).then(() => {
                    inspectPacket(activePacketId, false);
                });
            }
        }

        // ==========================================
        // KARTOTUVŲ TELEMETRIJOS UŽKLAUSŲ EILĖ (/api/repeater)
        // ==========================================
        let currentQueueData = [];
        let queueFilterTimeout = null;

        async function loadNodeQueue(forceRefresh = false) {
            const tableBody = document.getElementById('nodeQueueTableBody');
            const badge = document.getElementById('queueCountBadge');
            if (!tableBody) return;

            if (forceRefresh) {
                tableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">🔄 Atnaujinama kartotuvų užklausų eilė...</td></tr>`;
            }

            const botId = document.getElementById('queueBotFilter')?.value || '';
            const monitoredOnly = document.getElementById('queueMonitoredFilter')?.value ?? '1';
            const search = document.getElementById('queueSearchInput')?.value || '';

            const params = new URLSearchParams();
            if (botId) params.set('bot_id', botId);
            params.set('monitored_only', monitoredOnly);
            if (search) params.set('search', search);

            try {
                const res = await fetch(`api.php?action=node_queue&${params.toString()}`);
                const data = await res.json();

                if (!data.success) {
                    throw new Error(data.error || 'Nepavyko užkrauti eilės duomenų');
                }

                currentQueueData = (data.queue || []).map((item, idx) => {
                    item._originalIndex = idx + 1;
                    return item;
                });

                // Atnaujiname botų pasirinkimą jei dar nėra
                const botSelect = document.getElementById('queueBotFilter');
                if (botSelect && data.bots && Array.isArray(data.bots)) {
                    const currentVal = botSelect.value;
                    const existingOptions = Array.from(botSelect.options).map(o => o.value);
                    data.bots.forEach(b => {
                        if (!existingOptions.includes(b)) {
                            const opt = document.createElement('option');
                            opt.value = b;
                            opt.textContent = `Botas: ${b}`;
                            botSelect.appendChild(opt);
                        }
                    });
                    if (currentVal) botSelect.value = currentVal;
                }

                // Dinamiškai atnaujiname /api/repeater nuorodą su pasirinktu (ar pirmu) bot_id
                const botLink = document.getElementById('btnQueueRepeaterLink');
                if (botLink) {
                    const activeBot = botId || (data.bots && data.bots[0]) || 'KLP';
                    botLink.href = `../api/repeater?bot_id=${encodeURIComponent(activeBot)}`;
                    botLink.title = `Pasižiūrėti sekantį kartotuvą botui '${activeBot}' JSON formatu`;
                }

                // Statistika
                const stats = data.stats || {};
                const qStatTotal = document.getElementById('qStatTotal');
                const qStatPending = document.getElementById('qStatPending');
                const qStatSuccess = document.getElementById('qStatSuccess');
                const qStatRateLimited = document.getElementById('qStatRateLimited');
                const qStatTimeout = document.getElementById('qStatTimeout');
                const qStatBlocked = document.getElementById('qStatBlocked');

                if (qStatTotal) qStatTotal.innerText = stats.total ?? currentQueueData.length;
                if (qStatPending) qStatPending.innerText = stats.pending_first ?? 0;
                const qStatInProgress = document.getElementById('qStatInProgress');
                if (qStatInProgress) qStatInProgress.innerText = stats.in_progress ?? 0;
                if (qStatSuccess) qStatSuccess.innerText = stats.success ?? 0;
                if (qStatRateLimited) qStatRateLimited.innerText = stats.rate_limited ?? 0;
                if (qStatTimeout) qStatTimeout.innerText = stats.timeout ?? 0;
                if (qStatBlocked) qStatBlocked.innerText = stats.blocked ?? 0;
                if (badge) badge.innerText = `${currentQueueData.length} mazgų`;

                renderNodeQueueTable();
            } catch (err) {
                console.error('Klaida kraunant eilę:', err);
                if (tableBody) {
                    tableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--accent-red); padding: 24px;">❌ Klaida užkraunant eilę: ${escapeHtml(err.message)}</td></tr>`;
                }
                if (badge) badge.innerText = 'Klaida';
            }
        }

        function onQueueFilterChange() {
            updateAdminClearBtn('queueSearchInput');
            queuePage = 0;
            renderNodeQueueTable();
            clearTimeout(queueFilterTimeout);
            queueFilterTimeout = setTimeout(() => {
                loadNodeQueue(false);
            }, 250);
        }

        function onQueueStatusFilterChange() {
            queuePage = 0;
            renderNodeQueueTable();
        }

        function changeQueuePage(delta) {
            queuePage = Math.max(0, queuePage + delta);
            renderNodeQueueTable();
        }

        function renderNodeQueueTable() {
            const tbody = document.getElementById('nodeQueueTableBody');
            if (!tbody) return;

            const statusFilter = document.getElementById('queueStatusFilter')?.value || '';
            const searchVal = (document.getElementById('queueSearchInput')?.value || '').toLowerCase().trim();

            let list = [...currentQueueData];

            if (searchVal) {
                list = list.filter(item => {
                    const name = (item.name || item.node_name || '').toLowerCase();
                    const nodeId = (item.node_id || '').toLowerCase();
                    const pubKey = (item.public_key || '').toLowerCase();
                    return name.includes(searchVal) || nodeId.includes(searchVal) || pubKey.includes(searchVal);
                });
            }

            if (statusFilter) {
                if (statusFilter === 'blocked') {
                    list = list.filter(item => item.is_blocked || item.attempt_status === 'blocked');
                } else if (statusFilter === 'rate_limited') {
                    list = list.filter(item => item.is_rate_limited && !item.is_blocked);
                } else if (statusFilter === 'retry_cooldown') {
                    list = list.filter(item => item.is_retry_cooldown && !item.is_blocked);
                } else if (statusFilter === 'timeout') {
                    list = list.filter(item => item.attempt_status === 'timeout' && !item.is_blocked);
                } else {
                    list = list.filter(item => item.attempt_status === statusFilter && !item.is_blocked);
                }
            }

            // Rūšiavimas pagal pasirinktą stulpelį
            if (queueSort && queueSort.col) {
                const dir = (queueSort.dir === 'desc') ? -1 : 1;
                list.sort((a, b) => {
                    if (queueSort.col === 'index') {
                        const valA = a._originalIndex ?? a.queue_pos ?? 999999;
                        const valB = b._originalIndex ?? b.queue_pos ?? 999999;
                        return (valA - valB) * dir;
                    } else if (queueSort.col === 'name') {
                        const valA = (a.name || a.node_name || a.node_id || '').toLowerCase();
                        const valB = (b.name || b.node_name || b.node_id || '').toLowerCase();
                        return valA.localeCompare(valB) * dir;
                    } else if (queueSort.col === 'bot_id') {
                        const valA = (a.bot_id || '').toLowerCase();
                        const valB = (b.bot_id || '').toLowerCase();
                        return valA.localeCompare(valB) * dir;
                    } else if (queueSort.col === 'requested_date') {
                        const valA = a.requested_date ? new Date(a.requested_date).getTime() : 0;
                        const valB = b.requested_date ? new Date(b.requested_date).getTime() : 0;
                        return (valA - valB) * dir;
                    } else if (queueSort.col === 'status') {
                        const valA = (a.attempt_status || '') + '_' + (a.telemetry_fail_count || 0);
                        const valB = (b.attempt_status || '') + '_' + (b.telemetry_fail_count || 0);
                        return valA.localeCompare(valB) * dir;
                    } else if (queueSort.col === 'telemetry') {
                        const valA = a.last_telemetry_at ? new Date(a.last_telemetry_at).getTime() : (a.last_battery_v ? parseFloat(a.last_battery_v) : 0);
                        const valB = b.last_telemetry_at ? new Date(b.last_telemetry_at).getTime() : (b.last_battery_v ? parseFloat(b.last_battery_v) : 0);
                        return (valA - valB) * dir;
                    }
                    return 0;
                });
            }

            const totalCount = list.length;
            const totalPages = Math.max(1, Math.ceil(totalCount / queueLimit));
            if (queuePage >= totalPages) queuePage = Math.max(0, totalPages - 1);
            if (queuePage < 0) queuePage = 0;

            const pageInfo = document.getElementById('queuePageInfo');
            const btnPrev = document.getElementById('btnPrevQueue');
            const btnNext = document.getElementById('btnNextQueue');
            if (pageInfo) pageInfo.innerText = `Puslapis ${queuePage + 1} iš ${totalPages}`;
            if (btnPrev) btnPrev.disabled = (queuePage <= 0);
            if (btnNext) btnNext.disabled = (queuePage >= totalPages - 1);

            updateTableSortHeaders('queue');

            if (!list || list.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 32px; color: var(--text-muted);">
                            Pagal pasirinktus filtrus mazgų eilėje nerasta.
                        </td>
                    </tr>
                `;
                return;
            }

            const pageItems = list.slice(queuePage * queueLimit, (queuePage + 1) * queueLimit);

            tbody.innerHTML = pageItems.map(item => {
                const isBlocked = !!item.is_blocked;
                const isRateLimited = !!item.is_rate_limited && !isBlocked;
                const isInProgress = (item.attempt_status === 'in_progress' || !!item.is_in_progress) && !isBlocked;
                const isRetryCooldown = !!item.is_retry_cooldown && !isBlocked && !isInProgress;
                const failCount = parseInt(item.telemetry_fail_count || 0, 10);
                const maxFails = parseInt(item.max_telemetry_fails || 20, 10);
                const isNext = (!isBlocked && !isRateLimited && !isInProgress && !isRetryCooldown && item.queue_pos === 1);
                const posBadgeClass = isBlocked 
                    ? 'badge-red' 
                    : (isInProgress 
                        ? 'badge-blue' 
                        : (isRateLimited || isRetryCooldown
                            ? 'badge-amber' 
                            : (isNext ? 'badge-green' : (item.queue_pos && item.queue_pos <= 3 ? 'badge-cyan' : 'badge-gray'))));
                const posLabel = isBlocked 
                    ? '⛔' 
                    : (isInProgress 
                        ? '⏳' 
                        : (isRateLimited 
                            ? '⏸️' 
                            : (isRetryCooldown
                                ? '⏳'
                                : (item.queue_pos ? `#${item.queue_pos}` : '-'))));
                const posTitle = isBlocked 
                    ? `Mazgas sustabdytas (${failCount}/${maxFails} fail)` 
                    : (isInProgress
                        ? `Laukiama atsakymo iš LoRa tinklo (užklausta prieš ${Math.round(item.elapsed_req_sec || 0)}s, laukiama iki 180s)`
                        : (isRateLimited 
                            ? `Pauzė: telemetrija atnaujinta neseniai (liko ${item.rate_limit_remaining_mins} min., limitas: ${item.min_interval_mins} min.)` 
                            : (isRetryCooldown
                                ? `Pauzė po nesėkmės (timeout): pakartotinis bandymas po ${item.retry_remaining_mins} min. (limitas: ${item.min_interval_mins} min.)`
                                : (item.queue_pos ? `Eilės pozicija #${item.queue_pos}` : 'Laukiama'))));

                // Mazgo pavadinimas ir identifikatorius
                const nameStr = escapeHtml(item.name || item.node_id);
                const idStr = escapeHtml((item.node_id || '').toUpperCase());
                const keyShort = item.public_key ? escapeHtml(item.public_key.substring(0, 10)) + '...' : '-';
                const isMonitored = item.update;

                // Šaltinis
                const botStr = escapeHtml(item.bot_id || 'KLP');

                // Paskutinė užklausa (requested_date)
                let reqHtml = '';
                if (!item.requested_date) {
                    const subText = isBlocked 
                        ? '<span style="font-size: 0.72rem; color: #ff6b81;">Sustabdytas (neimamas)</span>'
                        : (isRateLimited 
                            ? `<span style="font-size: 0.72rem; color: var(--accent-amber);">Pauzė (liko ${item.rate_limit_remaining_mins} min.)</span>` 
                            : '<span style="font-size: 0.72rem; color: var(--accent-green);">Bus imamas iškart</span>');
                    const badgeClass = isBlocked ? 'badge-red' : (isRateLimited ? 'badge-amber' : 'badge-green');
                    const badgeLabel = isBlocked ? '⛔ NULL' : (isRateLimited ? '⏳ NULL (Pauzė)' : '🚀 NULL (Prioritetas)');
                    reqHtml = `
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            <span class="badge ${badgeClass}" style="font-weight: 700; width: fit-content;" title="Mazgas dar neužklaustas per /api/repeater arba eilė nuresetinta į NULL">
                                ${badgeLabel}
                            </span>
                            ${subText}
                        </div>
                    `;
                } else {
                    const reqDateFormatted = formatVilniusDate(item.requested_date);
                    const rel = formatRelativeTime(item.requested_date_iso || item.requested_date);
                    reqHtml = `
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            <span style="color: #ffffff; font-size: 0.82rem; font-family: var(--font-mono); font-weight: 500;">${escapeHtml(reqDateFormatted)}</span>
                            <span style="color: var(--text-muted); font-size: 0.73rem;">⏱️ ${rel} <span style="color: var(--text-dim); font-size: 0.67rem;">(LT)</span></span>
                        </div>
                    `;
                }

                // Paskutinio bandymo rezultatas
                let attemptBadge = '';
                const priorTelemDate = item.telemetry_updated_at ? formatVilniusDate(item.telemetry_updated_at) : null;
                const priorTelemRel = item.telemetry_updated_at ? formatRelativeTime(item.telemetry_updated_at_iso || item.telemetry_updated_at) : null;

                if (isBlocked || item.attempt_status === 'blocked') {
                    let priorTelemBox = '';
                    if (priorTelemDate) {
                        priorTelemBox = `
                            <div style="margin-top: 4px; padding: 5px 8px; background: rgba(255, 177, 66, 0.12); border-left: 2px solid #ffb142; border-radius: 4px;">
                                <div style="font-size: 0.69rem; color: #ffb142; font-weight: 600;">📡 Paskutinė gauta telemetrija:</div>
                                <div style="font-size: 0.76rem; color: #ffffff; font-family: var(--font-mono); font-weight: 600;">${escapeHtml(priorTelemDate)}</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);">⏱️ ${priorTelemRel} (LT laiku)</div>
                            </div>
                        `;
                    }
                    attemptBadge = `
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            <span class="badge badge-red" style="width: fit-content; background: #c23616;">⛔ Sustabdytas (Fail limitas)</span>
                            <span style="font-size: 0.72rem; color: #ff6b81;">Viršytas limitas: <b>${failCount}/${maxFails} fail</b></span>
                            <span style="font-size: 0.70rem; color: var(--text-muted);">Nebeimamas į /api/repeater užklausas</span>
                            ${priorTelemBox}
                        </div>
                    `;
                } else if (isRateLimited) {
                    attemptBadge = `
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            <span class="badge badge-amber" style="width: fit-content;">⏳ Atnaujinta neseniai (Pauzė)</span>
                            <span style="font-size: 0.72rem; color: var(--accent-amber);">Liko palaukti: <b>${item.rate_limit_remaining_mins} min.</b></span>
                            <span style="font-size: 0.70rem; color: var(--text-muted);">Intervalas: ${item.min_interval_mins} min. Eilėje praleidžiamas.</span>
                            ${priorTelemDate ? `
                                <div style="margin-top: 4px; font-size: 0.71rem; color: var(--text-muted);">
                                    📡 Gauta: <b style="font-family: var(--font-mono); color: #ffffff;">${escapeHtml(priorTelemDate)}</b> (${priorTelemRel})
                                </div>
                            ` : ''}
                        </div>
                    `;
                } else if (item.attempt_status === 'never') {
                    attemptBadge = `
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            <span class="badge badge-green" style="width: fit-content;">🚀 Prioritetas (NULL)</span>
                            <span style="font-size: 0.72rem; color: var(--text-muted);">Laukia 1-os užklausos per /api/repeater</span>
                            ${priorTelemDate ? `
                                <div style="margin-top: 4px; font-size: 0.71rem; color: var(--text-muted);">
                                    📡 Ankstesnė: <b style="font-family: var(--font-mono); color: #ffffff;">${escapeHtml(priorTelemDate)}</b> (${priorTelemRel})
                                </div>
                            ` : ''}
                        </div>
                    `;
                } else if (item.attempt_status === 'success') {
                    attemptBadge = `
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            <span class="badge badge-green" style="width: fit-content;">✅ Gauta</span>
                            <span style="font-size: 0.72rem; color: var(--accent-green);">📶 Telemetrija atsakė į užklausą</span>
                            <div style="font-size: 0.72rem; color: var(--text-muted);" title="Lietuvos laiku">
                                🕒 <span style="font-family: var(--font-mono); color: #ffffff;">${escapeHtml(priorTelemDate)}</span> (${priorTelemRel})
                            </div>
                        </div>
                    `;
                } else if (item.attempt_status === 'in_progress') {
                    const elapsed = item.elapsed_req_sec != null ? Math.round(item.elapsed_req_sec) : 0;
                    const remaining = item.remaining_wait_sec != null ? Math.round(item.remaining_wait_sec) : Math.max(0, 180 - elapsed);
                    attemptBadge = `
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            <span class="badge badge-blue" style="width: fit-content;">⏳ Laukiama...</span>
                            <span style="font-size: 0.72rem; color: var(--accent-cyan);">Užklausta prieš <b>${elapsed}s</b>, laukiama atsakymo (liko ~${remaining}s)</span>
                            <span style="font-size: 0.70rem; color: var(--text-muted);">Pakartotinai į eilę neimamas, kol nesibaigė laukimo langas</span>
                            ${priorTelemDate ? `
                                <div style="margin-top: 4px; font-size: 0.71rem; color: var(--text-muted);">
                                    📡 Paskutinė gauta: <b style="font-family: var(--font-mono); color: #ffffff;">${escapeHtml(priorTelemDate)}</b> (${priorTelemRel})
                                </div>
                            ` : ''}
                        </div>
                    `;
                } else {
                    let priorTelemBox = '';
                    if (priorTelemDate) {
                        priorTelemBox = `
                            <div style="margin-top: 4px; padding: 5px 8px; background: rgba(255, 177, 66, 0.12); border-left: 2px solid #ffb142; border-radius: 4px;">
                                <div style="font-size: 0.69rem; color: #ffb142; font-weight: 600;">📡 Paskutinė gauta telemetrija:</div>
                                <div style="font-size: 0.76rem; color: #ffffff; font-family: var(--font-mono); font-weight: 600;">${escapeHtml(priorTelemDate)}</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);">⏱️ ${priorTelemRel} (LT laiku)</div>
                            </div>
                        `;
                    } else {
                        priorTelemBox = `
                            <div style="font-size: 0.70rem; color: var(--text-dim); margin-top: 2px;">⚠️ Nėra jokios ankstesnės telemetrijos</div>
                        `;
                    }

                    if (isRetryCooldown) {
                        attemptBadge = `
                            <div style="display: flex; flex-direction: column; gap: 3px;">
                                <span class="badge badge-amber" style="width: fit-content;">⏳ Timeout (Pauzė)</span>
                                <span style="font-size: 0.72rem; color: var(--accent-amber);">Pauzė po nesėkmės: <b>liko ${item.retry_remaining_mins} min.</b></span>
                                <span style="font-size: 0.70rem; color: var(--text-muted);">Pakartotinis bandymas po ${item.min_interval_mins || 60} min. Neatsakė (${failCount}x).</span>
                                ${priorTelemBox}
                            </div>
                        `;
                    } else {
                        attemptBadge = `
                            <div style="display: flex; flex-direction: column; gap: 3px;">
                                <span class="badge badge-red" style="width: fit-content;">❌ Timeout ${failCount > 1 ? `(${failCount}x)` : ''}</span>
                                <span style="font-size: 0.72rem; color: #ff6b81;">Neatsakė į paskutinę užklausą (Pauzė baigėsi, laukia eilėje)</span>
                                ${priorTelemBox}
                            </div>
                        `;
                    }
                }

                // Paskutiniai rodmenys & Telemetrijos laikas
                let telemetryHtml = '<span style="color: var(--text-dim); font-size: 0.78rem;">-</span>';
                if (item.battery_v !== null || item.temperature_c !== null || priorTelemDate) {
                    let bColor = 'var(--text-muted)';
                    if (item.battery_v !== null) {
                        bColor = item.battery_v < 3.4 ? '#ff5252' : (item.battery_v < 3.7 ? '#ffb142' : 'var(--accent-green)');
                    }

                    telemetryHtml = `
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                ${item.battery_v !== null ? `<span style="font-size: 0.8rem; font-weight: 600; color: ${bColor};">🔋 ${Number(item.battery_v).toFixed(2)}V</span>` : '<span style="font-size: 0.8rem; color: var(--text-dim);" title="Baterijos duomenų nėra">🔋 -</span>'}
                                ${item.temperature_c !== null ? `<span style="font-size: 0.75rem; color: #ffffff;">🌡️ ${Number(item.temperature_c).toFixed(1)}°C</span>` : '<span style="font-size: 0.75rem; color: var(--text-dim);" title="Temperatūros duomenų nėra">🌡️ -</span>'}
                            </div>
                            ${priorTelemDate ? `
                                <div style="font-size: 0.72rem; color: var(--text-muted);" title="Lietuvos laiku: ${escapeHtml(priorTelemDate)}">
                                    🕒 <span style="font-family: var(--font-mono); color: var(--accent-cyan); font-weight: 500;">${escapeHtml(priorTelemDate)}</span>
                                    <span style="color: var(--text-dim); font-size: 0.68rem;">(${priorTelemRel})</span>
                                </div>
                            ` : '<span style="font-size: 0.7rem; color: var(--text-dim);">Laiko nėra</span>'}
                        </div>
                    `;
                }

                // Veiksmai
                const nodeIdentifier = item.node_id || item.public_key;
                const safeName = (item.name || item.node_id).replace(/'/g, "\\'");
                const nodeFilterId = escapeHtml(item.node_id || (item.public_key ? item.public_key.substring(0, 4) : ''));

                return `
                    <tr style="${isBlocked ? 'background: rgba(232, 65, 24, 0.08);' : (isRateLimited ? 'background: rgba(255, 177, 66, 0.05);' : (isNext ? 'background: rgba(0, 210, 255, 0.04);' : ''))}">
                        <td style="text-align: center;">
                            <span class="badge ${posBadgeClass}" style="font-weight: 700; white-space: nowrap;" title="${escapeHtml(posTitle)}">
                                ${posLabel}
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <span onclick="openEditNode('${escapeHtml(item.public_key || nodeIdentifier)}')" style="font-weight: 600; color: #ffffff; font-size: 0.88rem; cursor: pointer; text-decoration: underline dotted rgba(167, 139, 250, 0.65); text-underline-offset: 3px;" title="Paspauskite, kad atidarytumėte mazgo nustatymus ⚙️">${nameStr} <span style="font-size: 0.72rem; opacity: 0.75;">⚙️</span></span>
                                    <span class="badge badge-blue" style="font-size: 0.7rem; cursor: pointer;" onclick="openEditNode('${escapeHtml(item.public_key || nodeIdentifier)}')" title="Atidaryti mazgo nustatymus">${idStr}</span>
                                    ${isBlocked ? `<span class="badge badge-red" style="font-size: 0.68rem; font-weight: 700; padding: 1px 5px; background: #e84118;" title="Mazgas sustabdytas! Pasiekė ${failCount} fail (limitas: ${maxFails})">⛔ ${failCount} fail (Sustabdytas)</span>` : (failCount > 0 ? `<span class="badge badge-red" style="font-size: 0.68rem; font-weight: 700; padding: 1px 5px; cursor: pointer;" onclick="resetNodeFailCount('${escapeHtml(nodeIdentifier)}', '${safeName}')" title="${failCount} nesėkmingi bandymai. Paspauskite, kad nuresetintumėte į 0.">⚠️ ${failCount} fail 🧹</span>` : '')}
                                    ${isRateLimited ? `<span class="badge badge-amber" style="font-size: 0.68rem; font-weight: 600; padding: 1px 5px;" title="Telemetrijos atnaujinimo intervalas: ${item.min_interval_mins} min. Liko palaukti: ${item.rate_limit_remaining_mins} min.">⏳ Pauzė (${item.rate_limit_remaining_mins} min.)</span>` : ''}
                                    ${!isMonitored ? '<span class="badge badge-gray" style="font-size: 0.65rem;" title="update=0 (Išjungta)">Neaktyvus</span>' : ''}
                                </div>
                                <div style="font-family: var(--font-mono); font-size: 0.7rem; color: var(--text-dim);" title="Viešasis raktas: ${escapeHtml(item.public_key || '')}">
                                    🔑 ${keyShort}
                                </div>
                                <div style="margin-top: 3px; display: flex; gap: 5px; flex-wrap: wrap;">
                                    ${item.last_path === 'DIRECT' 
                                        ? '<span class="badge badge-green" style="font-size: 0.68rem;" title="Tiesioginis 0 šuolių ryšys">🎯 DIRECT (0 šuol.)</span>' 
                                        : (item.last_path 
                                            ? `<span class="badge badge-cyan" style="font-size: 0.68rem;" title="Išmoktas grįžtamasis kelias">🗺️ Kelias: [${escapeHtml(item.last_path)}]</span>` 
                                            : '<span class="badge badge-gray" style="font-size: 0.68rem;" title="Kelias nežinomas, užklausos bus siunčiamos per FLOOD">🌐 FLOOD (tinklas)</span>')}
                                    ${item.skip_login
                                        ? '<span class="badge badge-green" style="font-size: 0.68rem;" title="Kartotuvas jau turi šio boto sesiją (ACL) – Login paketas praleidžiamas (skip_login=true)">🔑 Sesija aktyvi (Skip Login)</span>'
                                        : '<span class="badge badge-amber" style="font-size: 0.68rem;" title="Bus siunčiamas pilnas ANON_REQ Login su slaptažodžiu (skip_login=false)">🔐 Pilnas Login</span>'}
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-purple" title="ESP32 Bot šaltinis">
                                🤖 ${botStr}
                            </span>
                        </td>
                        <td>
                            ${reqHtml}
                        </td>
                        <td>
                            ${attemptBadge}
                        </td>
                        <td>
                            ${telemetryHtml}
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button type="button" class="btn btn-secondary btn-icon" 
                                onclick="resetSingleRequestedDate('${escapeHtml(nodeIdentifier)}', '${safeName}')"
                                title="Nustatyti į eilės pradžią (requested_date = NULL, fail = 0)">
                                🔄
                            </button>
                            ${failCount > 0 ? `
                                <button type="button" class="btn btn-secondary btn-icon" style="margin-left: 3px; color: ${isBlocked ? '#ff6b81' : 'inherit'}; border-color: ${isBlocked ? '#ff6b81' : ''};" 
                                    onclick="resetNodeFailCount('${escapeHtml(nodeIdentifier)}', '${safeName}')" 
                                    title="Nuresetinti nesėkmingų bandymų (fail) skaičių į 0 (${failCount} fail)">
                                    🧹
                                </button>
                            ` : ''}
                            <button type="button" class="btn btn-secondary btn-icon" style="margin-left: 3px;"
                                onclick="filterTelemetryByNode('${nodeFilterId}')" 
                                title="Rodyti telemetrijos įrašus">
                                📊
                            </button>
                            <button type="button" class="btn btn-secondary btn-icon" style="margin-left: 3px;"
                                onclick="filterRawByNode('${nodeFilterId}')" 
                                title="Rodyti RAW paketus">
                                📦
                            </button>
                            ${item.public_key ? `
                                <button type="button" class="btn btn-secondary btn-icon" style="margin-left: 3px;"
                                    onclick="openEditNode('${escapeHtml(item.public_key)}')"
                                    title="Redaguoti mazgo nustatymus">
                                    ⚙️
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            }).join('');
        }

        async function resetSingleRequestedDate(identifier, nodeName) {
            try {
                const res = await fetch('api.php?action=reset_requested_date', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier })
                });
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Nepavyko nuresetinti mazgo užklausos datos');
                }

                showToast(`🚀 ${nodeName || identifier} nukreiptas į eilės pradžią (fail nuresetintas į 0)!`, 'success');
                if (currentTab === 'nodes') {
                    await loadNodes();
                    await loadStats();
                } else if (currentTab === 'queue') {
                    await loadNodeQueue(false);
                } else {
                    await loadNodeQueue(false);
                }
            } catch (err) {
                alert('Klaida: ' + err.message);
            }
        }

        async function resetAllRequestedDates() {
            let botFilter = '';
            let monitoredFilter = false;

            if (currentTab === 'nodes') {
                botFilter = document.getElementById('nodesBotFilter')?.value || '';
                monitoredFilter = (document.getElementById('nodesUpdateFilter')?.value === '1');
            } else {
                botFilter = document.getElementById('queueBotFilter')?.value || '';
                monitoredFilter = (document.getElementById('queueMonitoredFilter')?.value === '1');
            }

            let msg = 'Ar tikrai norite nuresetinti visų mazgų requested_date datą į NULL?\n\nVisi mazgai bus imami nuo pradžių sekantiems telemetrijos atnaujinimams ir jų fail skaičiai bus atstatyti į 0.';
            if (botFilter) {
                msg = `Ar tikrai norite nuresetinti boto "${botFilter}" mazgų requested_date datą į NULL?`;
            }

            if (!confirm(msg)) return;

            try {
                const res = await fetch('api.php?action=reset_all_requested_dates', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        bot_id: botFilter || null,
                        monitored_only: monitoredFilter
                    })
                });
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Nepavyko nuresetinti eilės');
                }

                showToast(data.message || 'Eilė sėkmingai nuresetinta!', 'success');
                if (currentTab === 'nodes') {
                    await loadNodes();
                    await loadStats();
                } else if (currentTab === 'queue') {
                    await loadNodeQueue(false);
                } else {
                    await loadNodeQueue(false);
                }
            } catch (err) {
                alert('Klaida nuresetinant eilę: ' + err.message);
            }
        }

        async function resetNodeFailCount(identifier, nodeName) {
            try {
                const res = await fetch('api.php?action=reset_node_fail_count', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier })
                });
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Nepavyko nuresetinti nesėkmingų bandymų');
                }

                showToast(`🧹 ${nodeName || identifier} fail skaičius atstatytas į 0! Mazgas vėl aktyvus eilėje.`, 'success');
                if (currentTab === 'queue') {
                    await loadNodeQueue(false);
                } else if (currentTab === 'nodes') {
                    await loadNodes();
                    await loadStats();
                } else {
                    await loadNodeQueue(false);
                }
            } catch (err) {
                alert('Klaida: ' + err.message);
            }
        }

        async function resetAllFailCounts() {
            let botFilter = '';
            if (currentTab === 'nodes') {
                botFilter = document.getElementById('nodesBotFilter')?.value || '';
            } else {
                botFilter = document.getElementById('queueBotFilter')?.value || '';
            }

            let msg = 'Ar tikrai norite nuresetinti VISŲ mazgų nesėkmingų bandymų (fail) skaičių į 0?\n\nVisi anksčiau sustabdyti mazgai vėl taps tinkami telemetrijos užklausoms.';
            if (botFilter) {
                msg = `Ar tikrai norite nuresetinti boto "${botFilter}" mazgų fail skaičių į 0?`;
            }

            if (!confirm(msg)) return;

            try {
                const res = await fetch('api.php?action=reset_all_fail_counts', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        bot_id: botFilter || null
                    })
                });
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Nepavyko nuresetinti fail skaičių');
                }

                showToast(data.message || 'Visų mazgų fail skaičius nuresetintas į 0!', 'success');
                if (currentTab === 'queue') {
                    await loadNodeQueue(false);
                } else if (currentTab === 'nodes') {
                    await loadNodes();
                    await loadStats();
                } else {
                    await loadNodeQueue(false);
                }
            } catch (err) {
                alert('Klaida: ' + err.message);
            }
        }

        async function resetActiveEditNodeFail() {
            const key = document.getElementById('editNodePublicKey')?.value;
            const name = document.getElementById('editNodeName')?.value || key;
            if (!key) return;
            await resetNodeFailCount(key, name);
            const failLabel = document.getElementById('editNodeFailLabel');
            if (failLabel) {
                failLabel.innerHTML = `Nesėkmingi bandymai (fail): <span style="color: #2ed573; font-weight: 700;">0</span>`;
            }
        }

        async function resetActiveEditNodeQueue() {
            const key = document.getElementById('editNodePublicKey')?.value;
            const name = document.getElementById('editNodeName')?.value || key;
            if (!key) return;
            await resetSingleRequestedDate(key, name);
            const failLabel = document.getElementById('editNodeFailLabel');
            if (failLabel) {
                failLabel.innerHTML = `Nesėkmingi bandymai (fail): <span style="color: #2ed573; font-weight: 700;">0</span>`;
            }
        }

        // 4. EILĖS APDOROJIMAS
        async function runQueueBatch(limit = 20) {
            const out = document.getElementById('queueOutput');
            out.innerText = `Paleidžiamas ${limit} paketų apdorojimas...`;

            try {
                const res = await fetch('api.php?action=process_queue_batch', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ limit })
                });
                const data = await res.json();
                out.innerText = JSON.stringify(data, null, 2);
                loadStats();
                showToast(`Apdorota: ${data.processed} paketų`, 'success');
            } catch (err) {
                out.innerText = 'Klaida vykdant eilės apdorojimą: ' + err.message;
            }
        }

        // Pagalbinės funkcijos
        function openModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.add('active');
        }

        function closeModal(id, fromUserAction = true) {
            const el = document.getElementById(id);
            if (!el) return;

            el.classList.remove('active');
            if (id === 'packetModal') {
                activePacketModalId = null;
            }
            if (id === 'editNodeModal') {
                activeEditNodeKey = null;
                pendingEditNodeKey = null;
            }
            if (id === 'editBotModal') {
                activeEditBotId = null;
            }

            if (fromUserAction && !isApplyingUrlState) {
                modalOpenedInSession = false;
                updateHistory({
                    tab: currentTab || 'nodes',
                    packet: activePacketModalId,
                    edit_node: activeEditNodeKey,
                    edit_bot: activeEditBotId
                }, 'replace');
            }
            updateDocumentTitle();
        }

        function showToast(msg, type = '') {
            const t = document.getElementById('toastMsg');
            if (!t) return;
            t.className = 'toast-msg ' + type;
            t.innerText = msg;
            t.style.display = 'flex';
            setTimeout(() => { t.style.display = 'none'; }, 3000);
        }

        function copyText(str) {
            if (!str) return;
            navigator.clipboard.writeText(str).then(() => {
                showToast('Nukopijuota į iškarpinę!', 'success');
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
        }

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        function formatRelativeTime(dateStr) {
            if (!dateStr) return 'Nematytas';
            try {
                let timestamp;
                if (typeof dateStr === 'string' && (dateStr.includes('T') || dateStr.includes('+') || dateStr.includes('Z'))) {
                    timestamp = new Date(dateStr).getTime();
                } else if (typeof dateStr === 'string') {
                    timestamp = new Date(dateStr.replace(' ', 'T')).getTime();
                } else if (dateStr instanceof Date) {
                    timestamp = dateStr.getTime();
                } else {
                    timestamp = Number(dateStr);
                }
                if (isNaN(timestamp)) return dateStr;
                const now = Date.now();
                const diffSec = Math.max(0, Math.floor((now - timestamp) / 1000));
                if (diffSec < 60) return `prieš ${diffSec}s`;
                if (diffSec < 3600) return `prieš ${Math.floor(diffSec / 60)} min.`;
                if (diffSec < 86400) {
                    const hrs = Math.floor(diffSec / 3600);
                    const mins = Math.floor((diffSec % 3600) / 60);
                    return mins > 0 ? `prieš ${hrs} val. ${mins} min.` : `prieš ${hrs} val.`;
                }
                const days = Math.floor(diffSec / 86400);
                const hrs = Math.floor((diffSec % 86400) / 3600);
                return hrs > 0 ? `prieš ${days} d. ${hrs} val.` : `prieš ${days} d.`;
            } catch (e) {
                return dateStr;
            }
        }

        // Užtikrina laiko rodymą Lietuvos laiko zonoje (Europe/Vilnius)
        function formatVilniusDate(dateStr, includeSeconds = true) {
            if (!dateStr) return '-';
            try {
                // Jei formatas jau "YYYY-MM-DD HH:mm:ss" be laiko zonos, jis jau yra Lietuvos laiku
                if (typeof dateStr === 'string' && /^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/.test(dateStr.trim())) {
                    const clean = dateStr.trim().replace('T', ' ');
                    return includeSeconds ? clean : clean.substring(0, 16);
                }
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr;
                return d.toLocaleString('lt-LT', {
                    timeZone: 'Europe/Vilnius',
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: includeSeconds ? '2-digit' : undefined,
                    hour12: false
                }).replace(',', '');
            } catch (e) {
                return dateStr;
            }
        }

        function formatUptime(seconds) {
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const mins = Math.floor((seconds % 3600) / 60);
            let res = '';
            if (days > 0) res += `${days}d `;
            if (hours > 0 || days > 0) res += `${hours}val. `;
            res += `${mins}min.`;
            return res;
        }

        // ==========================================
        // SISTEMOS NUSTATYMAI & GEOLOKACIJOS FILTRAS
        // ==========================================
        let currentSettings = null;

        async function loadSettings(showFeedback = false) {
            const statusBadge = document.getElementById('settingFilterStatusBadge');
            const feedback = document.getElementById('settingsFeedback');
            if (statusBadge) {
                statusBadge.className = 'badge badge-gray';
                statusBadge.innerText = 'Kraunama...';
            }

            try {
                const res = await fetch('api.php?action=settings');
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Nepavyko užkrauti nustatymų');
                }

                currentSettings = data.settings || {};
                const stats = data.stats || {};

                // Užpildome formos laukus
                const enabledEl = document.getElementById('settingFilterLtEnabled');
                const noCoordsEl = document.getElementById('settingFilterLtIncludeNoCoords');
                const latMinEl = document.getElementById('settingLtLatMin');
                const latMaxEl = document.getElementById('settingLtLatMax');
                const lonMinEl = document.getElementById('settingLtLonMin');
                const lonMaxEl = document.getElementById('settingLtLonMax');
                const maxFailsEl = document.getElementById('settingMaxTelemetryFails');
                const minIntervalEl = document.getElementById('settingMinTelemetryIntervalHours');
                const minRetryIntervalEl = document.getElementById('settingMinRetryIntervalHours');
                const chartDaysEl = document.getElementById('settingTelemetryChartDays');
                const maxTelemAgeDaysEl = document.getElementById('settingMaxTelemetryAgeDays');

                if (enabledEl) enabledEl.checked = !!currentSettings.filter_lithuania_enabled;
                if (noCoordsEl) noCoordsEl.checked = !!currentSettings.filter_lithuania_include_no_coords;
                if (latMinEl) latMinEl.value = currentSettings.lithuania_lat_min ?? 53.8500;
                if (latMaxEl) latMaxEl.value = currentSettings.lithuania_lat_max ?? 56.4500;
                if (lonMinEl) lonMinEl.value = currentSettings.lithuania_lon_min ?? 20.9000;
                if (lonMaxEl) lonMaxEl.value = currentSettings.lithuania_lon_max ?? 26.8500;
                if (maxFailsEl) maxFailsEl.value = currentSettings.max_telemetry_fails ?? 20;
                if (minIntervalEl) minIntervalEl.value = Number(((currentSettings.min_telemetry_interval_mins ?? 60) / 60).toFixed(2));
                if (minRetryIntervalEl) minRetryIntervalEl.value = Number(((currentSettings.min_retry_interval_mins ?? 60) / 60).toFixed(2));
                if (chartDaysEl) chartDaysEl.value = currentSettings.telemetry_chart_days ?? 7;
                if (maxTelemAgeDaysEl) maxTelemAgeDaysEl.value = currentSettings.max_telemetry_age_days ?? 0;

                const apiTtlEl = document.getElementById('settingApiCacheTtlMins');
                if (apiTtlEl) apiTtlEl.value = currentSettings.api_cache_ttl_mins ?? 5;
                const apiTelemLimitEl = document.getElementById('settingApiNodeTelemetryLimit') || document.getElementById('settingApiNodeTelemetryDays');
                if (apiTelemLimitEl) {
                    apiTelemLimitEl.value = currentSettings.api_node_telemetry_limit ?? (currentSettings.api_node_telemetry_days && currentSettings.api_node_telemetry_days > 14 ? currentSettings.api_node_telemetry_days : 50);
                }

                const autoAddRepEl = document.getElementById('settingAutoAddNewRepeaters');
                if (autoAddRepEl) autoAddRepEl.checked = !!currentSettings.auto_add_new_repeaters;

                const sourceRoutingEl = document.getElementById('settingEnableSourceRouting');
                if (sourceRoutingEl) sourceRoutingEl.checked = currentSettings.enable_source_routing !== false;

                // Statistika
                const stTotal = document.getElementById('stTotalNodes');
                const stLt = document.getElementById('stLtNodes');
                const stOutside = document.getElementById('stOutsideNodes');
                const stNoCoords = document.getElementById('stNoCoordsNodes');

                if (stTotal) stTotal.innerText = stats.total_nodes ?? '-';
                if (stLt) stLt.innerText = stats.nodes_in_lt ?? '-';
                if (stOutside) stOutside.innerText = stats.nodes_outside_lt ?? '-';
                if (stNoCoords) stNoCoords.innerText = stats.nodes_no_coords ?? '-';

                if (statusBadge) {
                    if (currentSettings.filter_lithuania_enabled) {
                        statusBadge.className = 'badge badge-green';
                        statusBadge.innerText = '✅ FILTRAS AKTYVUS';
                    } else {
                        statusBadge.className = 'badge badge-gray';
                        statusBadge.innerText = '⚪ FILTRAS IŠJUNGTAS';
                    }
                }

                if (showFeedback && feedback) {
                    feedback.style.color = 'var(--accent-green)';
                    feedback.innerText = 'Nustatymai atnaujinti.';
                    setTimeout(() => { if (feedback) feedback.innerText = ''; }, 3000);
                }
            } catch (err) {
                console.error('Klaida kraunant nustatymus:', err);
                if (statusBadge) {
                    statusBadge.className = 'badge badge-red';
                    statusBadge.innerText = 'Klaida';
                }
                if (feedback) {
                    feedback.style.color = 'var(--accent-red)';
                    feedback.innerText = 'Klaida: ' + err.message;
                }
            }
        }

        async function handleSaveSettings(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveSettings');
            const feedback = document.getElementById('settingsFeedback');
            const originalBtnHtml = btn ? btn.innerHTML : '';

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span>⏳ Saugoma...</span>';
            }
            if (feedback) {
                feedback.style.color = 'var(--text-muted)';
                feedback.innerText = 'Išsaugoma...';
            }

            try {
                const payload = {
                    enable_source_routing: document.getElementById('settingEnableSourceRouting')?.checked ?? true,
                    filter_lithuania_enabled: document.getElementById('settingFilterLtEnabled').checked,
                    filter_lithuania_include_no_coords: document.getElementById('settingFilterLtIncludeNoCoords').checked,
                    lithuania_lat_min: parseFloat(document.getElementById('settingLtLatMin').value),
                    lithuania_lat_max: parseFloat(document.getElementById('settingLtLatMax').value),
                    lithuania_lon_min: parseFloat(document.getElementById('settingLtLonMin').value),
                    lithuania_lon_max: parseFloat(document.getElementById('settingLtLonMax').value),
                    max_telemetry_fails: parseInt(document.getElementById('settingMaxTelemetryFails')?.value || '20', 10),
                    min_telemetry_interval_mins: Math.max(0, Math.round(parseFloat(document.getElementById('settingMinTelemetryIntervalHours')?.value || '1') * 60)),
                    min_retry_interval_mins: Math.max(1, Math.round(parseFloat(document.getElementById('settingMinRetryIntervalHours')?.value || '1') * 60)),
                    telemetry_chart_days: parseInt(document.getElementById('settingTelemetryChartDays')?.value || '7', 10),
                    max_telemetry_age_days: parseInt(document.getElementById('settingMaxTelemetryAgeDays')?.value || '0', 10),
                    api_cache_ttl_mins: parseInt(document.getElementById('settingApiCacheTtlMins')?.value || '5', 10),
                    api_node_telemetry_limit: parseInt((document.getElementById('settingApiNodeTelemetryLimit') || document.getElementById('settingApiNodeTelemetryDays'))?.value || '50', 10),
                    api_node_telemetry_days: parseInt((document.getElementById('settingApiNodeTelemetryLimit') || document.getElementById('settingApiNodeTelemetryDays'))?.value || '50', 10),
                    auto_add_new_repeaters: document.getElementById('settingAutoAddNewRepeaters')?.checked ?? false
                };

                const res = await fetch('api.php?action=save_settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Nepavyko išsaugoti');
                }

                if (feedback) {
                    feedback.style.color = 'var(--accent-green)';
                    feedback.innerText = '✅ Nustatymai sėkmingai išsaugoti!';
                }

                await loadSettings();
                setTimeout(() => {
                    if (feedback && feedback.innerText.includes('sėkmingai')) feedback.innerText = '';
                }, 4000);
            } catch (err) {
                console.error('Klaida saugant nustatymus:', err);
                if (feedback) {
                    feedback.style.color = 'var(--accent-red)';
                    feedback.innerText = '❌ Klaida: ' + err.message;
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                }
            }
        }

        async function clearApiCache() {
            if (!confirm('Ar tikrai norite išvalyti visą viešo API kešą?\n\nKitos /api/node/list ir /node/{public_key} užklausos duomenis nuskaitys tiesiai iš duomenų bazės.')) return;
            try {
                const res = await fetch('api.php?action=clear_api_cache');
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nepavyko išvalyti kešo');
                showToast(data.message || 'API kešas sėkmingai išvalytas!', 'success');
            } catch (err) {
                alert('Klaida valant kešą: ' + err.message);
            }
        }

        function resetDefaultLtBounds() {
            document.getElementById('settingLtLatMin').value = '53.8500';
            document.getElementById('settingLtLatMax').value = '56.4500';
            document.getElementById('settingLtLonMin').value = '20.9000';
            document.getElementById('settingLtLonMax').value = '26.8500';
        }

        // =========================================================================
        // 🌐 API UŽKLAUSŲ ŽURNALAS (API LOGS)
        // =========================================================================
        const debouncedLoadApiLogs = debounce(() => {
            apiLogsPage = 0;
            updateHistory({ tab: 'apilogs', page: 0 }, 'replace');
            loadApiLogs(0);
        }, 300);

        function onApiLogsSearchInput() {
            updateAdminClearBtn('apiLogsSearchInput');
            updateAdminClearBtn('apiLogsIpInput');
            debouncedLoadApiLogs();
        }

        function onApiLogsFilterChange() {
            apiLogsPage = 0;
            updateHistory({ tab: 'apilogs', page: 0 }, 'push');
            loadApiLogs(0);
        }

        function clearApiLogsSearch() {
            const el = document.getElementById('apiLogsSearchInput');
            if (el) el.value = '';
            updateAdminClearBtn('apiLogsSearchInput');
            onApiLogsFilterChange();
        }

        function clearApiLogsIpFilter() {
            const el = document.getElementById('apiLogsIpInput');
            if (el) el.value = '';
            updateAdminClearBtn('apiLogsIpInput');
            onApiLogsFilterChange();
        }

        function filterApiLogsByIp(ip) {
            const el = document.getElementById('apiLogsIpInput');
            if (el) el.value = ip || '';
            updateAdminClearBtn('apiLogsIpInput');
            closeModal('apiLogModal', false);
            apiLogsPage = 0;
            updateHistory({ tab: 'apilogs', ip: ip || '', page: 0 }, 'push');
            loadApiLogs(0);
        }

        function quickFilterApiLogs(type) {
            const ctSelect = document.getElementById('apiLogsClientTypeFilter');
            const stSelect = document.getElementById('apiLogsStatusFilter');
            if (type === 'all') {
                resetApiLogsFilters();
                return;
            } else if (type === 'errors') {
                if (ctSelect) ctSelect.value = 'all';
                if (stSelect) stSelect.value = 'err';
            } else {
                if (ctSelect) ctSelect.value = type;
                if (stSelect) stSelect.value = 'all';
            }
            onApiLogsFilterChange();
        }

        function resetApiLogsFilters() {
            if (document.getElementById('apiLogsSearchInput')) document.getElementById('apiLogsSearchInput').value = '';
            if (document.getElementById('apiLogsIpInput')) document.getElementById('apiLogsIpInput').value = '';
            if (document.getElementById('apiLogsEndpointFilter')) document.getElementById('apiLogsEndpointFilter').value = 'all';
            if (document.getElementById('apiLogsClientTypeFilter')) document.getElementById('apiLogsClientTypeFilter').value = 'all';
            if (document.getElementById('apiLogsBotFilter')) document.getElementById('apiLogsBotFilter').value = 'all';
            if (document.getElementById('apiLogsMethodFilter')) document.getElementById('apiLogsMethodFilter').value = 'all';
            if (document.getElementById('apiLogsStatusFilter')) document.getElementById('apiLogsStatusFilter').value = 'all';
            updateAllAdminClearBtns();
            onApiLogsFilterChange();
        }

        function toggleTopIpsPanel() {
            const list = document.getElementById('apiLogsTopIpsList');
            if (!list) return;
            list.style.display = (list.style.display === 'none') ? 'flex' : 'none';
        }

        function changeApiLogsPage(delta) {
            const next = apiLogsPage + delta;
            if (next < 0) return;
            loadApiLogs(next);
            updateHistory({ tab: 'apilogs', page: next }, 'push');
        }

        function getClientTypeBadge(clientType, botId) {
            let badge = '';
            if (clientType === 'ESP32') {
                badge = `<span class="badge badge-green" title="ESP32 / Arduino įrenginys">🤖 ESP32</span>`;
            } else if (clientType === 'Browser') {
                badge = `<span class="badge badge-blue" title="Vartotojo naršyklė">🖥️ Naršyklė</span>`;
            } else if (clientType === 'Crawler/Bot') {
                badge = `<span class="badge badge-red" title="Svetimas paieškos ar skenavimo botas">🕷️ Crawler/Bot</span>`;
            } else if (clientType === 'CLI/Script') {
                badge = `<span class="badge badge-amber" title="Skriptas arba CLI įrankis (curl, python, kt.)">💻 Skriptas/CLI</span>`;
            } else if (clientType === 'Empty-UA') {
                badge = `<span class="badge badge-amber" title="Klientas neatsiuntė jokio User-Agent">❓ Tuščias UA</span>`;
            } else {
                badge = `<span class="badge badge-gray">${escapeHtml(clientType || 'Unknown')}</span>`;
            }

            if (botId) {
                badge += ` <span class="badge badge-purple" title="Bot ID: ${escapeHtml(botId)}">📡 ${escapeHtml(botId)}</span>`;
            }
            return badge;
        }

        function formatBytes(bytes) {
            const b = parseInt(bytes, 10);
            if (isNaN(b) || b <= 0) return '0 B';
            if (b < 1024) return b + ' B';
            return (b / 1024).toFixed(1) + ' KB';
        }

        async function loadApiLogs(page = 0) {
            apiLogsPage = page;
            updateTableSortHeaders('apilogs');

            const search = document.getElementById('apiLogsSearchInput')?.value || '';
            const ip = document.getElementById('apiLogsIpInput')?.value || '';
            const endpoint = document.getElementById('apiLogsEndpointFilter')?.value || 'all';
            const clientType = document.getElementById('apiLogsClientTypeFilter')?.value || 'all';
            const botId = document.getElementById('apiLogsBotFilter')?.value || 'all';
            const method = document.getElementById('apiLogsMethodFilter')?.value || 'all';
            const status = document.getElementById('apiLogsStatusFilter')?.value || 'all';
            const offset = page * apiLogsLimit;

            const url = `api.php?action=api_logs&search=${encodeURIComponent(search)}&ip=${encodeURIComponent(ip)}&endpoint=${encodeURIComponent(endpoint)}&client_type=${encodeURIComponent(clientType)}&bot_id=${encodeURIComponent(botId)}&method=${encodeURIComponent(method)}&status=${encodeURIComponent(status)}&limit=${apiLogsLimit}&offset=${offset}&sort_by=${encodeURIComponent(apiLogsSort.col)}&sort_dir=${apiLogsSort.dir}`;

            try {
                const res = await fetch(url);
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.error || 'Nepavyko užkrauti API užklausų');
                }

                // Atnaujiname statistiką
                const st = data.stats || {};
                if (document.getElementById('apiLogStatTotal')) document.getElementById('apiLogStatTotal').innerText = st.total ?? 0;
                if (document.getElementById('apiLogStatUniqueIps')) document.getElementById('apiLogStatUniqueIps').innerText = st.unique_ips ?? 0;
                if (document.getElementById('apiLogStatEsp32')) document.getElementById('apiLogStatEsp32').innerText = st.esp32_count ?? 0;
                if (document.getElementById('apiLogStatBrowser')) document.getElementById('apiLogStatBrowser').innerText = st.browser_count ?? 0;
                if (document.getElementById('apiLogStatBotsScripts')) document.getElementById('apiLogStatBotsScripts').innerText = st.bot_script_count ?? 0;
                if (document.getElementById('apiLogStatErrors')) document.getElementById('apiLogStatErrors').innerText = st.error_count ?? 0;

                // Atnaujiname Top IP juostą
                const topIpsList = document.getElementById('apiLogsTopIpsList');
                if (topIpsList) {
                    const topIps = st.top_ips || [];
                    if (topIps.length === 0) {
                        topIpsList.innerHTML = `<span style="font-size: 0.78rem; color: var(--text-dim);">Nėra užregistruotų IP adresų.</span>`;
                    } else {
                        const activeIpFilter = (document.getElementById('apiLogsIpInput')?.value || '').trim();
                        topIpsList.innerHTML = topIps.map(item => {
                            const ipAddr = item.ip_address || 'Nežinomas';
                            const isSelected = activeIpFilter && activeIpFilter === ipAddr;
                            const ct = item.main_client_type || 'Unknown';
                            let icon = '🌐';
                            if (ct === 'ESP32') icon = '🤖';
                            else if (ct === 'Browser') icon = '🖥️';
                            else if (ct === 'Crawler/Bot') icon = '🕷️';
                            else if (ct === 'CLI/Script') icon = '💻';

                            return `
                                <button type="button"
                                    onclick="filterApiLogsByIp('${escapeHtml(isSelected ? '' : ipAddr)}')"
                                    class="btn btn-sm ${isSelected ? '' : 'btn-secondary'}"
                                    style="font-family: var(--font-mono); font-size: 0.74rem; padding: 3px 9px;"
                                    title="Paskutinis UA: ${escapeHtml(item.sample_ua || 'nėra')} | Matytas: ${formatVilniusDate(item.last_seen_at)}">
                                    <span>${icon} ${escapeHtml(ipAddr)}</span>
                                    <span class="badge ${ct === 'ESP32' ? 'badge-green' : (ct === 'Browser' ? 'badge-blue' : 'badge-amber')}" style="font-size: 0.66rem; padding: 1px 5px; margin-left: 4px;">${item.req_count}</span>
                                    ${item.last_bot_id ? `<span class="badge badge-purple" style="font-size: 0.64rem; padding: 1px 4px;">${escapeHtml(item.last_bot_id)}</span>` : ''}
                                </button>
                            `;
                        }).join('');
                    }
                }

                const total = data.total_filtered || 0;
                const badge = document.getElementById('apiLogsCountBadge');
                if (badge) badge.innerText = `${total} užklausų`;

                const totalPages = Math.max(1, Math.ceil(total / apiLogsLimit));
                const pageInfo = document.getElementById('apiLogsPageInfo');
                if (pageInfo) pageInfo.innerText = `Puslapis ${page + 1} iš ${totalPages} (Rasta: ${total})`;

                const btnPrev = document.getElementById('btnPrevApiLogs');
                const btnNext = document.getElementById('btnNextApiLogs');
                if (btnPrev) btnPrev.disabled = (page <= 0);
                if (btnNext) btnNext.disabled = ((page + 1) >= totalPages);

                renderApiLogsTable(data.items || []);
            } catch (err) {
                console.error('Klaida kraunant API logus:', err);
                const tbody = document.getElementById('apiLogsTableBody');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 24px; color: var(--accent-red);">Klaida: ${escapeHtml(err.message)}</td></tr>`;
                }
            }
        }

        function renderApiLogsTable(items) {
            const tbody = document.getElementById('apiLogsTableBody');
            if (!tbody) return;

            if (!items || items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 28px; color: var(--text-muted);">Pagal pasirinktus filtrus API užklausų nerasta.</td></tr>`;
                return;
            }

            tbody.innerHTML = items.map(row => {
                const statusCode = parseInt(row.status_code || 200, 10);
                let statusBadgeClass = 'badge-green';
                if (statusCode >= 500) statusBadgeClass = 'badge-red';
                else if (statusCode >= 400) statusBadgeClass = 'badge-amber';

                const methodBadgeClass = row.method === 'POST' ? 'badge-purple' : 'badge-cyan';
                const uaText = row.user_agent ? escapeHtml(row.user_agent) : '<span style="color: var(--text-dim); font-style: italic;">(nėra User-Agent)</span>';
                const uaShort = row.user_agent && row.user_agent.length > 70
                    ? escapeHtml(row.user_agent.substring(0, 70)) + '…'
                    : uaText;

                const respPreview = row.response_preview ? escapeHtml(row.response_preview) : '';
                const respShort = respPreview.length > 95 ? respPreview.substring(0, 95) + '…' : respPreview;

                const reqPreview = row.request_preview ? escapeHtml(row.request_preview) : '';
                const reqShort = reqPreview.length > 75 ? reqPreview.substring(0, 75) + '…' : reqPreview;

                const isSuspicious = ['Crawler/Bot', 'CLI/Script', 'Empty-UA'].includes(row.client_type);

                return `
                    <tr style="${statusCode >= 400 ? 'background: rgba(220, 53, 69, 0.06);' : (isSuspicious ? 'background: rgba(255, 193, 7, 0.04);' : '')}">
                        <td style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--text-muted);">#${row.id}</td>
                        <td style="font-family: var(--font-mono); font-size: 0.74rem; white-space: nowrap;">
                            ${formatVilniusDate(row.created_at)}
                        </td>
                        <td>
                            <span
                                onclick="filterApiLogsByIp('${escapeHtml(row.ip_address || '')}')"
                                style="font-family: var(--font-mono); font-size: 0.8rem; font-weight: 700; color: var(--accent-cyan); cursor: pointer; text-decoration: underline dotted;"
                                title="Paspauskite, kad nufiltruotumėte visas užklausas iš šio IP (${escapeHtml(row.ip_address || '')})">
                                ${escapeHtml(row.ip_address || '-')}
                            </span>
                        </td>
                        <td style="max-width: 280px;">
                            <div style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap; margin-bottom: 3px;">
                                ${getClientTypeBadge(row.client_type, row.bot_id)}
                            </div>
                            <div style="font-family: var(--font-mono); font-size: 0.7rem; color: var(--text-secondary); word-break: break-all;" title="${escapeHtml(row.user_agent || '')}">
                                ${uaShort}
                            </div>
                        </td>
                        <td style="max-width: 250px;">
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                                <span class="badge ${methodBadgeClass}" style="font-size: 0.68rem;">${escapeHtml(row.method || 'GET')}</span>
                                <strong style="font-family: var(--font-mono); font-size: 0.78rem; color: #ffffff;">${escapeHtml(row.endpoint || '')}</strong>
                            </div>
                            <div style="font-family: var(--font-mono); font-size: 0.7rem; color: var(--text-muted); word-break: break-all;" title="${escapeHtml(row.request_uri || '')}">
                                ${escapeHtml(row.request_uri || '')}
                            </div>
                        </td>
                        <td style="white-space: nowrap;">
                            <span class="badge ${statusBadgeClass}">${statusCode}</span>
                            <span style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted); margin-left: 4px;">
                                ${row.duration_ms !== null && row.duration_ms !== undefined ? row.duration_ms + ' ms' : ''}
                            </span>
                            <div style="font-family: var(--font-mono); font-size: 0.68rem; color: var(--text-dim); margin-top: 2px;">
                                📦 ${formatBytes(row.response_size)}
                            </div>
                        </td>
                        <td style="max-width: 310px;">
                            ${reqShort ? `<div style="font-family: var(--font-mono); font-size: 0.68rem; color: #c084fc; margin-bottom: 2px;" title="POST Body">📥 ${reqShort}</div>` : ''}
                            <div style="font-family: var(--font-mono); font-size: 0.71rem; color: var(--text-secondary); word-break: break-all; cursor: pointer;" onclick="inspectApiLog(${row.id})" title="Paspauskite, kad peržiūrėtumėte pilną JSON atsakymą">
                                📤 ${respShort || '<span style="color:var(--text-dim);">(tuščia)</span>'}
                            </div>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button class="btn btn-secondary btn-sm" onclick="inspectApiLog(${row.id})">🔍 Detaliau</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function formatJsonPretty(rawStr) {
            if (!rawStr) return '(tuščia)';
            try {
                const parsed = typeof rawStr === 'object' ? rawStr : JSON.parse(rawStr);
                return JSON.stringify(parsed, null, 2);
            } catch (e) {
                return String(rawStr);
            }
        }

        async function inspectApiLog(id) {
            try {
                const res = await fetch(`api.php?action=api_log_detail&id=${id}`);
                const data = await res.json();
                if (!data.success || !data.item) {
                    throw new Error(data.error || 'Įrašas nerastas');
                }

                const item = data.item;
                document.getElementById('apiLogModalTitle').innerText = `🌐 API Užklausa #${item.id} (${item.method} ${item.endpoint})`;

                const statusCode = parseInt(item.status_code || 200, 10);
                const statusBadgeClass = statusCode >= 500 ? 'badge-red' : (statusCode >= 400 ? 'badge-amber' : 'badge-green');

                document.getElementById('apiLogMetaRow').innerHTML = `
                    <span class="badge badge-cyan" style="font-family: var(--font-mono);">🌍 IP: ${escapeHtml(item.ip_address || '-')}</span>
                    ${getClientTypeBadge(item.client_type, item.bot_id)}
                    <span class="badge ${statusBadgeClass}">HTTP ${statusCode}</span>
                    <span class="badge badge-gray">⏱️ ${item.duration_ms ?? 0} ms</span>
                    <span class="badge badge-gray">📦 ${formatBytes(item.response_size)}</span>
                    <span class="badge badge-gray">🕒 ${formatVilniusDate(item.created_at)}</span>
                `;

                document.getElementById('apiLogFullUri').innerText = `${item.method} ${item.request_uri || item.endpoint}`;
                document.getElementById('apiLogUserAgent').innerText = item.user_agent || '(tuščias User-Agent)';

                const reqSec = document.getElementById('apiLogRequestSection');
                if (item.request_body && item.request_body.trim() !== '') {
                    reqSec.style.display = 'block';
                    document.getElementById('apiLogRequestBody').innerText = formatJsonPretty(item.request_body);
                } else {
                    reqSec.style.display = 'none';
                }

                document.getElementById('apiLogResponseBody').innerText = formatJsonPretty(item.response_body);
                document.getElementById('apiLogHeadersJson').innerText = formatJsonPretty(item.headers_json);

                const btnIp = document.getElementById('btnFilterByModalIp');
                if (btnIp) {
                    btnIp.onclick = () => filterApiLogsByIp(item.ip_address || '');
                }

                openModal('apiLogModal');
            } catch (err) {
                alert('Nepavyko užkrauti API užklausos detalių: ' + err.message);
            }
        }

        async function clearApiLogs(mode = 'all') {
            const msg = mode === 'older_24h'
                ? 'Ar tikrai norite ištrinti visus API žurnalo įrašus, senesnius nei 24 valandos?'
                : 'Ar tikrai norite išvalyti VISĄ API užklausų žurnalą?';
            if (!confirm(msg)) return;

            try {
                const res = await fetch('api.php?action=clear_api_logs', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mode })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Nepavyko išvalyti API logų');
                showToast(data.message || 'API žurnalas išvalytas!', 'success');
                loadApiLogs(0);
            } catch (err) {
                alert('Klaida valant API žurnalą: ' + err.message);
            }
        }

        // Uždarymas paspaudus už modal ribų arba paspaudus Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openPacketModal = document.getElementById('packetModal')?.classList.contains('active');
                const openEditModal = document.getElementById('editNodeModal')?.classList.contains('active');
                const openBotModal = document.getElementById('editBotModal')?.classList.contains('active');
                const openApiLogModal = document.getElementById('apiLogModal')?.classList.contains('active');
                if (openPacketModal) closeModal('packetModal', true);
                else if (openEditModal) closeModal('editNodeModal', true);
                else if (openBotModal) closeModal('editBotModal', true);
                else if (openApiLogModal) closeModal('apiLogModal', true);
            }
        });

        ['packetModal', 'editNodeModal', 'editBotModal', 'apiLogModal'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('click', (e) => {
                    if (e.target === el) {
                        closeModal(id, true);
                    }
                });
            }
        });

        // Naršyklės Atgal / Pirmyn (Back / Forward) palaikymas
        window.addEventListener('popstate', (e) => {
            <?php if ($isLoggedIn): ?>
                modalOpenedInSession = false;
                applyUrlState(false);
            <?php endif; ?>
        });

        // Paleidimas užsikrovus / atnaujinus puslapį (Refresh)
        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($isLoggedIn): ?>
                ['nodes', 'telemetry', 'raw', 'queue', 'apilogs'].forEach(t => updateTableSortHeaders(t));
                applyUrlState(true);
                updateAllAdminClearBtns();
                loadBots();
                loadStats();
            <?php endif; ?>
        });
    </script>
</body>
</html>
