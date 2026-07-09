<?php
$nombre_admin = htmlspecialchars($_SESSION["nombre"] ?? "Administrador");
$hora = (int)date("H");
if ($hora < 12) {
    $saludo = "Buenos días";
} elseif ($hora < 18) {
    $saludo = "Buenas tardes";
} else {
    $saludo = "Buenas noches";
}
?>
<nav id="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/img/logo_gca.png" alt="GCA" onerror="this.src=''; this.alt='GCA';">
        <div class="sidebar-logo-text">
            <span>Castillo Americano</span>
            <small>Panel de Administración</small>
        </div>
    </div>

    <div class="admin-badge">
        <div class="admin-avatar"><i class="bi bi-person-fill"></i></div>
        <div class="admin-info">
            <span><?= $nombre_admin ?></span>
            <small class="greeting-badge"><?= $saludo ?></small>
        </div>
    </div>

    <?php
    $num_alertas_admin = 0;
    try {
        $stmtA = $conexion->query("SELECT COUNT(*) FROM alertas WHERE para_rol = 'admin' AND leido = FALSE");
        $num_alertas_admin = (int) $stmtA->fetchColumn();
    } catch (Exception $e) {}
    ?>
    <a href="alertas.php" style="display:flex;align-items:center;gap:10px;margin:8px 18px 4px;padding:10px 14px;border-radius:10px;text-decoration:none;color:var(--text-sidebar-muted);font-size:13.5px;font-weight:500;transition:all.2s;border:1px solid rgba(212,175,55,0.08);background:rgba(212,175,55,0.03);"
       onmouseover="this.style.background='rgba(212,175,55,0.08)'" onmouseout="this.style.background='rgba(212,175,55,0.03)'">
        <i class="bi bi-bell" style="font-size:16px;"></i>
        Alertas
        <?php if ($num_alertas_admin > 0): ?>
            <span style="margin-left:auto;background:#dc3545;color:#fff;font-size:10px;font-weight:700;padding:1px 8px;border-radius:20px;"><?= $num_alertas_admin ?></span>
        <?php endif; ?>
    </a>

    <div class="sidebar-nav">
        <div class="nav-section-label">Navegación</div>

        <a href="dashboard.php"
            class="nav-parent <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>"
            style="text-decoration:none;">
            <i class="bi bi-speedometer2 nav-icon"></i>
            Dashboard
        </a>

        <div class="nav-parent" onclick="toggleMenu(this)">
            <i class="bi bi-people-fill nav-icon"></i>
            Usuarios
            <i class="bi bi-chevron-right chevron"></i>
        </div>
        <div class="nav-submenu">
            <a href="ver_admins.php" class="nav-sub-item">Administradores</a>
            <a href="ver_profesores.php" class="nav-sub-item">Profesores</a>
            <a href="ver_padres.php" class="nav-sub-item">Padres de Familia</a>
            <a href="ver_estudiantes.php" class="nav-sub-item">Estudiantes</a>
        </div>

        <div class="nav-parent" onclick="toggleMenu(this)">
            <i class="bi bi-journal-bookmark-fill nav-icon"></i>
            Académico
            <i class="bi bi-chevron-right chevron"></i>
        </div>
        <div class="nav-submenu">
            <a href="crear_curso.php" class="nav-sub-item">Crear Curso</a>
            <a href="ver_cursos.php" class="nav-sub-item">Ver Cursos</a>
            <a href="crear_asignatura.php" class="nav-sub-item">Crear Asignatura</a>
            <a href="ver_asignaturas.php" class="nav-sub-item">Ver Asignaturas</a>
            <a href="asignar_profesor.php" class="nav-sub-item">Asignar Materias</a>
        </div>

        <div class="nav-parent" onclick="toggleMenu(this)">
            <i class="bi bi-person-plus-fill nav-icon"></i>
            Registros
            <i class="bi bi-chevron-right chevron"></i>
        </div>
        <div class="nav-submenu">
            <a href="crear_admin.php" class="nav-sub-item">Nuevo Admin</a>
            <a href="crear_profesor.php" class="nav-sub-item">Nuevo Profesor</a>
            <a href="crear_padre.php" class="nav-sub-item">Nuevo Padre</a>
            <a href="crear_estudiante.php" class="nav-sub-item">Nuevo Estudiante</a>
        </div>

        <div class="nav-parent" onclick="toggleMenu(this)">
            <i class="bi bi-link-45deg nav-icon"></i>
            Asignaciones
            <i class="bi bi-chevron-right chevron"></i>
        </div>
        <div class="nav-submenu">
            <a href="asignar_padre.php" class="nav-sub-item">Padre → Estudiante</a>
            <a href="asignar_estudiante.php" class="nav-sub-item">Estudiante → Curso</a>
            <a href="asignar_director.php" class="nav-sub-item">Director de Grupo</a>
        </div>

        <div class="nav-parent" onclick="toggleMenu(this)">
            <i class="bi bi-newspaper nav-icon"></i>
            Contenido Web
            <i class="bi bi-chevron-right chevron"></i>
        </div>
        <div class="nav-submenu">
            <a href="ver_noticias.php" class="nav-sub-item">Noticias</a>
            <a href="ver_eventos.php" class="nav-sub-item">Eventos</a>
            <a href="ver_docentes.php" class="nav-sub-item">Directorio Docentes</a>
        </div>

        <div class="nav-section-label">Herramientas</div>

        <a href="plataforma.php"
            class="nav-parent <?php echo (basename($_SERVER['PHP_SELF']) == 'plataforma.php') ? 'active' : ''; ?>"
            style="text-decoration:none;">
            <i class="bi bi-sliders2 nav-icon"></i>
            Configuración
        </a>

        <a href="mejores_estudiantes.php"
            class="nav-parent <?php echo (basename($_SERVER['PHP_SELF']) == 'mejores_estudiantes.php') ? 'active' : ''; ?>"
            style="text-decoration:none;">
            <i class="bi bi-trophy nav-icon"></i>
            Mejores Estudiantes
        </a>

        <div class="nav-section-label">Sistema</div>

        <a href="../index.php" class="nav-parent" style="text-decoration:none;">
            <i class="bi bi-globe2 nav-icon"></i>
            Sitio Web
        </a>
    </div>

    <div class="sidebar-footer">
        <button id="darkModeToggle" class="btn-logout mb-2" style="border-style:dashed;" aria-label="Modo oscuro">
            <i class="bi bi-moon-stars" id="dmIcon"></i>
            <span id="dmLabel">Modo oscuro</span>
        </button>
        <a href="../auth/logout.php" class="btn-logout">
            <i class="bi bi-box-arrow-right"></i>
            Cerrar sesión
        </a>
    </div>
</nav>
