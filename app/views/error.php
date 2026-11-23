<div class="page-head">
  <div>
    <p class="eyebrow">Error <?= (int)$codigo ?></p>
    <h1><?= e($titulo) ?></h1>
    <?php if ($mensaje): ?><p class="lead"><?= e($mensaje) ?></p><?php endif; ?>
  </div>
</div>
<a class="btn" href="<?= e(url('')) ?>"><?= icon('back') ?> Volver al inicio</a>
