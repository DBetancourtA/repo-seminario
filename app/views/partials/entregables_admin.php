<?php /** @var array $lista @var array $entregas @var array $conv */ ?>
<section class="section" aria-labelledby="h-ent" id="entregables">
  <div class="section-head"><h2 id="h-ent">Entregables</h2>
    <span class="meta"><?= count(array_filter($entregas, fn($x) => $x['estado'] === 'aprobado')) ?> de <?= count($lista) ?> aprobados</span></div>
  <div class="deliverables">
  <?php foreach ($lista as $ent):
    $en = $entregas[(int)$ent['id']] ?? null;
    $st = Entregables::estado($en, $conv, $ent);
    $lim = Entregables::limite($conv, (int)$ent['semana']);
    $emb = $en ? Entregables::embed($en['url']) : null;
  ?>
    <article class="deliv deliv-<?= e($st) ?>">
      <div class="deliv-head">
        <div>
          <h3><?= e($ent['nombre']) ?></h3>
          <p class="small muted">Semana <?= (int)$ent['semana'] ?><?= $lim ? ' · hasta el ' . e(fecha_corta($lim)) : '' ?>
            <?php if ($en): ?> · entregado el <?= e(fecha_hora($en['entregado_at'])) ?><?= $lim && substr($en['entregado_at'], 0, 10) > $lim ? ' <b>(tarde)</b>' : '' ?><?php endif; ?></p>
        </div>
        <span class="badge <?= e(Entregables::estadoBadge($st)) ?>"><?= e(Entregables::estadoLabel($st)) ?></span>
      </div>
      <?php if ($en): ?>
        <p class="small"><a href="<?= e($en['url']) ?>" target="_blank" rel="noopener"><?= icon('link', 14) ?> Abrir en <?= e(Entregables::servicio($en['url'])) ?></a></p>
        <?php if ($emb): ?>
          <details class="preview" data-src="<?= e($emb) ?>">
            <summary>Ver aquí</summary>
            <div class="preview-frame"><iframe title="Vista previa de <?= e($ent['nombre']) ?>" loading="lazy" allow="autoplay; fullscreen" allowfullscreen></iframe></div>
            <p class="help">Si aparece "Necesitas acceso", inicia sesión en Google con tu correo institucional o usa "No tengo acceso".</p>
          </details>
        <?php endif; ?>
        <?php if ($en['comentario']): ?><p class="small"><span class="muted">Tu comentario:</span> <?= e($en['comentario']) ?></p><?php endif; ?>
        <form class="review-row" method="post" action="<?= e(url('admin/entrega')) ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$en['id'] ?>">
          <?php if ($en['estado'] === 'aprobado'): ?>
            <button class="btn btn-sm" type="submit" name="accion" value="reabrir">Reabrir</button>
          <?php else: ?>
            <label class="sr-only" for="c-<?= (int)$en['id'] ?>">Comentario</label>
            <input class="input" id="c-<?= (int)$en['id'] ?>" name="comentario" maxlength="1000" placeholder="Comentario (obligatorio para observar)">
            <button class="btn btn-sm btn-ok" type="submit" name="accion" value="aprobar"><?= icon('check', 14) ?> Aprobar</button>
            <button class="btn btn-sm btn-warn" type="submit" name="accion" value="observar">Observar</button>
            <button class="btn btn-sm" type="submit" name="accion" value="sin_acceso" title="Le pide al estudiante que te comparta el archivo">No tengo acceso</button>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
  </div>
</section>
