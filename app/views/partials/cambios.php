<?php
/** @var array $cambios */
$estadoTxt = [
    'aplicado'  => ['', ''],
    'pendiente' => ['badge-accent', 'Pendiente'],
    'aceptado'  => ['badge-ok', 'Aceptado'],
    'rechazado' => ['badge-danger', 'Rechazado'],
];
$quien = function (array $c): string {
    return match ($c['tipo']) {
        'docente'    => 'Editado por el docente',
        'completado' => 'Completado por el estudiante',
        default      => 'Solicitud del estudiante',
    };
};
?>
<?php if (!$cambios): ?>
  <p class="muted small">Sin cambios desde la aprobación.</p>
<?php else: ?>
<ol class="changes">
  <?php foreach ($cambios as $c): [$cls, $txt] = $estadoTxt[$c['estado']] ?? ['', '']; ?>
    <li>
      <div class="change-head">
        <span class="change-field"><?= e(Cambios::label($c['campo'])) ?></span>
        <?php if ($txt): ?><span class="badge <?= $cls ?>"><?= e($txt) ?></span><?php endif; ?>
        <span class="when"><?= e($quien($c)) ?> · <?= e(fecha_hora($c['created_at'])) ?></span>
      </div>
      <?php if ($c['campo'] === 'capturas'): ?>
        <p class="small"><?= e($c['valor_nuevo']) ?></p>
      <?php else: ?>
        <div class="diff">
          <div class="diff-old"><span class="diff-tag">Antes</span><?= $c['valor_anterior'] !== null && $c['valor_anterior'] !== '' ? nl2br(e($c['valor_anterior'])) : '<em class="muted">vacío</em>' ?></div>
          <div class="diff-new"><span class="diff-tag">Después</span><?= $c['valor_nuevo'] !== null && $c['valor_nuevo'] !== '' ? nl2br(e($c['valor_nuevo'])) : '<em class="muted">vacío</em>' ?></div>
        </div>
      <?php endif; ?>
      <?php if ($c['motivo']): ?><p class="small"><span class="muted">Motivo:</span> <?= e($c['motivo']) ?></p><?php endif; ?>
      <?php if ($c['comentario_revision']): ?><p class="small"><span class="muted">Docente:</span> <?= e($c['comentario_revision']) ?></p><?php endif; ?>
    </li>
  <?php endforeach; ?>
</ol>
<?php endif; ?>
