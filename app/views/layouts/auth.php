<!doctype html>
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
<button class="linkbtn theme-toggle auth-theme" type="button" aria-label="Cambiar a modo oscuro" title="Cambiar modo claro / oscuro"><span class="icon-moon"><?= icon('moon') ?></span><span class="icon-sun"><?= icon('sun') ?></span></button>
<div class="auth-body">
  <aside class="auth-side">
    <a class="brand" href="<?= e(url('')) ?>">
      <span class="brand-mark" aria-hidden="true">ST</span>
      <span class="brand-text"><strong><?= e(cfg('app.nombre')) ?></strong><span><?= e(cfg('app.institucion')) ?></span></span>
    </a>
    <div>
      <h1>Tu proyecto de titulación, clase por clase.</h1>
      <p>Registra tu proyecto, sigue su aprobación y documenta cada sesión del seminario con evidencia de tu código. Al cerrar la semana descargas el reporte para el EVA.</p>
      <ol class="auth-steps">
        <li><b>01</b>Te inscribes con el código de tu convocatoria.</li>
        <li><b>02</b>Registras el proyecto y el docente lo revisa.</li>
        <li><b>03</b>Defines las tecnologías con las que lo construirás.</li>
        <li><b>04</b>Cada clase subes lo que hiciste y una captura.</li>
      </ol>
    </div>
    <p class="auth-foot"><?= e(cfg('app.carrera')) ?></p>
  </aside>
  <main class="auth-main" id="main">
    <div class="auth-card<?= !empty($wide) ? ' wide' : '' ?>">
      <?php partial('flashes'); ?>
      <?= $content ?>
    </div>
  </main>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
