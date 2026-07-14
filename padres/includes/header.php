<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0f1117">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="../manifest.json">
    <title><?= $pageTitle ?> · Padres · GCA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #d4af37;
            --gold-dark: #b8962e;
            --gold-light: #f0d060;
            --gold-glow: rgba(212,175,55,.08);
            --radius: 16px;
            --radius-sm: 10px;

            --bg-body: #f5f3ee;
            --bg-card: #ffffff;
            --bg-card-hover: #fcfbfa;
            --bg-surface: #f8f7f4;
            --bg-input: #fff;
            --bg-dark: #0f1117;
            --surface: #ffffff;
            --surface-alt: #f8f7f4;
            --text-primary: #1a1a1a;
            --text-secondary: #666;
            --text-muted: #999;
            --border: #ece8e0;
            --border-input: #ddd;
            --form-bg: #fff;
            --alert-bg: #fef2f2;
            --alert-color: #b91c1c;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,.06);
        }

        body.dark-mode {
            --bg-body: #0f1117;
            --bg-card: #1a1d27;
            --bg-card-hover: #1e2130;
            --bg-surface: #1a1d27;
            --bg-input: #222639;
            --bg-dark: #0a0b10;
            --surface: #1a1d27;
            --surface-alt: #1a1d27;
            --text-primary: #e8e6e0;
            --text-secondary: #aaa;
            --text-muted: #777;
            --border: #2a2d3a;
            --border-input: #333750;
            --form-bg: #1a1d27;
            --alert-bg: rgba(220,38,38,.12);
            --alert-color: #f87171;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.2);
            --shadow-md: 0 4px 16px rgba(0,0,0,.3);
            color-scheme: dark;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-body);
            color: var(--text-primary);
            padding: 0;
            margin: 0;
            padding-top: 64px;
            padding-bottom: env(safe-area-inset-bottom, 0px);
            -webkit-font-smoothing: antialiased;
            transition: background .35s ease, color .35s ease;
        }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(212,175,55,.25); border-radius: 2px; }

        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes fadeInUp {
            from { transform: translateY(12px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .app-header {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            background: var(--bg-dark);
            border-bottom: 2px solid var(--gold);
            padding: 0 16px;
            padding-top: env(safe-area-inset-top, 0px);
            height: 64px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding-bottom: 10px;
            animation: slideDown .3s ease;
        }

        .app-header-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .app-header-brand img {
            height: 32px;
            filter: brightness(1.1);
        }

        .app-header-brand span {
            font-family: 'Cormorant Garamond', serif;
            color: var(--gold);
            font-size: 18px;
            font-weight: 700;
            letter-spacing: .3px;
        }

        .app-header-brand small {
            display: block;
            color: rgba(255,255,255,0.4);
            font-size: 8px;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-family: 'Outfit', sans-serif;
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-header {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 36px;
            height: 36px;
            border: 1px solid rgba(212,175,55,0.2);
            border-radius: 50%;
            color: var(--gold);
            font-size: 15px;
            text-decoration: none;
            transition: all .2s;
            background: transparent;
        }

        .btn-header:hover {
            background: rgba(212,175,55,0.08);
            border-color: rgba(212,175,55,0.35);
            color: var(--gold-light);
        }

        .btn-header.logout {
            width: auto;
            border-radius: 50px;
            padding: 0 14px;
            font-size: 12px;
            font-weight: 500;
            gap: 4px;
        }
        .btn-header.logout i { font-size: 14px; }

        .app-content {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px 16px;
            animation: fadeInUp .4s ease;
        }

        .app-card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: transform .2s, box-shadow .2s, border-color .25s;
        }

        .app-card:active { transform: scale(.98); }

        .app-card-header { padding: 18px 20px 0; }
        .app-card-body { padding: 16px 20px 20px; }

        .student-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 18px;
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            text-decoration: none;
            transition: all .25s cubic-bezier(.4,0,.2,1);
            cursor: pointer;
        }

        .student-card:hover {
            border-color: var(--gold);
            box-shadow: 0 4px 20px rgba(212,175,55,0.12);
            transform: translateY(-1px);
        }

        .student-card:active { transform: scale(.97); }

        .student-avatar {
            width: 48px; height: 48px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            display: flex; align-items: center; justify-content: center;
            color: #000; font-size: 20px; flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(212,175,55,0.25);
        }

        .student-info { flex: 1; min-width: 0; }
        .student-info h6 {
            font-weight: 700; color: var(--text-primary);
            margin: 0; font-size: 15px;
        }
        .student-info p {
            margin: 2px 0 0; font-size: 12px; color: var(--text-secondary);
        }

        .student-info .badge-curso {
            display: inline-block;
            background: rgba(212,175,55,0.1);
            color: var(--gold-dark);
            font-size: 10px; font-weight: 600;
            padding: 2px 10px; border-radius: 20px;
            margin-top: 4px;
        }

        .student-chevron { color: #ccc; font-size: 18px; flex-shrink: 0; transition: transform .2s; }
        .student-card:hover .student-chevron { transform: translateX(3px); color: var(--gold); }

        .grade-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }
        .grade-card:last-child { border-bottom: none; }
        .grade-card:hover { background: var(--surface-alt); }

        .grade-card .subject-name {
            font-weight: 500; color: var(--text-primary); font-size: 14px;
        }

        .grade-badge {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px; flex-shrink: 0;
        }
        .grade-badge.high { background: rgba(25,135,84,0.12); color: #198754; }
        .grade-badge.medium { background: rgba(255,193,7,0.15); color: #997404; }
        .grade-badge.low { background: rgba(220,53,69,0.1); color: #dc3545; }

        .avg-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 20px; border-radius: 50px;
            font-weight: 700; font-size: 18px;
        }
        .avg-pill.high { background: rgba(25,135,84,0.1); color: #198754; }
        .avg-pill.medium { background: rgba(255,193,7,0.12); color: #997404; }
        .avg-pill.low { background: rgba(220,53,69,0.1); color: #dc3545; }

        .period-tabs {
            display: flex; gap: 8px; overflow-x: auto;
            -webkit-overflow-scrolling: touch; padding-bottom: 4px;
            scrollbar-width: none;
        }
        .period-tabs::-webkit-scrollbar { display: none; }

        .period-tab {
            flex-shrink: 0; padding: 10px 20px; border-radius: 50px;
            font-size: 13px; font-weight: 600;
            border: 1.5px solid var(--border);
            background: var(--surface); color: var(--text-secondary);
            cursor: pointer; transition: all .2s;
            text-decoration: none; font-family: 'Outfit', sans-serif;
        }
        .period-tab:hover { border-color: var(--gold); color: var(--gold-dark); }
        .period-tab.active {
            background: var(--bg-dark); border-color: var(--gold); color: var(--gold);
        }
        .period-tab i { margin-right: 6px; }

        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px; font-weight: 700; color: var(--text-primary);
            margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
        }
        .section-title i { color: var(--gold); }

        .empty-state { text-align: center; padding: 40px 20px; }
        .empty-state i { font-size: 44px; color: var(--text-muted); margin-bottom: 10px; display: block; }
        .empty-state h5 { font-family: 'Cormorant Garamond', serif; color: var(--text-primary); font-weight: 700; }
        .empty-state p { color: var(--text-muted); font-size: 13px; margin-bottom: 0; }

        .student-header { text-align: center; padding: 20px 0 24px; }
        .student-header .big-avatar {
            width: 64px; height: 64px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            display: flex; align-items: center; justify-content: center;
            color: #000; font-size: 28px; margin: 0 auto 10px;
            box-shadow: 0 6px 20px rgba(212,175,55,0.25);
        }
        .student-header h4 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700; color: var(--text-primary); margin-bottom: 2px;
        }
        .student-header p { color: var(--text-secondary); font-size: 13px; margin: 0; }

        .text-gold { color: var(--gold-dark); }
        .fw-medium { font-weight: 500; }

        /* ── Toast notification ── */
        .toast-gca {
            position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
            background: var(--bg-dark); color: var(--gold);
            padding: 12px 24px; border-radius: 50px;
            font-size: 13px; font-weight: 600;
            box-shadow: 0 8px 30px rgba(0,0,0,.25);
            z-index: 1000;
            display: flex; align-items: center; gap: 8px;
            animation: fadeInUp .3s ease;
            border: 1px solid rgba(212,175,55,.2);
            max-width: 90%;
            white-space: nowrap;
        }

        .btn-gca-pdf {
            display: inline-flex; align-items: center; gap: 8px;
            color: #fff; background: var(--gold-dark);
            padding: 10px 24px; border-radius: 12px;
            text-decoration: none; font-weight: 600; font-size: 14px;
            transition: all .2s; border: none;
        }
        .btn-gca-pdf:hover { background: #b8951f; color: #fff; }

        @media (max-width: 480px) {
            body { padding-top: 56px; }
            .app-header { height: 56px; padding: 0 12px; padding-top: env(safe-area-inset-top, 0px); padding-bottom: 8px; }
            .app-content { padding: 16px 12px; }
            .app-header-brand span { font-size: 16px; }
            .app-header-brand img { height: 26px; }
            .app-header-brand small { display: none; }
        }

        @media (min-width: 768px) {
            .app-content { max-width: 640px; padding: 28px 24px; }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>
        .ts-wrapper .ts-control {
            border-radius: 10px; padding: 10px 14px; border: 1.5px solid var(--border-input);
            font-size: 14px; min-height: 46px; display: flex; align-items: center;
            background: var(--bg-input); color: var(--text-primary);
            font-family: 'Outfit', sans-serif;
            transition: border-color .2s, box-shadow .2s, background .25s;
        }
        .ts-wrapper.focus .ts-control {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px var(--gold-glow);
        }
        .ts-wrapper .ts-dropdown {
            border: 1px solid var(--border); border-radius: 12px;
            background: var(--bg-card);
            box-shadow: 0 8px 40px rgba(0,0,0,.15);
            margin-top: 4px; overflow: hidden;
            font-family: 'Outfit', sans-serif;
        }
        .ts-wrapper .ts-dropdown .option {
            padding: 10px 14px; font-size: 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
        }
        .ts-wrapper .ts-dropdown .option.active { background: var(--gold-glow); }
        .ts-wrapper .ts-dropdown .option.selected {
            background: rgba(201,162,77,.15); font-weight: 600;
        }
        .ts-wrapper .ts-dropdown .option.selected::after { content: ' ✓'; color: var(--gold-dark); font-weight: 700; }
    </style>
    <!-- ── Dark Mode overrides ── -->
    <style>
        .dark-mode table, .dark-mode .table, .dark-mode .card, .dark-mode .gca-card,
        .dark-mode .card-form, .dark-mode .stats-card, .dark-mode .modal-content,
        .dark-mode .list-group-item, .dark-mode .dropdown-menu, .dark-mode .accordion-item,
        .dark-mode .accordion-button, .dark-mode .page-link, .dark-mode .page-item.disabled .page-link,
        .dark-mode .ts-control, .dark-mode .ts-dropdown, .dark-mode .form-control, .dark-mode .form-select {
            background-color: var(--bg-surface); color: var(--text-primary); border-color: var(--border);
        }
        .dark-mode .table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: rgba(0,0,0,.15); color: var(--text-primary);
        }
        .dark-mode .table-hover > tbody > tr:hover > * {
            background-color: rgba(212,175,55,.06); color: var(--text-primary);
        }
        .dark-mode .accordion-button:not(.collapsed) {
            background-color: rgba(212,175,55,.08); color: var(--gold);
        }
        .dark-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        .dark-mode .page-link { background-color: var(--bg-surface); border-color: var(--border); color: var(--text-primary); }
        .dark-mode .page-link:hover { background-color: rgba(212,175,55,.1); color: var(--gold); }
        .dark-mode .page-item.active .page-link { background-color: var(--gold); border-color: var(--gold); color: #000; }
        .dark-mode .page-item.disabled .page-link { background-color: rgba(0,0,0,.2); color: var(--text-muted); }
        .dark-mode .alert { background-color: var(--alert-bg); color: var(--alert-color); border-color: var(--border); }
        .dark-mode .ts-dropdown { background: var(--bg-surface); border-color: var(--border); }
        .dark-mode .ts-dropdown .option.active { background: rgba(212,175,55,.12); color: var(--gold); }
        .dark-mode .ts-wrapper .ts-control { background: var(--bg-surface); color: var(--text-primary); border-color: var(--border); }
        .dark-mode .ts-wrapper.multi .ts-control > div { background: rgba(212,175,55,.1); border-color: rgba(212,175,55,.25); color: var(--text-primary); }
        .dark-mode .ts-wrapper.multi .ts-control > div .remove { border-color: rgba(212,175,55,.15); color: var(--text-muted); }
        .dark-mode .ts-wrapper.multi .ts-control > div .remove:hover { background: rgba(212,175,55,.15); }
        .dark-mode .grade-card { border-color: var(--border); }
        .dark-mode .grade-card:hover { background: var(--surface-alt); }
        .dark-mode .empty-state i { color: var(--text-muted); }
    </style>
    <!-- ── Glow-up: aparición escalonada + realce (panel de padres) ── -->
    <style>
        .app-content > * { animation: fadeInUp .45s ease both; }
        .app-content > *:nth-child(1){ animation-delay:.03s; }
        .app-content > *:nth-child(2){ animation-delay:.09s; }
        .app-content > *:nth-child(3){ animation-delay:.15s; }
        .app-content > *:nth-child(4){ animation-delay:.21s; }
        .app-content > *:nth-child(5){ animation-delay:.27s; }

        .student-card { animation: fadeInUp .45s ease both; }
        .student-card:nth-child(1){ animation-delay:.05s; }
        .student-card:nth-child(2){ animation-delay:.12s; }
        .student-card:nth-child(3){ animation-delay:.19s; }
        .student-card:nth-child(4){ animation-delay:.26s; }
        .student-card:nth-child(5){ animation-delay:.33s; }

        .app-card { transition: transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s, border-color .25s; }
        .app-card:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,.07); }

        .period-tab { transition: all .22s cubic-bezier(.22,1,.36,1); }
        .period-tab:hover { transform: translateY(-1px); }
        .grade-badge { transition: transform .2s ease; }
        .grade-card:hover .grade-badge { transform: scale(1.08); }

        @media (prefers-reduced-motion: reduce) {
            .app-content > *, .student-card { animation: none !important; }
            .app-card:hover, .period-tab:hover, .grade-card:hover .grade-badge { transform: none; }
        }
    </style>
</head>