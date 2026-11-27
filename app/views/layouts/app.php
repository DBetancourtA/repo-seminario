<?php
$u = Auth::user();
$route = trim((string)($_GET['r'] ?? ''), '/');
$isAdmin = $u && $u['rol'] === 'admin';
$navItems = $isAdmin
    ? ['admin' => 'Tablero', 'admin/proyectos' => 'Proyectos', 'admin/convocatorias' => 'Convocatorias', 'admin/usuarios' => 'Usuarios', 'admin/tecnologias' => 'Tecnologías']
    : ['panel' => 'Mi seminario', 'proyecto' => 'Mi proyecto', 'entregables' => 'Entregables', 'tecnologias' => 'Tecnologías'];
$active = function (string $key) use ($route): bool {
    if ($route === $key) return true;
    return $key !== 'admin' && $key !== 'panel' && str_starts_with($route, $key);
};
if (in_array($route, ['avance', 'semana'], true)) { $activeKey = 'panel'; }
if (in_array($route, ['admin/proyecto'], true)) { $activeKey = 'admin/proyectos'; }
if (in_array($route, ['admin/convocatoria', 'admin/convocatoria/ver'], true)) { $activeKey = 'admin/convocatorias'; }
if (in_array($route, ['admin/usuario'], true)) { $activeKey = 'admin/usuarios'; }
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>
(function () {
  var t = null;
  try { t = localStorage.getItem('theme'); } catch (e) {}
  if (t !== 'dark' && t !== 'light') {
    t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  document.documentElement.setAttribute('data-theme', t);
})();
</script>
<meta name="color-scheme" content="light dark">

<title><?= e(($titulo ?? '') !== '' ? $titulo . ' · ' : '') ?><?= e(cfg('app.nombre')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect x='2' y='2' width='28' height='28' rx='5' fill='none' stroke='%231C1B19' stroke-width='3'/%3E%3Cpath d='M10 21V11h7a3 3 0 0 1 0 6h-7' fill='none' stroke='%231F4E5F' stroke-width='3'/%3E%3C/svg%3E">
</head>
<body>
<a class="skip" href="#main">Saltar al contenido</a>
<header class="topbar">
  <div class="topbar-in">
    <a class="brand" href="<?= e(url('')) ?>">
      <span class="brand-mark" aria-hidden="true">ST</span>
      <span class="brand-text"><strong><?= e(cfg('app.nombre')) ?></strong><span><?= e(cfg('app.institucion')) ?></span></span>
    </a>
    <?php if ($u): ?>
    <button class="linkbtn menu-toggle" type="button" aria-expanded="false" aria-controls="nav" aria-label="Abrir menú"><?= icon('menu', 20) ?></button>
    <nav class="nav" id="nav" aria-label="Principal">
      <?php foreach ($navItems as $key => $label):
        $isActive = isset($activeKey) ? $activeKey === $key : $active($key); ?>
        <a href="<?= e(url($key)) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="user">
      <button class="linkbtn theme-toggle" type="button" aria-label="Cambiar a modo oscuro" title="Cambiar modo claro / oscuro"><span class="icon-moon"><?= icon('moon') ?></span><span class="icon-sun"><?= icon('sun') ?></span></button>
      <a class="linkbtn" href="<?= e(url('cuenta/clave')) ?>" aria-label="Cambiar mi contraseña" title="Cambiar mi contraseña"><?= icon('key') ?></a>
      <span class="avatar" aria-hidden="true"><?= e(initials($u['nombres'], $u['apellidos'])) ?></span>
      <span class="user-name"><?= e(explode(' ', $u['nombres'])[0] . ' ' . explode(' ', $u['apellidos'])[0]) ?><?= $isAdmin ? ' · Docente' : '' ?></span>
      <form method="post" action="<?= e(url('salir')) ?>">
        <?= csrf_field() ?>
        <button class="linkbtn" type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"><?= icon('logout') ?></button>
      </form>
    </div>
    <?php else: ?>
    <div class="user" style="margin-left:auto"><button class="linkbtn theme-toggle" type="button" aria-label="Cambiar a modo oscuro" title="Cambiar modo claro / oscuro"><span class="icon-moon"><?= icon('moon') ?></span><span class="icon-sun"><?= icon('sun') ?></span></button></div>
    <?php endif; ?>
  </div>
</header>
<main id="main" class="page">
  <?php partial('flashes'); ?>
  <?php if (!empty($_SESSION['clave_temporal']) && $isAdmin): $ct = $_SESSION['clave_temporal']; unset($_SESSION['clave_temporal']); ?>
    <div class="temp-pass" role="alert">
      <div>
        <strong>Contraseña temporal de <?= e($ct['nombre']) ?></strong>
        <p>Díctasela o envíasela. Entra con <span class="mono"><?= e($ct['email']) ?></span> y debe cambiarla con el ícono de la llave. Esta contraseña no se volverá a mostrar.</p>
      </div>
      <div class="temp-pass-code"><span class="mono" id="temp-pass"><?= e($ct['clave']) ?></span>
        <button class="btn btn-sm" type="button" data-copy="<?= e($ct['clave']) ?>">Copiar</button></div>
    </div>
  <?php endif; ?>
  <?= $content ?>
</main>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
