<?php

session_start();

if (!isset($_SESSION['usuarios'])) {
    $_SESSION['usuarios'] = [
        ['id' => 1, 'nombre_completo' => 'María López',   'usuario' => 'mlopez',   'correo' => 'mlopez@correo.com',   'telefono' => '8888-1111', 'contrasena' => password_hash('12345678', PASSWORD_DEFAULT), 'rol' => 'Coordinador',   'estado' => 'Activo'],
        ['id' => 2, 'nombre_completo' => 'Carlos Ramírez','usuario' => 'cramirez', 'correo' => 'cramirez@correo.com', 'telefono' => '8888-2222', 'contrasena' => password_hash('12345678', PASSWORD_DEFAULT), 'rol' => 'Supervisor',    'estado' => 'Activo'],
        ['id' => 3, 'nombre_completo' => 'Luis Torres',   'usuario' => 'ltorres',  'correo' => 'luis@correo.com',     'telefono' => '8888-3333', 'contrasena' => password_hash('12345678', PASSWORD_DEFAULT), 'rol' => 'Transportista','estado' => 'Activo'],
        ['id' => 4, 'nombre_completo' => 'Ana Gómez',     'usuario' => 'agomez',   'correo' => 'ana@correo.com',      'telefono' => '8888-4444', 'contrasena' => password_hash('12345678', PASSWORD_DEFAULT), 'rol' => 'Administrador','estado' => 'Activo'],
        ['id' => 5, 'nombre_completo' => 'José Pérez',    'usuario' => 'jperez',   'correo' => 'jose@correo.com',     'telefono' => '8888-5555', 'contrasena' => password_hash('12345678', PASSWORD_DEFAULT), 'rol' => 'Coordinador',   'estado' => 'Inactivo'],
    ];
    $_SESSION['siguiente_id'] = 6;
}

$roles = ['Administrador', 'Coordinador', 'Supervisor', 'Transportista'];
$errores = [];
$usuario_editar = null;

function buscar_por_id($id) {
    foreach ($_SESSION['usuarios'] as $u) {
        if ($u['id'] == $id) return $u;
    }
    return null;
}

function usuario_o_correo_repetido($usuario, $correo, $id_excluir = null) {
    foreach ($_SESSION['usuarios'] as $u) {
        if ($id_excluir !== null && $u['id'] == $id_excluir) continue;
        if ($u['usuario'] === $usuario || $u['correo'] === $correo) return true;
    }
    return false;
}

if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];

    $_SESSION['usuarios'] = array_values(array_filter(
        $_SESSION['usuarios'],
        fn($u) => $u['id'] != $id
    ));

    header('Location: admin_usuarios.php?' . http_build_query(array_filter([
        'buscar' => $_GET['buscar'] ?? null,
        'pagina' => $_GET['pagina'] ?? null,
    ])));
    exit;
}

