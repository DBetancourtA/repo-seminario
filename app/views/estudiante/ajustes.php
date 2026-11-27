<?php
$err = errors();
$val = fn($campo) => old($campo, $valores[$campo]);
?>
<div class="page-head">
  <div>
    <p class="eyebrow"><a href="<?= e(url('panel')) ?>">Mi seminario</a><span aria-hidden="true">/</span><a href="<?= e(url('proyecto')) ?>">Ficha</a><span aria-hidden="true">/</span><span>Ajustar</span></p>
    <h1>Ajustar la ficha del proyecto</h1>
    <p class="lead">Tu proyecto ya está aprobado. Lo que estaba vacío lo puedes completar ahora mismo; cambiar algo que ya escribiste necesita la aprobación del docente.</p>
  </div>
</div>

<?php if ($congela): ?>
  <div class="note note-warn" style="margin-bottom:24px"><strong>Disponible hasta el <?= e(fecha_larga(date('Y-m-d', strtotime($congela . ' -1 day')))) ?></strong>Desde la semana <?= (int)$conv['semana_congelamiento'] ?> (<?= e(fecha_larga($congela)) ?>) la ficha queda cerrada y solo el docente puede modificarla.</div>
<?php endif; ?>

<div class="grid-2">
<form class="form" method="post" action="<?= e(url('proyecto/ajustes')) ?>" enctype="multipart/form-data" novalidate data-busy="Guardando…">
  <?= csrf_field() ?>
  <?php foreach (Cambios::CAMPOS_PROYECTO as $campo => $def):
    $actual = $valores[$campo];
    $vacio = $actual === '';
    $pd = $pend[$campo] ?? null;
    $modo = $pd ? 'pendiente' : ($def['libre'] ? 'libre' : ($vacio ? 'completar' : 'solicitud'));
    $id = 'f-' . $campo;
  ?>
    <fieldset class="fieldset adjust adjust-<?= $modo ?>">
      <div class="adjust-head">
        <legend><?= e($def['label']) ?></legend>
        <?php if ($modo === 'pendiente'): ?><span class="badge badge-accent">Solicitud pendiente</span>
        <?php elseif ($modo === 'completar'): ?><span class="badge">Vacío · se guarda al instante</span>
        <?php elseif ($modo === 'libre'): ?><span class="badge">Libre</span>
        <?php else: ?><span class="badge badge-warn">Requiere aprobación</span><?php endif; ?>
      </div>

      <?php if ($pd): ?>
        <div class="diff">
          <div class="diff-old"><span class="diff-tag">Actual</span><?= $actual !== '' ? nl2br(e($actual)) : '<em class="muted">vacío</em>' ?></div>
          <div class="diff-new"><span class="diff-tag">Pediste</span><?= nl2br(e($pd['valor_nuevo'])) ?></div>
        </div>
        <p class="small muted">Enviada el <?= e(fecha_hora($pd['created_at'])) ?> · Motivo: <?= e($pd['motivo']) ?></p>
        <div><button class="btn btn-sm" type="submit" form="cancel-<?= (int)$pd['id'] ?>">Retirar solicitud</button></div>
      <?php else: ?>
        <?php if ($campo === 'titulo' || $campo === 'repo_url'): ?>
          <label class="sr-only" for="<?= $id ?>"><?= e($def['label']) ?></label>
          <input class="input<?= $campo === 'repo_url' ? ' mono' : '' ?>" id="<?= $id ?>" name="<?= $campo ?>" <?= $campo === 'repo_url' ? 'type="url" placeholder="https://github.com/usuario/proyecto"' : 'maxlength="200"' ?> value="<?= e($val($campo)) ?>"<?= invalid($campo) ?> data-original="<?= e($actual) ?>">
        <?php else: ?>
          <label class="sr-only" for="<?= $id ?>"><?= e($def['label']) ?></label>
          <textarea class="textarea" id="<?= $id ?>" name="<?= $campo ?>" rows="<?= $campo === 'descripcion' ? 5 : 3 ?>"<?= invalid($campo) ?> data-original="<?= e($actual) ?>"><?= e($val($campo)) ?></textarea>
          <?php if ($campo === 'objetivos'): ?><p class="help">Un objetivo por línea, hasta 6.</p><?php endif; ?>
        <?php endif; ?>
        <?= field_error($campo) ?>
        <?php if ($campo === 'repo_url') partial('repo_hint', ['for' => $id]); ?>
        <?php if ($modo === 'solicitud'): ?>
          <div class="field motivo" data-for="<?= $id ?>"<?= isset($err['motivo_' . $campo]) || old('motivo_' . $campo) !== '' ? '' : ' hidden' ?>>
            <label for="m-<?= $campo ?>">¿Por qué necesitas este cambio?</label>
            <input class="input" id="m-<?= $campo ?>" name="motivo_<?= $campo ?>" maxlength="500" value="<?= e(old('motivo_' . $campo)) ?>"<?= invalid('motivo_' . $campo) ?>>
            <?= field_error('motivo_' . $campo) ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </fieldset>
  <?php endforeach; ?>

  <fieldset class="fieldset adjust adjust-libre">
    <div class="adjust-head"><legend>Capturas</legend><span class="badge">Libre · hasta 4</span></div>
    <?php if ($capturas): ?>
      <div class="shots">
        <?php foreach ($capturas as $c): $src = url('archivo', ['t' => 'proyecto', 'id' => $c['id']]); ?>
          <div class="shot">
            <a href="<?= e($src) ?>" target="_blank" rel="noopener"><img src="<?= e($src) ?>" alt="Captura del proyecto" loading="lazy"></a>
            <button class="linkbtn shot-del" type="submit" form="del-<?= (int)$c['id'] ?>" aria-label="Eliminar captura"><?= icon('x', 16) ?></button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (count($capturas) < 4): ?>
      <div class="drop" data-previews="prev-aj" data-max="<?= 4 - count($capturas) ?>">
        <input type="file" name="capturas[]" accept="image/png,image/jpeg,image/webp" multiple aria-label="Agregar capturas del proyecto"<?= invalid('capturas') ?>>
        <?= icon('image', 22) ?>
        <strong>Agregar capturas</strong>
        <span>PNG, JPG o WebP · máx. <?= (int)cfg('app.max_mb', 5) ?> MB c/u</span>
      </div>
      <p class="field-error" id="prev-aj-msg" aria-live="polite"></p>
      <div class="previews" id="prev-aj"></div>
    <?php endif; ?>
    <?= field_error('capturas') ?>
  </fieldset>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-ghost" href="<?= e(url('proyecto')) ?>">Cancelar</a>
  </div>
