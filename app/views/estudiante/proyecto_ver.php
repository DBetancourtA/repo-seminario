<?php $nPend = count(array_filter($cambios, fn($c) => $c['estado'] === 'pendiente')); ?>
<div class="page-head">
  <div>
    <p class="eyebrow"><a href="<?= e(url('panel')) ?>">Mi seminario</a><span aria-hidden="true">/</span><span>Ficha del proyecto</span></p>
    <h1><?= e($p['titulo']) ?></h1>
  </div>
  <div class="actions">
    <?php if ($p['estado'] === 'rechazado'): ?>
      <a class="btn btn-primary" href="<?= e(url('proyecto', ['nuevo' => 1])) ?>"><?= icon('plus', 16) ?> Nueva propuesta</a>
    <?php else: ?>
      <?php if ($puedeAjustar): ?><a class="btn btn-primary" href="<?= e(url('proyecto/ajustes')) ?>"><?= icon('edit', 16) ?> Ajustar ficha<?= $nPend ? ' · ' . $nPend . ' pendiente' . ($nPend > 1 ? 's' : '') : '' ?></a><?php endif; ?>
      <?php if (ProjectState::techEditable($p['estado'])): ?><a class="btn" href="<?= e(url('tecnologias')) ?>">Tecnologías</a><?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php partial('workflow', ['p' => $p, 'conv' => $conv, 'actual' => Calendar::currentWeek((int)$conv['id'])]); ?>

<?php if (!$puedeAjustar && in_array($p['estado'], ['aprobado', 'tecnologias_definidas', 'en_desarrollo', 'finalizado'], true) && $congela && date('Y-m-d') >= $congela): ?>
  <div class="note" style="margin-bottom:24px"><strong>Ficha cerrada</strong>Desde la semana <?= (int)$conv['semana_congelamiento'] ?> solo el docente puede modificar la ficha del proyecto. Si necesitas una corrección, pídesela en clase.</div>
<?php endif; ?>

<div class="grid-2">
  <section aria-labelledby="ficha">
    <div class="section-head"><h2 id="ficha">Ficha</h2></div>
    <?php partial('ficha', compact('p', 'objetivos', 'capturas', 'tecs')); ?>

    <?php if ($cambios): ?>
    <div class="section">
      <div class="section-head"><h2>Registro de cambios</h2><span class="meta"><?= count($cambios) ?></span></div>
      <?php partial('cambios', ['cambios' => $cambios]); ?>
    </div>
    <?php endif; ?>
  </section>
  <aside aria-labelledby="hist">
    <div class="section-head"><h2 id="hist">Historial</h2></div>
    <?php partial('historial', ['historial' => $historial]); ?>
  </aside>
</div>
