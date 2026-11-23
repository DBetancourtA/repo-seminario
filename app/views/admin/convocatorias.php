<?php $estadoTono = ['borrador' => '', 'abierta' => 'badge-accent', 'en_curso' => 'badge-ok', 'cerrada' => '']; ?>
<div class="page-head">
  <div>
    <p class="eyebrow">Administración</p>
    <h1>Convocatorias</h1>
    <p class="lead">Cada convocatoria es un seminario con su calendario y su código de inscripción.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(url('admin/convocatoria')) ?>"><?= icon('plus', 16) ?> Nueva convocatoria</a>
</div>

<?php if (!$rows): ?>
  <div class="panel empty"><strong>Aún no hay convocatorias</strong>Crea la primera para generar el calendario y el código de inscripción.</div>
<?php else: ?>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th scope="col">Convocatoria</th><th scope="col">Código</th><th scope="col">Inicio</th><th scope="col">Horario</th><th scope="col" class="num">Inscritos</th><th scope="col" class="num">Proyectos</th><th scope="col">Estado</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="title-cell"><a href="<?= e(url('admin/convocatoria/ver', ['id' => $r['id']])) ?>"><?= e($r['nombre']) ?></a><div class="small muted"><?= e($r['periodo']) ?> · <?= (int)$r['semanas'] ?> semanas</div></td>
          <td class="mono"><?= e($r['codigo_inscripcion']) ?></td>
          <td class="nowrap"><?= e(fecha_corta($r['fecha_inicio'])) ?> <?= substr($r['fecha_inicio'], 0, 4) ?></td>
          <td class="small"><?= e(Calendar::diasLabel($r['dias_clase'])) ?><br><span class="muted"><?= e(substr($r['hora_inicio'], 0, 5)) ?>–<?= e(substr($r['hora_fin'], 0, 5)) ?></span></td>
          <td class="num"><?= (int)$r['inscritos'] ?></td>
          <td class="num"><?= (int)$r['proyectos'] ?><?php if ($r['pendientes']): ?> <a class="small" href="<?= e(url('admin/proyectos', ['conv' => $r['id'], 'estado' => 'en_revision'])) ?>">(<?= (int)$r['pendientes'] ?> por revisar)</a><?php endif; ?></td>
          <td><span class="badge <?= $estadoTono[$r['estado']] ?>"><?= e(['borrador' => 'Borrador', 'abierta' => 'Inscripciones abiertas', 'en_curso' => 'En curso', 'cerrada' => 'Cerrada'][$r['estado']]) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
