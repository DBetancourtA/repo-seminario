<ol class="timeline">
  <?php foreach ($historial as $h): ?>
    <li>
      <div class="when"><?= e(fecha_hora($h['created_at'])) ?><?= $h['rol'] === 'admin' ? ' · docente' : '' ?></div>
      <div class="what"><?= e(estado_label($h['hacia'])) ?></div>
      <?php if ($h['comentario']): ?><div class="why"><?= e($h['comentario']) ?></div><?php endif; ?>
    </li>
  <?php endforeach; ?>
</ol>
