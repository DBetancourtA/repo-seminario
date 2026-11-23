<?php
/** @var array $sesiones @var array $avances @var array $conv @var ?callable $editLink @var bool $admin */
$admin = $admin ?? false;
?>
<div class="log">
<?php foreach ($sesiones as $s):
    $a = $avances[(int)$s['id']] ?? null;
    $cls = $s['estado'] === 'suspendida' ? ' is-off' : (!$a ? ' is-empty' : '');
?>
  <article class="log-item<?= $cls ?>" id="<?= $a ? 'avance-' . (int)$a['id'] : 'sesion-' . (int)$s['id'] ?>">
    <div class="log-when">
      <div class="n">Clase <?= (int)$s['numero'] ?></div>
      <div class="d"><?= e(ucfirst(fecha_larga($s['fecha']))) ?></div>
    </div>
    <div class="log-body">
      <?php if ($s['estado'] === 'suspendida'): ?>
        <p>Clase suspendida<?= $s['tema'] ? ' · ' . e($s['tema']) : '' ?>. No requiere registro.</p>
      <?php elseif (!$a): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
          <p><?php
            if (!Calendar::isOpen($conv, $s)) echo 'Aún no inicia.';
            elseif (Calendar::isLate($conv, $s)) echo 'Sin registro. El plazo venció.';
            else echo 'Pendiente de registrar.';
          ?></p>
          <?php if (!empty($editLink) && Calendar::isOpen($conv, $s)): ?>
            <a class="btn btn-sm <?= Calendar::isLate($conv, $s) ? '' : 'btn-primary' ?>" href="<?= e($editLink($s)) ?>"><?= icon('upload', 14) ?> Registrar</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="log-meta">
          <span class="badge <?= $a['tardio'] ? 'badge-warn' : 'badge-ok' ?>"><?= $a['tardio'] ? 'Tardío' : 'A tiempo' ?></span>
          <span><?= e(tipo_label($a['tipo'])) ?></span>
          <span>· <?= e(fecha_hora($a['created_at'])) ?></span>
          <?php if ($a['enlace']): ?><a class="mono" href="<?= e($a['enlace']) ?>" target="_blank" rel="noopener"><?= icon('link', 14) ?> enlace</a><?php endif; ?>
          <?php if (!empty($editLink)): ?><a style="margin-left:auto" href="<?= e($editLink($s)) ?>"><?= icon('edit', 14) ?> Editar</a><?php endif; ?>
        </div>
        <?php if ($s['tema']): ?><p class="small muted">Tema: <?= e($s['tema']) ?></p><?php endif; ?>
        <p class="text"><?= e($a['actividad']) ?></p>
        <?php if ($a['capturas']): ?>
          <div class="shots">
            <?php foreach ($a['capturas'] as $c): $src = url('archivo', ['t' => 'avance', 'id' => $c['id']]); ?>
              <div class="shot"><a href="<?= e($src) ?>" target="_blank" rel="noopener"><img src="<?= e($src) ?>" alt="Evidencia de la clase <?= (int)$s['numero'] ?>" loading="lazy"></a></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($a['comentarios'] || $admin): ?>
          <div class="comments">
            <?php foreach ($a['comentarios'] as $c): ?>
              <div class="comment"><div class="who"><?= e($c['nombres'] . ' ' . $c['apellidos']) ?> · <?= e(fecha_hora($c['created_at'])) ?></div><?= nl2br(e($c['texto'])) ?></div>
            <?php endforeach; ?>
            <?php if ($admin): ?>
              <form method="post" action="<?= e(url('admin/comentar')) ?>" style="display:flex;gap:8px;align-items:flex-start">
                <?= csrf_field() ?>
                <input type="hidden" name="avance_id" value="<?= (int)$a['id'] ?>">
                <label class="sr-only" for="com-<?= (int)$a['id'] ?>">Comentario para el estudiante</label>
                <input class="input" id="com-<?= (int)$a['id'] ?>" name="texto" placeholder="Comentario para el estudiante" maxlength="2000">
                <button class="btn btn-sm" type="submit" style="min-height:42px">Comentar</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>
</div>
