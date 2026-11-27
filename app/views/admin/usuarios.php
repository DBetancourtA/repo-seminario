<?php
$tabs = ['' => 'Todos', 'activos' => 'Activos', 'sin_proyecto' => 'Sin proyecto', 'desactivados' => 'Desactivados'];
$hace = function (?string $dt): string {
    if (!$dt) return '—';
    $d = (new DateTime())->diff(new DateTime($dt));
    if ($d->days === 0) return 'hoy';
    if ($d->days === 1) return 'ayer';
    return 'hace ' . $d->days . ' días';
};
$base = array_filter(['q' => $f['q'], 'conv' => $f['conv'] ?: null, 'rol' => $f['rol']]);
?>
<div class="page-head">
  <div>
    <p class="eyebrow">Administración</p>
    <h1>Usuarios</h1>
    <p class="lead">Todas las cuentas de la plataforma. Desactivar impide el ingreso, pero conserva el proyecto y la bitácora.</p>
  </div>
  <a class="btn" href="<?= e(url('admin/usuarios/exportar', array_filter($f))) ?>"><?= icon('download', 16) ?> Exportar CSV</a>
</div>

<nav class="tabs" aria-label="Filtrar por estado">
  <?php foreach ($tabs as $k => $l): ?>
    <a href="<?= e(url('admin/usuarios', $base + array_filter(['estado' => $k]))) ?>"<?= $f['estado'] === $k ? ' aria-current="true"' : '' ?>><?= e($l) ?><span class="count"><?= (int)$conteo[$k] ?></span></a>
  <?php endforeach; ?>
</nav>

<form class="toolbar" method="get" action="<?= e(base_url() . '/index.php') ?>" role="search">
  <input type="hidden" name="r" value="admin/usuarios">
  <?php if ($f['estado'] !== ''): ?><input type="hidden" name="estado" value="<?= e($f['estado']) ?>"><?php endif; ?>
  <label class="sr-only" for="q">Buscar</label>
  <input class="input" id="q" name="q" type="search" placeholder="Nombre, cédula o correo" value="<?= e($f['q']) ?>">
  <label class="sr-only" for="conv">Convocatoria</label>
  <select class="select" id="conv" name="conv">
    <option value="">Todas las convocatorias</option>
    <?php foreach ($convs as $c): ?><option value="<?= (int)$c['id'] ?>"<?= (int)$f['conv'] === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['nombre'] . ' · ' . $c['periodo']) ?></option><?php endforeach; ?>
  </select>
  <label class="sr-only" for="rol">Rol</label>
  <select class="select" id="rol" name="rol" style="min-width:150px">
    <option value="">Todos los roles</option>
    <option value="estudiante"<?= $f['rol'] === 'estudiante' ? ' selected' : '' ?>>Estudiantes</option>
    <option value="admin"<?= $f['rol'] === 'admin' ? ' selected' : '' ?>>Docentes</option>
  </select>
  <button class="btn" type="submit">Filtrar</button>
  <?php if ($base): ?><a class="btn btn-ghost" href="<?= e(url('admin/usuarios', array_filter(['estado' => $f['estado']]))) ?>">Limpiar</a><?php endif; ?>
</form>

<?php if (!$hayUltimo): ?>
  <div class="note" style="margin-bottom:16px"><strong>Falta un paso para ver el último ingreso</strong>Ejecuta una vez <span class="mono">database/migracion_002_usuarios.sql</span> en phpMyAdmin. Lo demás funciona igual.</div>
<?php endif; ?>

<?php if (!$rows): ?>
  <div class="panel empty"><strong>No hay usuarios con este filtro</strong>Prueba con otra búsqueda.</div>
<?php else: ?>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th scope="col">Usuario</th><th scope="col">Cédula</th><th scope="col">Convocatoria</th><th scope="col">Proyecto</th><th scope="col">Último ingreso</th><th scope="col"><span class="sr-only">Acciones</span></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr<?= $r['activo'] ? '' : ' class="row-off"' ?>>
        <td class="title-cell">
          <a href="<?= e(url('admin/usuario', ['id' => $r['id']])) ?>"><?= e($r['apellidos'] . ', ' . $r['nombres']) ?></a>
          <?php if ($r['rol'] === 'admin'): ?> <span class="badge badge-accent">Docente</span><?php endif; ?>
          <?php if (!$r['activo']): ?> <span class="badge badge-danger">Desactivado</span><?php endif; ?>
          <div class="small muted"><?= e($r['email']) ?></div>
        </td>
        <td class="mono small"><?= e($r['cedula']) ?></td>
        <td class="small"><?= $r['conv_nombre'] ? e($r['conv_nombre']) . '<div class="muted">' . e($r['conv_periodo']) . '</div>' : '<span class="muted">—</span>' ?></td>
        <td class="small">
          <?php if ($r['pid']): ?>
            <a href="<?= e(url('admin/proyecto', ['id' => $r['pid']])) ?>"><?= e(mb_strimwidth($r['titulo'], 0, 48, '…')) ?></a><br>
            <span class="badge badge-<?= e(['ok' => 'ok', 'warn' => 'warn', 'danger' => 'danger', 'neutral' => 'accent'][estado_tono($r['estado'])]) ?>" style="margin-top:4px"><?= e(estado_label($r['estado'])) ?></span>
          <?php elseif ($r['rol'] === 'estudiante'): ?><span class="muted">Sin proyecto</span><?php else: ?><span class="muted">—</span><?php endif; ?>
        </td>
        <td class="small nowrap" title="<?= e($r['ultimo_ingreso'] ? fecha_hora($r['ultimo_ingreso']) : 'Sin registro') ?>"><?= e($hace($r['ultimo_ingreso'])) ?></td>
        <td style="text-align:right"><a class="btn btn-sm btn-ghost" href="<?= e(url('admin/usuario', ['id' => $r['id']])) ?>">Administrar <?= icon('arrow', 14) ?></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="help" style="margin-top:8px"><?= count($rows) ?> usuario<?= count($rows) === 1 ? '' : 's' ?></p>
<?php endif; ?>
