<p class="eyebrow">Verificación</p>
<h2>Comprobar un reporte semanal</h2>
<p class="lead">Escribe el código que aparece al pie del PDF.</p>
<form class="form" method="get" action="<?= e(base_url() . '/index.php') ?>">
  <input type="hidden" name="r" value="verificar">
  <div class="field">
    <label for="c">Código de verificación</label>
    <input class="input code-input" id="c" name="c" required maxlength="16" autocomplete="off" value="<?= e($codigo) ?>">
  </div>
  <button class="btn btn-primary" type="submit">Verificar</button>
</form>

<?php if ($codigo !== ''): ?>
<div class="verify-result">
  <?php if (!$rep): ?>
    <div class="note note-danger"><strong>Código no encontrado</strong>Este código no corresponde a ningún reporte emitido por la plataforma.</div>
  <?php else: ?>
    <div class="note <?= $integro ? 'note-accent' : 'note-warn' ?>">
      <strong><?= $integro ? 'Reporte válido' : 'Reporte emitido, pero la bitácora cambió después' ?></strong>
      <?= $integro
        ? 'El contenido de la semana coincide con lo registrado en la plataforma.'
        : 'El estudiante editó sus avances de esa semana después de generar este PDF. Revisa la versión actual en la plataforma.' ?>
    </div>
    <dl class="dl" style="margin-top:16px">
      <dt>Estudiante</dt><dd><?= e($rep['nombres'] . ' ' . $rep['apellidos']) ?></dd>
      <dt>Proyecto</dt><dd><?= e($rep['titulo']) ?></dd>
      <dt>Convocatoria</dt><dd><?= e($rep['conv_nombre']) ?></dd>
      <dt>Semana</dt><dd class="mono"><?= (int)$rep['semana'] ?></dd>
      <dt>Emitido</dt><dd><?= e(fecha_hora($rep['created_at'])) ?></dd>
    </dl>
  <?php endif; ?>
</div>
<?php endif; ?>
<p class="auth-switch"><a href="<?= e(url('login')) ?>">Ir a la plataforma</a></p>
