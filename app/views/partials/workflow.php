<?php
/** @var array|null $p  @var array $conv  @var int $actual  @var bool $compact */
$estado = $p['estado'] ?? null;
[$cur, $tone] = $estado ? ProjectState::stageOf($estado) : [1, 'current'];
$sub = [
    1 => ['done' => 'Enviado', 'current' => 'Por registrar'],
    2 => ['done' => 'Revisado', 'current' => 'Esperando al docente', 'warn' => 'Con observaciones', 'danger' => 'Rechazado'],
    3 => ['done' => 'Aprobado'],
    4 => ['done' => 'Definidas', 'current' => 'Te toca definirlas'],
    5 => ['done' => 'Completado', 'current' => $estado === 'tecnologias_definidas' ? 'Empieza en la semana 2' : 'Semana ' . max(1, (int)($actual ?? 1)) . ' de ' . (int)$conv['semanas']],
    6 => ['done' => 'Completado'],
];
?>
<section class="flow" aria-labelledby="flow-title">
  <div class="flow-head">
    <h2 id="flow-title">Estado del trabajo de titulación</h2>
    <?php if ($estado): ?>
      <span class="badge badge-<?= e(['ok' => 'ok', 'warn' => 'warn', 'danger' => 'danger', 'neutral' => 'accent'][estado_tono($estado)]) ?>"><?= e(estado_label($estado)) ?></span>
    <?php endif; ?>
  </div>
  <ol>
    <?php foreach (ProjectState::STAGES as $i => $name):
      if ($i < $cur) { $cls = 'done'; }
      elseif ($i === $cur) { $cls = $tone; }
      else { $cls = 'pending'; }
      $label = $sub[$i][$cls] ?? '';
      if ($cls === 'pending') $label = 'Pendiente';
      $aria = $cls === 'done' ? 'completada' : ($cls === 'pending' ? 'pendiente' : 'actual');
    ?>
      <li class="<?= e($cls) ?>"<?= in_array($cls, ['current', 'warn', 'danger'], true) ? ' aria-current="step"' : '' ?>>
        <span class="dot" aria-hidden="true"><?= $cls === 'done' ? icon('check', 14) : ($cls === 'warn' ? '!' : ($cls === 'danger' ? icon('x', 14) : $i)) ?></span>
        <span class="step-name"><?= e($name) ?><span class="sr-only"> — etapa <?= $aria ?></span></span>
        <span class="step-state"><?= e($label) ?></span>
      </li>
    <?php endforeach; ?>
  </ol>
  <?php $curName = ProjectState::STAGES[$cur]; $curLabel = $sub[$cur][$tone] ?? ''; ?>
  <p class="flow-mobile" aria-hidden="true">Etapa <?= $cur ?> de 6 · <b><?= e($curName) ?></b><?= $curLabel ? ' — ' . e($curLabel) : '' ?></p>
  <?php if (empty($compact)): ?>
    <?php if ($estado === 'observado'): ?>
      <div class="note note-warn flow-note"><strong>Observaciones del docente</strong><span style="white-space:pre-line"><?= e($p['observacion']) ?></span>
        <p style="margin-top:8px"><a href="<?= e(url('proyecto')) ?>">Corregir y reenviar el proyecto</a></p></div>
    <?php elseif ($estado === 'rechazado'): ?>
      <div class="note note-danger flow-note"><strong>El proyecto no fue aprobado</strong><span style="white-space:pre-line"><?= e($p['observacion']) ?></span>
        <p style="margin-top:8px"><a href="<?= e(url('proyecto', ['nuevo' => 1])) ?>">Registrar una nueva propuesta</a></p></div>
    <?php elseif ($estado === 'aprobado'): ?>
      <div class="note note-accent flow-note"><strong>Proyecto aprobado</strong>Define el lenguaje, la base de datos y la plataforma que usarás. <a href="<?= e(url('tecnologias')) ?>">Definir tecnologías</a></div>
    <?php elseif ($estado === 'en_revision'): ?>
      <p class="help flow-note">Tu docente revisará la propuesta. Mientras tanto puedes editarla y registrar los avances de la semana 1.</p>
    <?php elseif (!$estado): ?>
      <div class="note note-accent flow-note"><strong>Primer paso</strong>Registra tu proyecto de titulación. <a href="<?= e(url('proyecto')) ?>">Registrar proyecto</a></div>
    <?php endif; ?>
  <?php endif; ?>
</section>
