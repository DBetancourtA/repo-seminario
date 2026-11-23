<?php $registro = url('registro', ['codigo' => $c['codigo_inscripcion']]); ?>
<div class="page-head">
  <div>
    <p class="eyebrow"><a href="<?= e(url('admin/convocatorias')) ?>">Convocatorias</a><span aria-hidden="true">/</span><span><?= e($c['periodo']) ?></span></p>
    <h1><?= e($c['nombre']) ?></h1>
    <p class="lead"><?= e($c['carrera']) ?> · <?= (int)$c['semanas'] ?> semanas · <?= e(Calendar::diasLabel($c['dias_clase'])) ?> de <?= e(substr($c['hora_inicio'], 0, 5)) ?> a <?= e(substr($c['hora_fin'], 0, 5)) ?></p>
  </div>
  <div class="actions">
    <a class="btn" href="<?= e(url('admin/convocatoria', ['id' => $c['id']])) ?>"><?= icon('edit', 16) ?> Editar</a>
    <a class="btn" href="<?= e(url('admin', ['conv' => $c['id']])) ?>">Tablero</a>
  </div>
</div>

<div class="panel panel-pad" style="display:flex;justify-content:space-between;gap:24px;flex-wrap:wrap;align-items:center">
  <div>
    <p class="eyebrow" style="margin-bottom:6px">Código de inscripción</p>
    <div class="copy-box"><span class="code-big"><?= e($c['codigo_inscripcion']) ?></span>
      <button class="btn btn-sm" type="button" data-copy="<?= e($c['codigo_inscripcion']) ?>">Copiar código</button></div>
  </div>
  <div style="min-width:0;flex:1;max-width:520px">
    <p class="eyebrow" style="margin-bottom:6px">Enlace directo para estudiantes</p>
    <div class="copy-box"><span class="mono small" style="overflow-wrap:anywhere"><?= e($registro) ?></span>
      <button class="btn btn-sm" type="button" data-copy="<?= e($registro) ?>">Copiar enlace</button></div>
  </div>
</div>

<section class="section" aria-labelledby="h-est">
  <div class="section-head"><h2 id="h-est">Estudiantes inscritos</h2><span class="meta"><?= count($estudiantes) ?></span></div>
  <?php if (!$estudiantes): ?>
    <div class="panel empty"><strong>Nadie se ha inscrito todavía</strong>Comparte el código o el enlace de arriba.</div>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th scope="col">Estudiante</th><th scope="col">Cédula</th><th scope="col">Proyecto</th><th scope="col">Estado</th></tr></thead>
    <tbody>
    <?php foreach ($estudiantes as $s): ?>
      <tr>
        <td><?= e($s['apellidos'] . ', ' . $s['nombres']) ?><div class="small muted"><?= e($s['email']) ?></div></td>
        <td class="mono small"><?= e($s['cedula']) ?></td>
        <td class="title-cell"><?= $s['pid'] ? '<a href="' . e(url('admin/proyecto', ['id' => $s['pid']])) . '">' . e($s['titulo']) . '</a>' : '<span class="muted">Sin registrar</span>' ?></td>
        <td><?php if ($s['estado']): ?><span class="badge badge-<?= e(['ok' => 'ok', 'warn' => 'warn', 'danger' => 'danger', 'neutral' => 'accent'][estado_tono($s['estado'])]) ?>"><?= e(estado_label($s['estado'])) ?></span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>

<section class="section" aria-labelledby="h-cal">
  <div class="section-head"><h2 id="h-cal">Calendario de clases</h2><span class="meta">Cambia la fecha, ponle tema o suspende una clase (feriados).</span></div>
  <?php foreach ($weeks as $w => $ss): ?>
    <div class="ses-week<?= $w === $actual ? ' is-current' : '' ?>" id="semana-<?= $w ?>">
      <header><span>Semana <?= sprintf('%02d', $w) ?><?= $w === 1 ? ' · Definición' : '' ?><?= $w === $actual ? ' · en curso' : '' ?></span><span><?= e(fecha_corta($ss[0]['fecha'])) ?> – <?= e(fecha_corta(end($ss)['fecha'])) ?></span></header>
      <?php foreach ($ss as $s): ?>
        <form class="ses-row" method="post" action="<?= e(url('admin/sesion')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
          <span class="mono small">Clase <?= (int)$s['numero'] ?></span>
          <label class="sr-only" for="f<?= (int)$s['id'] ?>">Fecha</label>
          <input class="input" id="f<?= (int)$s['id'] ?>" type="date" name="fecha" value="<?= e($s['fecha']) ?>">
          <label class="sr-only" for="t<?= (int)$s['id'] ?>">Tema</label>
          <input class="input tema" id="t<?= (int)$s['id'] ?>" name="tema" maxlength="150" placeholder="Tema de la clase (opcional)" value="<?= e($s['tema']) ?>">
          <label class="sr-only" for="e<?= (int)$s['id'] ?>">Estado</label>
          <select class="select" id="e<?= (int)$s['id'] ?>" name="estado">
            <option value="normal"<?= $s['estado'] === 'normal' ? ' selected' : '' ?>>Normal</option>
            <option value="suspendida"<?= $s['estado'] === 'suspendida' ? ' selected' : '' ?>>Suspendida</option>
          </select>
          <button class="btn btn-sm" type="submit">Guardar</button>
        </form>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</section>
