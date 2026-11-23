<?php
$puede = $p && ProjectState::canLogProgress($p['estado']);
$first = $sesiones[0]['fecha']; $last = end($sesiones)['fecha'];
?>
<div class="page-head">
  <div>
    <p class="eyebrow"><a href="<?= e(url('panel')) ?>">Mi seminario</a><span aria-hidden="true">/</span><span>Semana <?= $n ?> de <?= (int)$conv['semanas'] ?></span></p>
    <h1>Semana <?= $n ?><?= $n === 1 ? ' · Definición del proyecto' : '' ?></h1>
    <p class="lead">Del <?= e(fecha_larga($first)) ?> al <?= e(fecha_larga($last)) ?></p>
  </div>
  <nav class="week-nav" aria-label="Semanas">
    <?php for ($i = 1; $i <= (int)$conv['semanas']; $i++): ?>
      <a href="<?= e(url('semana', ['n' => $i])) ?>"<?= $i === $n ? ' aria-current="true"' : '' ?>><?= sprintf('%02d', $i) ?></a>
    <?php endfor; ?>
  </nav>
</div>

<div class="report-box" style="margin-bottom:24px">
  <div>
    <strong style="display:block">Reporte semanal en PDF</strong>
    <p><?php if ($disp['ok']): ?>
      Listo para descargar. Adjúntalo en la tarea de la semana <?= $n ?> en el EVA.
    <?php else: ?>
      Se habilita cuando registres todas las clases o cuando termine la semana. Llevas <?= $disp['hechas'] ?> de <?= $disp['total'] ?>.
    <?php endif; ?></p>
  </div>
  <?php if ($disp['ok']): ?>
    <a class="btn btn-primary" href="<?= e(url('reporte', ['semana' => $n])) ?>"><?= icon('download', 16) ?> Descargar PDF</a>
  <?php else: ?>
    <span class="btn" aria-disabled="true"><?= icon('download', 16) ?> Descargar PDF</span>
  <?php endif; ?>
</div>

<?php partial('bitacora', [
  'sesiones' => $sesiones, 'avances' => $avances, 'conv' => $conv,
  'editLink' => $puede ? fn($s) => url('avance', ['sesion' => $s['id']]) : null,
]); ?>
