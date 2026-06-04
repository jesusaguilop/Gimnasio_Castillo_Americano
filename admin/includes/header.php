<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $pageTitle; ?> | GCA
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

    <style>
        :root {
            --gold: #d4af37;
            --gold-dark: #b8962e;
            --gold-light: #f0d060;
            --gold-glow: rgba(212,175,55,.08);
            --sidebar-w: 300px;

            /* Light (default) */
            --bg-body: #f5f3ee;
            --bg-card: #ffffff;
            --bg-card-hover: #fcfbfa;
            --bg-surface: #f8f7f4;
            --bg-input: #fff;
            --bg-dark: #0f1117;
            --bg-sidebar: #0a0a0a;
            --bg-sidebar-surface: #111111;
            --bg-sidebar-surface2: #1a1a1a;
            --text-primary: #1a1a1a;
            --text-secondary: #555;
            --text-muted: #888;
            --text-sidebar: #f0ede6;
            --text-sidebar-muted: #888;
            --border-color: #ece8e0;
            --border-input: #ddd;
            --border-light: rgba(0,0,0,.06);
            --shadow-card: 0 1px 3px rgba(0,0,0,.02);
            --shadow-card-hover: 0 4px 16px rgba(0,0,0,.04);
            --topbar-bg: rgba(255,255,255,.85);
            --table-header-bg: #f8f7f4;
            --table-row-border: #f0ede8;
            --form-bg: #fff;
            --alert-bg: #fef2f2;
            --alert-color: #b91c1c;
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
            --bg-sidebar-surface2: #181b28;
            --text-primary: #e8e6e0;
            --text-secondary: #aaa;
            --text-muted: #777;
            --text-sidebar: #e8e6e0;
            --text-sidebar-muted: #777;
            --border-color: #2a2d3a;
            --border-input: #333750;
            --border-light: rgba(255,255,255,.04);
            --shadow-card: 0 1px 3px rgba(0,0,0,.2);
            --shadow-card-hover: 0 4px 20px rgba(0,0,0,.3);
            --topbar-bg: rgba(15,17,23,.9);
            --table-header-bg: #1a1d27;
            --table-row-border: #252836;
            --form-bg: #1a1d27;
            --alert-bg: rgba(220,38,38,.12);
            --alert-color: #f87171;
        }

        body {
            transition: background .25s, color .25s;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-body);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(212, 175, 55, 0.3);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(212, 175, 55, 0.5);
        }

        #sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--bg-sidebar);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: transform .3s cubic-bezier(.4, 0, .2, 1);
            border-right: var(--border-gold);
        }

        #sidebar.collapsed {
            transform: translateX(-100%);
        }

        .sidebar-logo {
            padding: 28px 24px 20px;
            border-bottom: var(--border-gold);
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .sidebar-logo img {
            height: 48px;
            filter: brightness(1.1);
        }

        .sidebar-logo-text {
            line-height: 1.2;
        }

        .sidebar-logo-text span {
            display: block;
            font-family: 'Cormorant Garamond', serif;
            color: var(--gold);
            font-size: 17px;
            letter-spacing: .5px;
        }

        .sidebar-logo-text small {
            color: var(--text-muted);
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .admin-badge {
            margin: 16px 18px;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.08), rgba(212, 175, 55, 0.03));
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
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
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        }

        .admin-info {
            min-width: 0;
        }

        .admin-info span {
            display: block;
            color: var(--text-sidebar);
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .admin-info small {
            color: var(--text-muted);
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .greeting-badge {
            font-size: 10px;
            color: var(--gold);
            letter-spacing: 0.5px;
            margin-top: 2px;
            display: block;
        }

        .sidebar-nav {
            flex: 1;
            padding: 4px 0 16px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 3px;
        }

        .nav-section-label {
            padding: 20px 24px 6px;
            font-size: 10px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: rgba(212, 175, 55, 0.4);
            font-weight: 600;
        }

        .nav-parent {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: var(--text-sidebar-muted);
            font-size: 14.5px;
            font-weight: 500;
            cursor: pointer;
            user-select: none;
            border-left: 3px solid transparent;
            transition: all .2s;
            text-decoration: none;
            position: relative;
        }

        .nav-parent:hover {
            color: var(--gold);
            background: rgba(212, 175, 55, 0.05);
        }

        .nav-parent.active {
            color: var(--gold);
            border-left-color: var(--gold);
            background: rgba(212, 175, 55, 0.07);
        }

        .nav-parent .nav-icon {
            font-size: 18px;
            width: 22px;
            text-align: center;
            flex-shrink: 0;
        }

        .nav-parent .chevron {
            margin-left: auto;
            font-size: 12px;
            transition: transform .25s;
            color: var(--text-muted);
        }

        .nav-parent.open .chevron {
            transform: rotate(90deg);
            color: var(--gold);
        }

        .nav-submenu {
            overflow: hidden;
            max-height: 0;
            transition: max-height .35s cubic-bezier(.4, 0, .2, 1);
        }

        .nav-submenu.open {
            max-height: 500px;
        }

        .nav-sub-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 24px 9px 50px;
            color: var(--text-sidebar-muted);
            font-size: 13.5px;
            text-decoration: none;
            transition: all .18s;
            border-left: 3px solid transparent;
            position: relative;
        }

        .nav-sub-item::before {
            content: '';
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.3);
            flex-shrink: 0;
            transition: background .18s;
        }

        .nav-sub-item:hover {
            color: var(--gold-light);
            background: rgba(212, 175, 55, 0.04);
            border-left-color: rgba(212, 175, 55, 0.3);
        }

        .nav-sub-item:hover::before {
            background: var(--gold);
            box-shadow: 0 0 8px rgba(212, 175, 55, 0.5);
        }

        .nav-sub-item.active-item {
            color: var(--gold);
            font-weight: 600;
            border-left-color: var(--gold);
        }

        .nav-sub-item.active-item::before {
            background: var(--gold);
            box-shadow: 0 0 8px rgba(212, 175, 55, 0.5);
        }

        .sidebar-footer {
            padding: 14px 18px;
            border-top: var(--border-gold);
        }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 11px 16px;
            background: transparent;
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 12px;
            color: var(--text-muted);
            font-size: 13.5px;
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

        #main.expanded {
            margin-left: 0;
        }

        .topbar {
            background: var(--topbar-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-light);
            padding: 12px 32px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .toggle-btn {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 10px;
            transition: all .2s;
        }

        .toggle-btn:hover {
            background: rgba(0, 0, 0, 0.05);
            color: var(--text-primary);
        }

        .breadcrumb-bar {
            flex: 1;
        }

        .breadcrumb-bar h5 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            color: var(--text-primary);
            margin: 0;
        }

        .breadcrumb-bar p {
            font-size: 12px;
            color: var(--text-muted);
            margin: 0;
            letter-spacing: .3px;
        }

        .content-area {
            padding: 32px 36px;
            flex: 1;
        }

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

        .gca-card:hover {
            box-shadow: var(--shadow-card-hover);
        }

        .gca-table thead th {
            background: var(--table-header-bg);
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            border: none;
            padding: 14px 16px;
        }

        .gca-table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            color: var(--text-primary);
            border-bottom: 1px solid var(--table-row-border);
        }

        .gca-table tbody tr:last-child td {
            border-bottom: none;
        }

        .gca-table tbody tr:hover {
            background: var(--bg-card-hover);
        }

        .btn-gca {
            background: var(--bg-dark);
            color: var(--gold);
            border: 1px solid rgba(212, 175, 55, 0.3);
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 10px;
            transition: all .25s;
            font-size: 14px;
        }

        .btn-gca:hover {
            background: var(--gold);
            color: #000;
            border-color: var(--gold);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(212, 175, 55, 0.25);
        }
        .dark-mode .btn-gca { border-color: rgba(212,175,55,.25); }

        .btn-gca-sm {
            padding: 6px 14px;
            font-size: 13px;
            border-radius: 8px;
        }

        .btn-outline-gca {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-input);
            padding: 10px 20px;
            font-weight: 500;
            border-radius: 10px;
            transition: all .2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-outline-gca:hover {
            border-color: var(--gold);
            color: var(--gold-dark);
            background: rgba(212, 175, 55, 0.04);
        }

        .btn-action {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            transition: all .2s;
            text-decoration: none;
            font-size: 15px;
        }

        .btn-edit {
            background: rgba(212, 175, 55, 0.1);
            color: var(--gold-dark);
        }

        .btn-edit:hover {
            background: var(--gold);
            color: #000;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: rgba(220, 53, 69, 0.08);
            color: #dc3545;
        }

        .btn-delete:hover {
            background: #dc3545;
            color: #fff;
            transform: translateY(-1px);
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 14px;
            border: 1.5px solid var(--border-input);
            background: var(--bg-input);
            color: var(--text-primary);
            transition: border-color .2s, box-shadow .2s, background .25s;
            font-size: 14px;
        }
        .form-control::placeholder {
            color: var(--text-muted);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px var(--gold-glow);
            background: var(--form-bg);
        }

        select option { background: var(--bg-card); color: var(--text-primary); }

        .card-form {
            border-radius: 18px;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
            transition: background .25s, border-color .25s;
        }

        .header-box {
            text-align: center;
            margin-bottom: 28px;
        }

        .header-box img {
            height: 72px;
            margin-bottom: 12px;
        }

        .header-box h4 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .header-box span {
            color: var(--gold-dark);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-header h4 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h4 i {
            color: var(--gold);
        }

        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 48px;
            color: var(--text-muted);
            margin-bottom: 12px;
            display: block;
        }

        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
            }

            #sidebar.open {
                transform: translateX(0);
            }

            #main {
                margin-left: 0 !important;
            }

            #overlay.show {
                display: block;
                opacity: 1;
                pointer-events: all;
            }

            .content-area {
                padding: 20px 16px;
            }

            .topbar {
                padding: 10px 16px;
            }
        }
    </style>

    <!-- Tom Select: tema personalizado GCA -->
    <style>
        .ts-wrapper .ts-control {
            border-radius: 10px;
            padding: 10px 14px;
            border: 1.5px solid var(--border-input);
            font-size: 14px;
            min-height: 46px;
            display: flex;
            align-items: center;
            box-shadow: none;
            background: var(--bg-input);
            transition: border-color .2s, box-shadow .2s, background .25s;
            font-family: 'Outfit', sans-serif;
            color: var(--text-primary);
        }
        .ts-wrapper.single .ts-control { background: var(--bg-input); }
        .ts-wrapper .ts-control:hover { border-color: var(--text-muted); }
        .ts-wrapper.focus .ts-control {
            border-color: var(--gold);
            box-shadow: 0 0 0 4px var(--gold-glow);
            background: var(--form-bg);
        }
        .ts-wrapper .ts-control input {
            font-family: 'Outfit', sans-serif;
            color: var(--text-primary);
        }
        .ts-wrapper .ts-control input::placeholder { color: var(--text-muted); }
        .ts-wrapper .ts-dropdown {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 8px 40px rgba(0,0,0,.15);
            margin-top: 4px;
            overflow: hidden;
            font-family: 'Outfit', sans-serif;
            background: var(--bg-card);
        }
        .ts-wrapper .ts-dropdown .option {
            padding: 10px 14px;
            font-size: 14px;
            border-bottom: 1px solid var(--table-row-border);
            transition: background .15s;
            color: var(--text-primary);
        }
        .ts-wrapper .ts-dropdown .option:last-child { border-bottom: none; }
        .ts-wrapper .ts-dropdown .option.active { background: var(--gold-glow); }
        .ts-wrapper .ts-dropdown .option.highlight { background: rgba(212,175,55,.12); }
        .ts-wrapper .ts-dropdown .option.selected {
            background: rgba(212,175,55,.15);
            font-weight: 600;
        }
        .ts-wrapper .ts-dropdown .option.selected::after {
            content: ' ✓';
            color: var(--gold-dark);
            font-weight: 700;
        }
        .ts-wrapper .ts-dropdown .no-results {
            padding: 14px;
            color: var(--text-muted);
            font-size: 13px;
            text-align: center;
        }
        .ts-wrapper .ts-control .item {
            background: linear-gradient(135deg, rgba(212,175,55,.1), rgba(212,175,55,.05));
            border: 1px solid rgba(212,175,55,.25);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 13px;
        }
        .ts-wrapper .ts-control .item .remove {
            border-color: rgba(212,175,55,.15);
            color: var(--text-muted);
        }
        .ts-wrapper .ts-control .item .remove:hover {
            background: rgba(212,175,55,.15);
            color: var(--text-primary);
        }
        .dropdown-active .ts-control { border-radius: 10px 10px 0 0; }
        .ts-wrapper .ts-dropdown .optgroup-header {
            background: var(--bg-surface);
            color: var(--text-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 8px 14px;
            font-weight: 600;
        }
    </style>
    <!-- ── Dark Mode overrides ── -->
    <style>
        .dark-mode,
        [data-bs-theme="dark"] {
            /* Ensure body, tables, cards, forms get dark bg */
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
        .dark-mode[data-bs-theme="dark"] .ts-control,
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
        .dark-mode .admin-avatar { color: var(--text-primary); }
        .dark-mode .stat-card {
            background: var(--bg-card);
            border-color: var(--border-color);
        }
        .dark-mode .stat-value { color: var(--text-primary); }
        .dark-mode .empty-state,
        .dark-mode .empty-state i { color: var(--text-muted); }
        .dark-mode select option { background: var(--bg-surface); color: var(--text-primary); }
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
