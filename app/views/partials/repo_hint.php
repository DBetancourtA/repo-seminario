<?php
/** @var string $for  id del campo con la URL */
$correo = (string) cfg('app.correo_docente', '');
?>
<div class="repo-hint" data-repo-for="<?= e($for) ?>" data-correo="<?= e($correo) ?>">
  <p class="help">Si el repositorio es <strong>privado</strong>, dale acceso a tu docente: en GitHub, abre el repositorio → <b>Settings</b> → <b>Collaborators</b> → <b>Add people</b> y escribe <?= $correo !== '' ? '<span class="mono">' . e($correo) . '</span>' : 'el correo de tu docente' ?>.</p>
  <p class="repo-status" role="status" aria-live="polite" hidden></p>
</div>
