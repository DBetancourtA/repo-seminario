<?php
$tabs = ['' => 'Todos', 'en_revision' => 'Por revisar', 'observado' => 'Observados', 'aprobado' => 'Aprobados', 'tecnologias_definidas' => 'Con stack', 'en_desarrollo' => 'En desarrollo', 'finalizado' => 'Finalizados', 'rechazado' => 'Rechazados'];
$total = array_sum($conteo);
?>
<div class="page-head">
  <div>
    <p class="eyebrow"><span>Proyectos</span><?php if ($conv): ?><span aria-hidden="true">·</span><span><?= e($conv['periodo']) ?></span><?php endif; ?></p>
    <h1><?= $conv ? e($conv['nombre']) : 'Proyectos' ?></h1>
  </div>
  <?php if ($conv): ?><a class="btn" href="<?= e(url('admin/exportar', ['conv' => $conv['id']])) ?>"><?= icon('download', 16) ?> Exportar CSV</a><?php endif; ?>
</div>

<nav class="tabs" aria-label="Filtrar por estado">
  <?php $tabs = array_slice($tabs, 0, 2, true) + ['cambios' => 'Cambios pendientes'] + array_slice($tabs, 2, null, true);
  foreach ($tabs as $k => $l): $n = $k === '' ? $total : ($k === 'cambios' ? $conteoCambios : (int)($conteo[$k] ?? 0)); ?>
    <a href="<?= e(url('admin/proyectos', array_filter(['estado' => $k, 'q' => $q]))) ?>"<?= $estado === $k ? ' aria-current="true"' : '' ?>><?= e($l) ?><span class="count"><?= $n ?></span></a>
  <?php endforeach; ?>
</nav>

<form class="toolbar" method="get" action="<?= e(base_url() . '/index.php') ?>" role="search">
  <input type="hidden" name="r" value="admin/proyectos">
  <?php if ($estado !== ''): ?><input type="hidden" name="estado" value="<?= e($estado) ?>"><?php endif; ?>
  <label class="sr-only" for="q">Buscar</label>
  <input class="input" id="q" name="q" type="search" placeholder="Buscar por título, nombre o cédula" value="<?= e($q) ?>">
  <?php if (count($convs) > 1): ?>
    <label class="sr-only" for="conv">Convocatoria</label>
    <select class="select" id="conv" name="conv">
      <?php foreach ($convs as $c): ?><option value="<?= (int)$c['id'] ?>"<?= (int)$c['id'] === (int)($conv['id'] ?? 0) ? ' selected' : '' ?>><?= e($c['nombre'] . ' · ' . $c['periodo']) ?></option><?php endforeach; ?>
    </select>
  <?php endif; ?>
  <button class="btn" type="submit">Buscar</button>
</form>

<?php if (!$rows): ?>
  <div class="panel empty"><strong>No hay proyectos con este filtro</strong><?= $q !== '' ? 'Prueba con otra búsqueda.' : 'Cuando los estudiantes registren sus proyectos aparecerán aquí.' ?></div>
<?php else: ?>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th scope="col">Proyecto</th><th scope="col">Estudiante</th><th scope="col">Estado</th><th scope="col" class="num">Avances</th><th scope="col">Actualizado</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="title-cell"><a href="<?= e(url('admin/proyecto', ['id' => $r['id']])) ?>"><?= e($r['titulo']) ?></a><?php if ($r['cambios_pend']): ?> <span class="badge badge-accent"><?= (int)$r['cambios_pend'] ?> cambio<?= $r['cambios_pend'] > 1 ? 's' : '' ?> por revisar</span><?php endif; ?></td>
          <td><?= e($r['apellidos'] . ', ' . $r['nombres']) ?><div class="mono small muted"><?= e($r['cedula']) ?></div></td>
          <td><span class="badge badge-<?= e(['ok' => 'ok', 'warn' => 'warn', 'danger' => 'danger', 'neutral' => 'accent'][estado_tono($r['estado'])]) ?>"><?= e(estado_label($r['estado'])) ?></span></td>
          <td class="num"><?= (int)$r['avances'] ?></td>
          <td class="nowrap small muted"><?= e(fecha_hora($r['updated_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
