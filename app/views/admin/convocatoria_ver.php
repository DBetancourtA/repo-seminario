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
    <thead><tr><th scope="col">Estudiante</th><th scope="col">Cédula</th><th scope="col">Proyecto</th><th scope="col">Estado</th><th scope="col"><span class="sr-only">Acciones</span></th></tr></thead>
    <tbody>
    <?php foreach ($estudiantes as $s): ?>
      <tr>
        <td><?= e($s['apellidos'] . ', ' . $s['nombres']) ?><div class="small muted"><?= e($s['email']) ?></div></td>
        <td class="mono small"><?= e($s['cedula']) ?></td>
        <td class="title-cell"><?= $s['pid'] ? '<a href="' . e(url('admin/proyecto', ['id' => $s['pid']])) . '">' . e($s['titulo']) . '</a>' : '<span class="muted">Sin registrar</span>' ?></td>
        <td><?php if ($s['estado']): ?><span class="badge badge-<?= e(['ok' => 'ok', 'warn' => 'warn', 'danger' => 'danger', 'neutral' => 'accent'][estado_tono($s['estado'])]) ?>"><?= e(estado_label($s['estado'])) ?></span><?php endif; ?></td>
        <td style="text-align:right">
          <form method="post" action="<?= e(url('admin/estudiante/clave')) ?>" data-confirm="¿Generar una contraseña temporal para <?= e($s['nombres']) ?>? La actual dejará de funcionar.">
            <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$s['uid'] ?>">
            <button class="btn btn-sm btn-ghost" type="submit" title="Restablecer contraseña"><?= icon('key', 14) ?> Contraseña</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>

<section class="section" aria-labelledby="h-entc" id="entregables">
  <div class="section-head"><h2 id="h-entc">Entregables</h2><span class="meta">Los estudiantes entregan un enlace por cada uno</span></div>
  <?php if (!$entDisponible): ?>
    <div class="note">Ejecuta <span class="mono">database/migracion_003_entregables.sql</span> en phpMyAdmin para activar los entregables.</div>
  <?php else: ?>
  <div class="ses-week">
    <?php foreach ($entLista as $en): ?>
      <form class="ent-row" method="post" action="<?= e(url('admin/entregables')) ?>">
        <?= csrf_field() ?><input type="hidden" name="conv" value="<?= (int)$c['id'] ?>"><input type="hidden" name="id" value="<?= (int)$en['id'] ?>">
        <label class="sr-only" for="en-n-<?= (int)$en['id'] ?>">Nombre</label>
        <input class="input" id="en-n-<?= (int)$en['id'] ?>" name="nombre" maxlength="120" value="<?= e($en['nombre']) ?>">
        <label class="sr-only" for="en-d-<?= (int)$en['id'] ?>">Descripción</label>
        <input class="input" id="en-d-<?= (int)$en['id'] ?>" name="descripcion" maxlength="255" placeholder="Descripción" value="<?= e($en['descripcion']) ?>">
        <label class="sr-only" for="en-s-<?= (int)$en['id'] ?>">Semana</label>
        <select class="select" id="en-s-<?= (int)$en['id'] ?>" name="semana"><?php for ($w = 1; $w <= (int)$c['semanas']; $w++): ?><option value="<?= $w ?>"<?= (int)$en['semana'] === $w ? ' selected' : '' ?>>Semana <?= $w ?></option><?php endfor; ?></select>
        <label class="sr-only" for="en-t-<?= (int)$en['id'] ?>">Tipo</label>
        <select class="select" id="en-t-<?= (int)$en['id'] ?>" name="tipo"><?php foreach (Entregables::TIPOS as $k => $l): ?><option value="<?= $k ?>"<?= $en['tipo'] === $k ? ' selected' : '' ?>><?= e(explode(' (', $l)[0]) ?></option><?php endforeach; ?></select>
        <button class="btn btn-sm" type="submit" name="accion" value="guardar">Guardar</button>
        <button class="linkbtn" type="submit" name="accion" value="eliminar" aria-label="Eliminar <?= e($en['nombre']) ?>" title="Eliminar"><?= icon('x', 16) ?></button>
      </form>
    <?php endforeach; ?>
    <form class="ent-row ent-new" method="post" action="<?= e(url('admin/entregables')) ?>">
      <?= csrf_field() ?><input type="hidden" name="conv" value="<?= (int)$c['id'] ?>">
      <label class="sr-only" for="en-nn">Nuevo entregable</label>
      <input class="input" id="en-nn" name="nombre" maxlength="120" placeholder="Nuevo entregable">
      <label class="sr-only" for="en-nd">Descripción</label>
      <input class="input" id="en-nd" name="descripcion" maxlength="255" placeholder="Descripción (opcional)">
      <label class="sr-only" for="en-ns">Semana</label>
      <select class="select" id="en-ns" name="semana"><?php for ($w = 1; $w <= (int)$c['semanas']; $w++): ?><option value="<?= $w ?>"><?= 'Semana ' . $w ?></option><?php endfor; ?></select>
      <label class="sr-only" for="en-nt">Tipo</label>
      <select class="select" id="en-nt" name="tipo"><?php foreach (Entregables::TIPOS as $k => $l): ?><option value="<?= $k ?>"><?= e(explode(' (', $l)[0]) ?></option><?php endforeach; ?></select>
      <button class="btn btn-sm" type="submit" name="accion" value="crear"><?= icon('plus', 14) ?> Agregar</button>
      <span></span>
    </form>
  </div>
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
