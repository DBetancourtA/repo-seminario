<?php $fl = flashes(); if ($fl): ?>
<div class="flash-stack" role="status">
  <?php foreach ($fl as $f):
    $ic = ['ok' => 'check', 'warn' => 'alert', 'danger' => 'x', 'info' => 'clock'][$f['type']] ?? 'clock'; ?>
    <div class="flash flash-<?= e($f['type']) ?>"><?= icon($ic) ?><span><?= e($f['msg']) ?></span></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
