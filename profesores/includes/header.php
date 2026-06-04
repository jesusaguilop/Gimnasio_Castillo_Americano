<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Profesor · GCA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --gold: #d4af37;
            --gold-dark: #b8962e;
            --gold-light: #f0d060;
            --gold-glow: rgba(212,175,55,.08);
            --sidebar-w: 280px;

            --bg-body: #f5f3ee;
            --bg-card: #ffffff;
            --bg-card-hover: #fcfbfa;
            --bg-surface: #f8f7f4;
            --bg-input: #fff;
            --bg-dark: #0f1117;
            --bg-sidebar: #0a0a0a;
            --bg-sidebar-surface: #111111;
            --text-primary: #1a1a1a;
            --text-sidebar: #f0ede6;
            --text-secondary: #555;
            --text-muted: #888;
            --border-color: #ece8e0;
            --border-input: #ddd;
            --border-light: rgba(0,0,0,.06);
            --shadow-card: 0 1px 3px rgba(0,0,0,.02);
            --shadow-card-hover: 0 4px 16px rgba(0,0,0,.04);
            --topbar-bg: rgba(255,255,255,.85);
            --table-header-bg: #f8f7f4;
            --table-row-border: #f0ede8;
            --form-bg: #fff;
        }

        body.dark-mode {
            --bg-body: #0f1117;
            --bg-card: #1a1d27;
            --bg-card-hover: #1e2130;
            --bg-surface: #1a1d27;
            --bg-input: #222639;
            --bg-dark: #0a0b10;
            --bg-sidebar: #0a0b10;
            --bg-sidebar-surface: #11131c;
            --text-primary: #e8e6e0;
            --text-sidebar: #e8e6e0;
            --text-secondary: #aaa;
            --text-muted: #777;
            --border-color: #2a2d3a;
            --border-input: #333750;
            --border-light: rgba(255,255,255,.04);
            --shadow-card: 0 1px 3px rgba(0,0,0,.2);
            --shadow-card-hover: 0 4px 20px rgba(0,0,0,.3);
            --topbar-bg: rgba(15,17,23,.9);
            --table-header-bg: #1a1d27;
            --table-row-border: #252836;
            --form-bg: #1a1d27;
        }

        body { transition: background .25s, color .25s; }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-body);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(212, 175, 55, 0.3); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(212, 175, 55, 0.5); }

        #sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--bg-dark);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: transform .3s cubic-bezier(.4, 0, .2, 1);
            border-right: var(--border-gold);
        }

        #sidebar.collapsed { transform: translateX(-100%); }

        .sidebar-logo {
            padding: 24px 20px 16px;
            border-bottom: var(--border-gold);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-logo img { height: 42px; filter: brightness(1.1); }

        .sidebar-logo-text { line-height: 1.2; }
        .sidebar-logo-text span {
            display: block;
            font-family: 'Cormorant Garamond', serif;
            color: var(--gold);
            font-size: 16px;
            letter-spacing: .3px;
        }
        .sidebar-logo-text small {
            color: var(--text-muted);
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .admin-badge {
            margin: 14px 16px;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.08), rgba(212, 175, 55, 0.03));
            border: 1px solid rgba(212, 175, 55, 0.18);
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .admin-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
            background: linear-gradient(180deg, var(--gold), var(--gold-dark));
            border-radius: 0 2px 2px 0;
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-size: 16px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.25);
        }

        .admin-info { min-width: 0; }
        .admin-info span {
            display: block;
            color: var(--text-sidebar);
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .admin-info small {
            color: var(--text-muted);
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .greeting-badge {
            font-size: 9px;
            color: var(--gold);
            letter-spacing: 0.5px;
            margin-top: 1px;
            display: block;
        }

        .sidebar-nav {
            flex: 1;
            padding: 4px 0 12px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .sidebar-nav::-webkit-scrollbar { width: 3px; }

        .nav-section-label {
            padding: 16px 20px 4px;
            font-size: 9px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: rgba(212, 175, 55, 0.35);
            font-weight: 600;
        }

        .nav-link-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: #b0ada6;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-left: 3px solid transparent;
            transition: all .2s;
            text-decoration: none;
        }

        .nav-link-item:hover {
            color: var(--gold);
            background: rgba(212, 175, 55, 0.05);
        }

        .nav-link-item.active {
            color: var(--gold);
            border-left-color: var(--gold);
            background: rgba(212, 175, 55, 0.07);
        }

        .nav-link-item .nav-icon {
            font-size: 17px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: 12px 16px;
            border-top: var(--border-gold);
        }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 14px;
            background: transparent;
            border: 1px solid rgba(212, 175, 55, 0.12);
            border-radius: 10px;
            color: var(--text-muted);
            font-size: 13px;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
            font-family: 'Outfit', sans-serif;
        }

        .btn-logout:hover {
            background: rgba(212, 175, 55, 0.06);
            color: var(--gold);
            border-color: rgba(212, 175, 55, 0.3);
        }

        #main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            transition: margin-left .3s cubic-bezier(.4, 0, .2, 1);
            min-height: 100vh;
        }

        #main.expanded { margin-left: 0; }

        .topbar {
            background: var(--topbar-bg);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-light);
        }

        .toggle-btn {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 6px 8px;
            border-radius: 10px;
            transition: all .2s;
        }
        .toggle-btn:hover { background: rgba(0, 0, 0, 0.05); color: var(--text-primary); }

        .breadcrumb-bar { flex: 1; }
        .breadcrumb-bar h5 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 19px;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .breadcrumb-bar h5 i { color: var(--gold); }
        .breadcrumb-bar p {
            font-size: 11px;
            color: var(--text-muted);
            margin: 0;
            letter-spacing: .3px;
        }

        .content-area { padding: 28px 32px; flex: 1; }

        #overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 99;
        }

        .gca-card {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-card);
            transition: box-shadow .2s, background .25s;
        }
        .gca-card:hover { box-shadow: var(--shadow-card-hover); }

        .gca-table thead th {
            background: var(--table-header-bg);
            color: var(--text-secondary);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            border: none;
            padding: 12px 14px;
        }
        .gca-table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            color: var(--text-primary);
            border-bottom: 1px solid var(--table-row-border);
        }
        .gca-table tbody tr:last-child td { border-bottom: none; }
        .gca-table tbody tr:hover { background: var(--bg-card-hover); }

        .btn-gca {
            background: var(--bg-dark);
            color: var(--gold);
            border: 1px solid rgba(212, 175, 55, 0.3);
            padding: 9px 18px;
            font-weight: 600;
            border-radius: 10px;
            transition: all .25s;
            font-size: 13.5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-family: 'Outfit', sans-serif;
        }
        .btn-gca:hover {
            background: var(--gold);
            color: #000;
            border-color: var(--gold);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(212, 175, 55, 0.25);
        }

        .btn-gca-sm {
            padding: 5px 12px;
            font-size: 12px;
            border-radius: 8px;
        }

        .btn-outline-gca {
            background: transparent;
            color: #555;
            border: 1px solid #ddd;
            padding: 9px 18px;
            font-weight: 500;
            border-radius: 10px;
            transition: all .2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-family: 'Outfit', sans-serif;
        }
        .btn-outline-gca:hover {
            border-color: var(--gold);
            color: var(--gold-dark);
            background: rgba(212, 175, 55, 0.04);
        }

        .form-control, .form-select {
            border-radius: 10px; padding: 10px 14px; border: 1.5px solid var(--border-input);
            background: var(--bg-input); color: var(--text-primary);
            transition: border-color .2s, box-shadow .2s, background .25s;
        }
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
        .grade-input { background: rgba(212,175,55,0.06) !important; border-color: var(--gold) !important; }
        .grade-input:focus { background: var(--bg-input) !important; box-shadow: 0 0 0 3px rgba(212,175,55,0.2) !important; }
        .dark-mode .grade-input { background: rgba(212,175,55,0.1) !important; }
        .dark-mode .grade-input:focus { background: var(--bg-input) !important; }
        .form-control::placeholder { color: var(--text-muted); }
        .form-control:focus, .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px var(--gold-glow);
            background: var(--form-bg);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.12);
        }

        .card-form {
            border-radius: 18px;
            border: none;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
        }

        .header-box { text-align: center; margin-bottom: 24px; }
        .header-box img { height: 64px; margin-bottom: 10px; }
        .header-box h4 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .header-box span {
            color: var(--gold-dark);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .section-header h4 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-header h4 i { color: var(--gold); }

        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 42px; color: var(--text-muted); margin-bottom: 10px; display: block; }

        .stat-card {
            background: var(--bg-card);
            border-radius: 14px;
            padding: 20px 22px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: box-shadow .2s, transform .2s;
        }
        .stat-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .stat-icon.gold { background: rgba(212, 175, 55, 0.12); color: var(--gold-dark); }
        .stat-icon.blue { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .stat-icon.green { background: rgba(25, 135, 84, 0.1); color: #198754; }
        .stat-icon.purple { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }

        .stat-info h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 26px;
            font-weight: 700;
            margin: 0;
            color: var(--text-primary);
        }
        .stat-info p {
            margin: 0;
            font-size: 12px;
            color: var(--text-muted);
            letter-spacing: .3px;
        }

        .quick-card {
            background: var(--bg-card);
            border-radius: 14px;
            padding: 28px 24px;
            border: 1px solid var(--border-color);
            text-align: center;
            transition: box-shadow .2s, transform .2s;
            text-decoration: none;
            display: block;
        }
        .quick-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            transform: translateY(-3px);
        }
        .quick-card i {
            font-size: 36px;
            color: var(--gold);
            margin-bottom: 12px;
            display: block;
        }
        .quick-card h6 {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .quick-card p {
            font-size: 12px;
            color: var(--text-muted);
            margin: 0;
        }

        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.open {
                transform: translateX(0);
            }
            #main { margin-left: 0 !important; }
            #overlay.show { display: block; opacity: 1; pointer-events: all; }
            .content-area { padding: 18px 14px; }
            .topbar { padding: 8px 14px; }
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
            border: 1px solid var(--border-color); border-radius: 12px;
            background: var(--bg-card);
            box-shadow: 0 8px 40px rgba(0,0,0,.15);
            margin-top: 4px; overflow: hidden;
            font-family: 'Outfit', sans-serif;
        }
        .ts-wrapper .ts-dropdown .option {
            padding: 10px 14px; font-size: 14px;
            border-bottom: 1px solid var(--table-row-border);
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
        .dark-mode,
        [data-bs-theme="dark"] {
            color-scheme: dark;
        }
        .dark-mode table,
        .dark-mode .table,
        .dark-mode .card,
        .dark-mode .gca-card,
        .dark-mode .card-form,
        .dark-mode .stats-card,
        .dark-mode .modal-content,
        .dark-mode .list-group-item,
        .dark-mode .dropdown-menu,
        .dark-mode .accordion-item,
        .dark-mode .accordion-button,
        .dark-mode .page-link,
        .dark-mode .page-item.disabled .page-link,
        .dark-mode .ts-control,
        .dark-mode .ts-dropdown,
        .dark-mode .form-control,
        .dark-mode .form-select {
            background-color: var(--bg-surface);
            color: var(--text-primary);
            border-color: var(--border-color);
        }
        .dark-mode .table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: rgba(0,0,0,.15);
            color: var(--text-primary);
        }
        .dark-mode .table-hover > tbody > tr:hover > * {
            background-color: rgba(212,175,55,.06);
            color: var(--text-primary);
        }
        .dark-mode .accordion-button:not(.collapsed) {
            background-color: rgba(212,175,55,.08);
            color: var(--gold);
        }
        .dark-mode .page-link {
            background-color: var(--bg-surface);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        .dark-mode .page-link:hover {
            background-color: rgba(212,175,55,.1);
            color: var(--gold);
        }
        .dark-mode .page-item.active .page-link {
            background-color: var(--gold);
            border-color: var(--gold);
            color: #000;
        }
        .dark-mode .page-item.disabled .page-link {
            background-color: rgba(0,0,0,.2);
            color: var(--text-muted);
        }
        .dark-mode .modal-header,
        .dark-mode .modal-footer {
            border-color: var(--border-color);
        }
        .dark-mode .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        .dark-mode .breadcrumb-item.active {
            color: var(--text-primary);
        }
        .dark-mode .breadcrumb-item + .breadcrumb-item::before {
            color: var(--text-muted);
        }
        .dark-mode .form-control::placeholder,
        .dark-mode .form-select::placeholder {
            color: var(--text-muted);
        }
        .dark-mode .alert {
            background-color: var(--alert-bg);
            color: var(--alert-color);
            border-color: var(--border-color);
        }
        .dark-mode .btn-gca { border-color: rgba(212,175,55,.25); }
        .dark-mode .btn-gca:hover { color: #fff; }
        .dark-mode .btn-outline-gca { color: var(--text-primary); border-color: var(--border-color); }
        .dark-mode .btn-outline-gca:hover { color: var(--gold); background: rgba(212,175,55,.08); }
        .dark-mode .btn-edit { background: rgba(212,175,55,.1); color: var(--gold); }
        .dark-mode .btn-edit:hover { color: #fff; background: var(--gold); }
        .dark-mode .btn-delete { color: #f87171; background: rgba(220,38,38,.08); }
        .dark-mode .btn-delete:hover { color: #fff; background: #dc3545; }
        .dark-mode .stat-card {
            background: var(--bg-card);
            border-color: var(--border-color);
        }
        .dark-mode .stat-info p { color: var(--text-muted); }
        .dark-mode .toggle-btn { color: var(--text-secondary); }
        .dark-mode .toggle-btn:hover { background: rgba(255,255,255,.06); color: var(--text-primary); }
        .dark-mode .empty-state,
        .dark-mode .empty-state i { color: var(--text-muted); }
        .dark-mode .ts-dropdown {
            background: var(--bg-surface);
            border-color: var(--border-color);
        }
        .dark-mode .ts-dropdown .option.active {
            background: rgba(212,175,55,.12);
            color: var(--gold);
        }
        .dark-mode .ts-wrapper .ts-control {
            background: var(--bg-surface);
            color: var(--text-primary);
            border-color: var(--border-color);
        }
        .dark-mode .ts-wrapper.multi .ts-control > div {
            background: rgba(212,175,55,.1);
            border-color: rgba(212,175,55,.25);
            color: var(--text-primary);
        }
        .dark-mode .ts-wrapper.multi .ts-control > div .remove {
            border-color: rgba(212,175,55,.15);
            color: var(--text-muted);
        }
        .dark-mode .ts-wrapper.multi .ts-control > div .remove:hover {
            background: rgba(212,175,55,.15);
        }
    </style>
</head>