</form>

<aside>
  <div class="panel panel-pad">
    <h2 style="font-size:15px;margin-bottom:10px">Cómo funciona</h2>
    <dl class="rules">
      <dt><span class="badge">Vacío</span></dt><dd>Si no lo llenaste al registrarte, complétalo: se guarda de inmediato.</dd>
      <dt><span class="badge badge-warn">Requiere aprobación</span></dt><dd>Si cambias algo que ya estaba escrito, escribe el motivo. El docente acepta o rechaza; mientras tanto se mantiene la versión aprobada.</dd>
      <dt><span class="badge">Libre</span></dt><dd>Repositorio y capturas: cámbialos cuando quieras.</dd>
    </dl>
    <p class="help" style="margin-top:12px">Todo queda en el registro de cambios que ve el docente.</p>
  </div>
</aside>
</div>

<?php foreach ($pend as $pd): ?>
  <form id="cancel-<?= (int)$pd['id'] ?>" method="post" action="<?= e(url('proyecto/cambio/cancelar')) ?>" hidden><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$pd['id'] ?>"></form>
<?php endforeach; ?>
<?php foreach ($capturas as $c): ?>
  <form id="del-<?= (int)$c['id'] ?>" method="post" action="<?= e(url('proyecto/captura/borrar')) ?>" data-confirm="¿Eliminar esta captura?" hidden><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"></form>
<?php endforeach; ?>
