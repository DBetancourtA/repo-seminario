<?php
$e = $p['estado'];
$semanaSel = max(1, min((int)$conv['semanas'], $semanaSel));
$sesionesSel = $weeks[$semanaSel] ?? [];
?>
<div class="page-head">
  <div>
    <p class="eyebrow"><a href="<?= e(url('admin/proyectos')) ?>">Proyectos</a><span aria-hidden="true">/</span><span><?= e($p['apellidos'] . ', ' . $p['nombres']) ?></span></p>
    <h1><?= e($p['titulo']) ?></h1>
    <p class="lead"><?= e($p['conv_nombre']) ?> · registrado el <?= e(fecha_hora($p['created_at'])) ?></p>
  </div>
  <a class="btn" href="<?= e(url('admin/proyecto/editar', ['id' => $p['id']])) ?>"><?= icon('edit', 16) ?> Editar ficha</a>
</div>

<?php partial('workflow', ['p' => $p, 'conv' => $conv, 'actual' => Calendar::currentWeek((int)$conv['id']), 'compact' => true]); ?>

<div class="grid-2">
  <div>
    <section aria-labelledby="h-ficha">
      <div class="section-head"><h2 id="h-ficha">Propuesta</h2></div>
      <?php partial('ficha', compact('p', 'objetivos', 'capturas', 'tecs')); ?>
    </section>

    <?php if ($cambios): ?>
    <section class="section" aria-labelledby="h-cambios" id="cambios">
      <div class="section-head"><h2 id="h-cambios">Registro de cambios</h2><span class="meta"><?= count($cambios) ?></span></div>
      <?php partial('cambios', ['cambios' => $cambios]); ?>
    </section>
    <?php endif; ?>

    <section class="section" aria-labelledby="h-bit" id="bitacora">
      <div class="section-head"><h2 id="h-bit">Bitácora</h2><span class="meta"><?= count($avances) ?> clases registradas</span></div>
      <nav class="week-nav" aria-label="Semanas" style="margin-bottom:16px">
        <?php foreach ($weeks as $w => $ss): $has = $disp[$w]['hechas'] > 0; ?>
          <a class="<?= $has ? 'has' : '' ?>" href="<?= e(url('admin/proyecto', ['id' => $p['id'], 'semana' => $w])) ?>#bitacora"<?= $w === $semanaSel ? ' aria-current="true"' : '' ?> title="Semana <?= $w ?>: <?= $disp[$w]['hechas'] ?>/<?= $disp[$w]['total'] ?>"><?= sprintf('%02d', $w) ?></a>
        <?php endforeach; ?>
      </nav>
      <?php if ($sesionesSel): ?>
        <div class="report-box" style="margin-bottom:16px">
          <div><strong style="display:block">Semana <?= $semanaSel ?></strong><p><?= $disp[$semanaSel]['hechas'] ?> de <?= $disp[$semanaSel]['total'] ?> clases registradas</p></div>
          <?php if ($disp[$semanaSel]['ok']): ?>
            <a class="btn btn-sm" href="<?= e(url('reporte', ['proyecto' => $p['id'], 'semana' => $semanaSel])) ?>"><?= icon('download', 14) ?> PDF de la semana</a>
          <?php endif; ?>
        </div>
        <?php partial('bitacora', ['sesiones' => $sesionesSel, 'avances' => $avances, 'conv' => $conv, 'admin' => true]); ?>
      <?php endif; ?>
    </section>
  </div>

  <aside class="review" aria-label="Revisión">
    <?php foreach ($pendientes as $c): ?>
      <div class="pending-box">
        <div class="adjust-head"><strong style="font-size:14px">Solicitud de cambio · <?= e(Cambios::label($c['campo'])) ?></strong><span class="when small muted"><?= e(fecha_corta($c['created_at'])) ?></span></div>
        <div class="diff">
          <div class="diff-old"><span class="diff-tag">Actual</span><?= $c['valor_anterior'] !== '' && $c['valor_anterior'] !== null ? nl2br(e($c['valor_anterior'])) : '<em class="muted">vacío</em>' ?></div>
          <div class="diff-new"><span class="diff-tag">Propuesto</span><?= nl2br(e($c['valor_nuevo'])) ?></div>
        </div>
        <p class="small"><span class="muted">Motivo:</span> <?= e($c['motivo']) ?></p>
        <form method="post" action="<?= e(url('admin/cambio')) ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <label class="sr-only" for="cr-<?= (int)$c['id'] ?>">Comentario</label>
          <input class="input" id="cr-<?= (int)$c['id'] ?>" name="comentario" maxlength="500" placeholder="Comentario (opcional)">
          <div class="actions">
            <button class="btn btn-sm btn-ok" type="submit" name="accion" value="aceptar"><?= icon('check', 14) ?> Aceptar</button>
            <button class="btn btn-sm btn-danger" type="submit" name="accion" value="rechazar">Rechazar</button>
          </div>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (in_array($e, ['en_revision', 'observado', 'aprobado'], true)): ?>
      <form class="panel panel-pad form" method="post" action="<?= e(url('admin/proyecto/estado')) ?>" style="gap:12px">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <h2 style="font-size:15px"><?= $e === 'aprobado' ? 'Proyecto aprobado' : 'Revisión de la propuesta' ?></h2>
        <?php if ($e === 'observado'): ?><p class="help">Esperando correcciones del estudiante. Puedes aprobar o rechazar igualmente.</p><?php endif; ?>
        <div class="field">
          <label for="comentario">Comentario para el estudiante <span class="opt">(obligatorio si observas o rechazas)</span></label>
          <textarea class="textarea" id="comentario" name="comentario"<?= invalid('comentario') ?>><?= e(old('comentario')) ?></textarea>
          <?= field_error('comentario') ?>
        </div>
        <?php if ($e === 'aprobado'): ?>
          <button class="btn btn-warn" type="submit" name="accion" value="observar">Devolver con observaciones</button>
        <?php else: ?>
        <div class="review-actions">
          <button class="btn btn-ok" type="submit" name="accion" value="aprobar"><?= icon('check', 16) ?> Aprobar</button>
          <?php if ($e === 'en_revision'): ?><button class="btn btn-warn" type="submit" name="accion" value="observar">Observar</button><?php endif; ?>
          <button class="btn btn-danger" type="submit" name="accion" value="rechazar">Rechazar</button>
        </div>
        <?php endif; ?>
      </form>
    <?php elseif (in_array($e, ['tecnologias_definidas', 'en_desarrollo'], true)): ?>
      <form class="panel panel-pad form" method="post" action="<?= e(url('admin/proyecto/estado')) ?>" style="gap:12px" data-confirm="¿Marcar el proyecto como finalizado? El estudiante ya no podrá registrar avances.">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <h2 style="font-size:15px">Cierre</h2>
        <p class="help">Cuando el estudiante concluya el seminario, marca el proyecto como finalizado.</p>
        <div class="field"><label for="comentario">Comentario <span class="opt">(opcional)</span></label><textarea class="textarea" id="comentario" name="comentario" style="min-height:70px"></textarea></div>
        <button class="btn" type="submit" name="accion" value="finalizar">Marcar como finalizado</button>
      </form>
    <?php elseif ($e === 'finalizado'): ?>
      <form class="panel panel-pad form" method="post" action="<?= e(url('admin/proyecto/estado')) ?>" style="gap:12px">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <h2 style="font-size:15px">Proyecto finalizado</h2>
        <button class="btn" type="submit" name="accion" value="reabrir">Reabrir (volver a desarrollo)</button>
      </form>
    <?php endif; ?>

    <div>
      <div class="section-head"><h2>Historial</h2></div>
      <?php partial('historial', ['historial' => $historial]); ?>
    </div>
  </aside>
</div>
