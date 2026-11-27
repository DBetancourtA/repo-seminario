<?php
$min = (int)cfg('app.min_actividad', 80);
$tipo = old('tipo', $a['tipo'] ?? 'codigo');
?>
<div class="page-head">
  <div>
    <p class="eyebrow"><a href="<?= e(url('panel')) ?>">Mi seminario</a><span aria-hidden="true">/</span><a href="<?= e(url('semana', ['n' => $s['semana']])) ?>">Semana <?= (int)$s['semana'] ?></a><span aria-hidden="true">/</span><span>Clase <?= (int)$s['numero'] ?></span></p>
    <h1><?= e(ucfirst(fecha_larga($s['fecha']))) ?></h1>
    <p class="lead"><?= $s['tema'] ? e($s['tema']) . ' · ' : '' ?>Clase de <?= e(substr($conv['hora_inicio'], 0, 5)) ?> a <?= e(substr($conv['hora_fin'], 0, 5)) ?></p>
  </div>
  <div>
    <?php if ($a): ?>
      <span class="badge <?= $a['tardio'] ? 'badge-warn' : 'badge-ok' ?>"><?= $a['tardio'] ? 'Registrado fuera de plazo' : 'Registrado a tiempo' ?></span>
    <?php elseif ($tarde): ?>
      <span class="badge badge-warn">Plazo vencido · quedará como tardío</span>
    <?php else: ?>
      <span class="badge badge-accent">Plazo hasta <?= e(fecha_corta($deadline->format('Y-m-d'))) ?>, <?= e($deadline->format('H:i')) ?></span>
    <?php endif; ?>
  </div>
</div>

<div class="grid-2">
<form class="form" method="post" action="<?= e(url('avance', ['sesion' => $s['id']])) ?>" enctype="multipart/form-data" novalidate data-busy="Subiendo…">
  <?= csrf_field() ?>
  <div class="field">
    <label for="actividad">¿Qué hiciste en esta clase? <span class="req" aria-hidden="true">*</span></label>
    <textarea class="textarea" id="actividad" name="actividad" rows="6" data-count data-min="<?= $min ?>" aria-describedby="act-help"<?= invalid('actividad') ?>><?= e(old('actividad', $a['actividad'] ?? '')) ?></textarea>
    <div style="display:flex;justify-content:space-between;gap:12px">
      <p class="help" id="act-help">Sé concreto: qué módulo, qué función, qué problema resolviste.</p>
      <span class="counter" id="actividad-count" aria-live="polite"></span>
    </div>
    <?= field_error('actividad') ?>
  </div>

  <div class="row">
    <div class="field">
      <label for="tipo">Tipo de evidencia <span class="req" aria-hidden="true">*</span></label>
      <select class="select" id="tipo" name="tipo"<?= invalid('tipo') ?>>
        <?php foreach (['codigo', 'interfaz', 'base_datos', 'documentacion', 'otro'] as $t): ?>
          <option value="<?= $t ?>"<?= $tipo === $t ? ' selected' : '' ?>><?= e(tipo_label($t)) ?></option>
        <?php endforeach; ?>
      </select>
      <?= field_error('tipo') ?>
    </div>
    <div class="field">
      <label for="enlace">Commit o enlace <span class="opt">(opcional)</span></label>
      <input class="input mono" id="enlace" name="enlace" type="url" placeholder="https://github.com/…/commit/…" value="<?= e(old('enlace', $a['enlace'] ?? '')) ?>"<?= invalid('enlace') ?>>
      <?= field_error('enlace') ?>
      <p class="repo-status" data-repo-for="enlace" data-correo="<?= e((string)cfg('app.correo_docente', '')) ?>" role="status" aria-live="polite" hidden></p>
    </div>
  </div>

  <div class="field">
    <span class="label">Capturas de pantalla <span class="req" aria-hidden="true">*</span> <span class="opt">(1 a 3: código o proyecto funcionando)</span></span>
    <?php if ($caps): ?>
      <div class="shots">
        <?php foreach ($caps as $c): $src = url('archivo', ['t' => 'avance', 'id' => $c['id']]); ?>
          <div class="shot">
            <a href="<?= e($src) ?>" target="_blank" rel="noopener"><img src="<?= e($src) ?>" alt="Captura registrada" loading="lazy"></a>
            <label class="remove"><input type="checkbox" name="quitar[]" value="<?= (int)$c['id'] ?>"> Quitar al guardar</label>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="drop" data-previews="prev-av" data-max="3">
      <input type="file" name="capturas[]" accept="image/png,image/jpeg,image/webp" multiple aria-label="Subir capturas de la clase"<?= invalid('capturas') ?>>
      <?= icon('image', 22) ?>
      <strong><?= $caps ? 'Agregar más capturas' : 'Arrastra tus capturas o haz clic para elegir' ?></strong>
      <span>PNG, JPG o WebP · máx. <?= (int)cfg('app.max_mb', 5) ?> MB c/u</span>
    </div>
    <p class="field-error" id="prev-av-msg" aria-live="polite"></p>
    <div class="previews" id="prev-av"></div>
    <?= field_error('capturas') ?>
  </div>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit"><?= $a ? 'Guardar cambios' : 'Registrar avance' ?></button>
    <a class="btn btn-ghost" href="<?= e(url('semana', ['n' => $s['semana']])) ?>">Cancelar</a>
  </div>
</form>

<aside>
  <div class="panel panel-pad">
    <h2 style="font-size:15px;margin-bottom:10px">Una buena evidencia</h2>
    <ul style="margin:0;padding-left:18px;display:grid;gap:8px;font-size:14px;color:var(--ink-2)">
      <li>Muestra el código que escribiste hoy, con el nombre del archivo visible.</li>
      <li>O la pantalla funcionando con datos reales de prueba.</li>
      <li>Evita fotos del monitor: usa la herramienta de recorte (<span class="mono">Win + Shift + S</span>).</li>
    </ul>
  </div>
  <?php if ($comentarios): ?>
    <div class="section" style="margin-top:24px">
      <div class="section-head"><h2>Comentarios del docente</h2></div>
      <div class="comments" style="border:0;padding:0">
        <?php foreach ($comentarios as $c): ?>
          <div class="comment"><div class="who"><?= e($c['nombres'] . ' ' . $c['apellidos']) ?> · <?= e(fecha_hora($c['created_at'])) ?></div><?= nl2br(e($c['texto'])) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</aside>
</div>