if (isset($_GET['editar'])) {
    $usuario_editar = buscar_por_id((int) $_GET['editar']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
    $id_edicion      = $_POST['id'] !== '' ? (int) $_POST['id'] : null;
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $usuario         = trim($_POST['usuario'] ?? '');
    $telefono        = trim($_POST['telefono'] ?? '');
    $correo          = trim($_POST['correo'] ?? '');
    $contrasena      = $_POST['contrasena'] ?? '';
    $rol             = $_POST['rol'] ?? '';
    $estado          = $_POST['estado'] ?? 'Activo';

    if ($nombre_completo === '') $errores[] = 'El nombre completo es obligatorio.';
    if ($usuario === '') $errores[] = 'El usuario es obligatorio.';
    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo electrónico no es válido.';
    if (!in_array($rol, $roles, true)) $errores[] = 'Debes seleccionar un rol.';
    if ($id_edicion === null && strlen($contrasena) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($contrasena !== '' && strlen($contrasena) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if (usuario_o_correo_repetido($usuario, $correo, $id_edicion)) $errores[] = 'Ya existe un usuario con ese nombre de usuario o correo.';

    if (empty($errores)) {
        if ($id_edicion === null) {
            $_SESSION['usuarios'][] = [
                'id' => $_SESSION['siguiente_id']++,
                'nombre_completo' => $nombre_completo,
                'usuario' => $usuario,
                'correo' => $correo,
                'telefono' => $telefono,
                'contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
                'rol' => $rol,
                'estado' => $estado,
            ];
        } else {
            foreach ($_SESSION['usuarios'] as &$u) {
                if ($u['id'] == $id_edicion) {
                    $u['nombre_completo'] = $nombre_completo;
                    $u['usuario'] = $usuario;
                    $u['correo'] = $correo;
                    $u['telefono'] = $telefono;
                    $u['rol'] = $rol;
                    $u['estado'] = $estado;
                    if ($contrasena !== '') $u['contrasena'] = password_hash($contrasena, PASSWORD_DEFAULT);
                }
            }
            unset($u);
        }
        header('Location: admin_usuarios.php?' . http_build_query(array_filter([
            'buscar' => $_GET['buscar'] ?? null,
            'pagina' => $_GET['pagina'] ?? null,
        ])));
        exit;
    } else {
        $usuario_editar = [
            'id' => $id_edicion,
            'nombre_completo' => $nombre_completo,
            'usuario' => $usuario,
            'correo' => $correo,
            'telefono' => $telefono,
            'rol' => $rol,
            'estado' => $estado,
        ];
    }
}

$termino = trim($_GET['buscar'] ?? '');
$usuarios_filtrados = $_SESSION['usuarios'];
if ($termino !== '') {
    $termino_lower = mb_strtolower($termino);
    $usuarios_filtrados = array_values(array_filter($usuarios_filtrados, function ($u) use ($termino_lower) {
        return str_contains(mb_strtolower($u['nombre_completo']), $termino_lower)
            || str_contains(mb_strtolower($u['usuario']), $termino_lower)
            || str_contains(mb_strtolower($u['correo']), $termino_lower);
    }));
}

$por_pagina = 5;
$total_usuarios = count($usuarios_filtrados);
$total_paginas = max(1, (int) ceil($total_usuarios / $por_pagina));
$pagina_actual = max(1, min($total_paginas, (int) ($_GET['pagina'] ?? 1)));
$usuarios_pagina = array_slice($usuarios_filtrados, ($pagina_actual - 1) * $por_pagina, $por_pagina);

$roles_clase = [
    'Coordinador' => 'rol-coordinador',
    'Supervisor' => 'rol-supervisor',
    'Transportista' => 'rol-transportista',
    'Administrador' => 'rol-administrador',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administración de usuarios · Grupo S.A Logistic</title>
<link rel="stylesheet" href="css/usuario.css">
</head>
<body>

<button type="button" class="menu-btn" id="menuBtn" aria-label="Abrir menú">☰</button>
<div class="menu-fondo" id="menuFondo"></div>

<aside class="side-menu" id="sideMenu">
  <div class="menu-content">
    <div class="menu-header">
      <div>
        <h5>Grupo S.A Logistics</h5>
        <div class="company-subtitle">Paquetería y Envíos</div>
      </div>
      <button type="button" class="menu-close" id="menuClose" aria-label="Cerrar menú">×</button>
    </div>

    <div class="menu-container">
      <a href="admin_usuarios.php" class="menu-link active"><span>👤</span><span>Usuario</span></a>
      <a href="clientes.php" class="menu-link"><span>👥</span><span>Clientes</span></a>
      <a href="empleados.php" class="menu-link"><span>🧑‍💼</span><span>Empleado</span></a>
      <a href="incidencia.php" class="menu-link"><span>⚠️</span><span>Incidencia</span></a>
      <a href="reporte.php" class="menu-link"><span>📊</span><span>Reporte</span></a>
      <a href="logout.php" class="menu-link"><span>↪</span><span>Cerrar sesión</span></a>
    </div>
  </div>
</aside>

<script>
(function(){
  var menu = document.getElementById('sideMenu');
  var fondo = document.getElementById('menuFondo');
  function abrir(){ menu.classList.add('activo'); fondo.classList.add('activo'); }
  function cerrar(){ menu.classList.remove('activo'); fondo.classList.remove('activo'); }
  document.getElementById('menuBtn').addEventListener('click', abrir);
  document.getElementById('menuClose').addEventListener('click', cerrar);
  fondo.addEventListener('click', cerrar);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') cerrar(); });
})();
</script>

<main>
  <header class="page-head">
    <span class="page-icon">👤</span>
    <div>
      <h1>Administración de usuarios</h1>
      <p>Gestione los usuarios del sistema, asigne roles y mantenga la seguridad de la información.</p>
    </div>
  </header>

  <?php if (!empty($errores)): ?>
    <div class="alerta-error">
      <ul><?php foreach ($errores as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
    </div>
  <?php endif; ?>

  <section class="panel" id="form-usuario">
    <div class="panel-title">
      <span class="plus">⊕</span>
      <?= $usuario_editar && !empty($usuario_editar['id']) ? 'Editar usuario' : 'Nuevo usuario' ?>
    </div>

    <form method="POST" action="admin_usuarios.php<?= $termino !== '' ? '?buscar=' . urlencode($termino) : '' ?>">
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="id" value="<?= htmlspecialchars($usuario_editar['id'] ?? '') ?>">

      <div class="form-grid">
        <div class="campo">
          <label>Nombre completo *</label>
          <input type="text" name="nombre_completo" placeholder="Ej. María López" value="<?= htmlspecialchars($usuario_editar['nombre_completo'] ?? '') ?>" required>
        </div>
        <div class="campo">
          <label>Usuario *</label>
          <input type="text" name="usuario" placeholder="Ej. mlopez" value="<?= htmlspecialchars($usuario_editar['usuario'] ?? '') ?>" required>
        </div>
        <div class="campo">
          <label>Teléfono</label>
          <input type="text" name="telefono" placeholder="Ej. 8888-8888" value="<?= htmlspecialchars($usuario_editar['telefono'] ?? '') ?>">
        </div>

        <div class="campo">
          <label>Correo electrónico *</label>
          <input type="email" name="correo" placeholder="ejemplo@correo.com" value="<?= htmlspecialchars($usuario_editar['correo'] ?? '') ?>" required>
        </div>
        <div class="campo">
          <label>Contraseña <?= ($usuario_editar && !empty($usuario_editar['id'])) ? '' : '*' ?></label>
          <div class="campo-password">
            <input type="password" name="contrasena" id="campoContrasena" placeholder="<?= ($usuario_editar && !empty($usuario_editar['id'])) ? 'Dejar en blanco para no cambiar' : 'Mínimo 8 caracteres' ?>">
            <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
          </div>
        </div>
        <div class="campo">
          <label>Estado</label>
          <select name="estado">
            <option value="Activo" <?= (($usuario_editar['estado'] ?? 'Activo') === 'Activo') ? 'selected' : '' ?>>Activo</option>
            <option value="Inactivo" <?= (($usuario_editar['estado'] ?? '') === 'Inactivo') ? 'selected' : '' ?>>Inactivo</option>
          </select>
        </div>

        <div class="campo">
          <label>Rol *</label>
          <select name="rol" required>
            <option value="" disabled <?= empty($usuario_editar['rol']) ? 'selected' : '' ?>>Seleccionar rol</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r ?>" <?= (($usuario_editar['rol'] ?? '') === $r) ? 'selected' : '' ?>><?= $r ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-acciones">
        <a href="admin_usuarios.php<?= $termino !== '' ? '?buscar=' . urlencode($termino) : '' ?>" class="btn-outline">↻ Limpiar</a>
        <button type="submit" class="btn-primary">💾 Guardar</button>
      </div>
    </form>
  </section>

  <section class="panel">
    <div class="lista-header">
      <h2>Lista de usuarios</h2>
      <form method="GET" action="admin_usuarios.php" class="buscador">
        <input type="text" name="buscar" placeholder="🔍 Buscar por nombre, usuario o correo..." value="<?= htmlspecialchars($termino) ?>">
      </form>
      <a href="#form-usuario" class="btn-primary">+ Nuevo usuario</a>
    </div>

    <div class="table-scroll">
      <table class="tabla-usuarios">
        <thead>
          <tr>
            <th>#</th><th>Nombre completo</th><th>Usuario</th><th>Correo electrónico</th>
            <th>Rol</th><th>Estado</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($usuarios_pagina)): ?>
            <tr><td colspan="7" class="sin-resultados">No se encontraron usuarios.</td></tr>
          <?php endif; ?>
          <?php foreach ($usuarios_pagina as $i => $u): ?>
            <tr>
              <td><?= ($pagina_actual - 1) * $por_pagina + $i + 1 ?></td>
              <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
              <td><?= htmlspecialchars($u['usuario']) ?></td>
              <td><?= htmlspecialchars($u['correo']) ?></td>
              <td><span class="badge-rol <?= $roles_clase[$u['rol']] ?? '' ?>"><?= htmlspecialchars($u['rol']) ?></span></td>
              <td><span class="badge-estado <?= $u['estado'] === 'Activo' ? 'estado-activo' : 'estado-inactivo' ?>"><?= $u['estado'] ?></span></td>
              <td class="acciones">
                <a href="admin_usuarios.php?editar=<?= $u['id'] ?>#form-usuario" class="btn-icon btn-editar" title="Editar">✎</a>
                <a href="admin_usuarios.php?eliminar=<?= $u['id'] ?>&buscar=<?= urlencode($termino) ?>&pagina=<?= $pagina_actual ?>"
                   class="btn-icon btn-eliminar" title="Eliminar"
                   onclick="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($u['nombre_completo'])) ?>?');">🗑</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="lista-footer">
      <span>Mostrando <?= $total_usuarios === 0 ? 0 : ($pagina_actual - 1) * $por_pagina + 1 ?> - <?= min($pagina_actual * $por_pagina, $total_usuarios) ?> de <?= $total_usuarios ?> usuarios</span>
      <div class="paginacion">
        <a class="pag-btn <?= $pagina_actual <= 1 ? 'disabled' : '' ?>" href="admin_usuarios.php?<?= http_build_query(array_filter(['buscar' => $termino, 'pagina' => $pagina_actual - 1])) ?>">‹</a>
        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
          <a class="pag-btn <?= $p === $pagina_actual ? 'activo' : '' ?>" href="admin_usuarios.php?<?= http_build_query(array_filter(['buscar' => $termino, 'pagina' => $p])) ?>"><?= $p ?></a>
        <?php endfor; ?>
        <a class="pag-btn <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>" href="admin_usuarios.php?<?= http_build_query(array_filter(['buscar' => $termino, 'pagina' => $pagina_actual + 1])) ?>">›</a>
      </div>
    </div>
  </section>
</main>

<script>
function togglePassword() {
  const campo = document.getElementById('campoContrasena');
  campo.type = campo.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html> 