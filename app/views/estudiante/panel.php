<?php
$hoy = null;
foreach ($weeks as $ss) {
    foreach ($ss as $s) {
        if ($s['fecha'] === date('Y-m-d') && $s['estado'] === 'normal') { $hoy = $s; }
    }
}
$puedeRegistrar = $p && ProjectState::canLogProgress($p['estado']);
$porCat = [];
foreach ($tecs as $t) { $porCat[$t['categoria']][] = $t; }
?>
<div class="page-head">
  <div>
    <p class="eyebrow"><span><?= e($conv['nombre']) ?></span><span aria-hidden="true">·</span><span><?= e($conv['periodo']) ?></span></p>
    <h1><?= $p ? e($p['titulo']) : 'Mi trabajo de titulación' ?></h1>
    <p class="lead">Clases <?= e(Calendar::diasLabel($conv['dias_clase'])) ?> de <?= e(substr($conv['hora_inicio'], 0, 5)) ?> a <?= e(substr($conv['hora_fin'], 0, 5)) ?>. Registra cada clase hasta <?= (int)$conv['ventana_horas'] ?> h después de que termine.</p>
  </div>
  <div class="actions">
    <?php if ($hoy && $puedeRegistrar && Calendar::isOpen($conv, $hoy)): ?>
      <a class="btn btn-primary" href="<?= e(url('avance', ['sesion' => $hoy['id']])) ?>"><?= icon('upload', 16) ?> <?= isset($avances[(int)$hoy['id']]) ? 'Editar avance de hoy' : 'Registrar la clase de hoy' ?></a>
    <?php elseif (!$p): ?>
      <a class="btn btn-primary" href="<?= e(url('proyecto')) ?>"><?= icon('plus', 16) ?> Registrar proyecto</a>
    <?php endif; ?>
  </div>
</div>

<?php partial('workflow', ['p' => $p, 'conv' => $conv, 'actual' => $actual]); ?>

<section class="section" style="margin-top:0" aria-labelledby="bitacora">
  <div class="section-head">
    <h2 id="bitacora">Bitácora del seminario</h2>
    <span class="meta"><?= count($weeks) ?> semanas · <?= count($avances) ?> clases registradas</span>
  </div>
  <?php if (!$weeks): ?>
    <div class="panel empty"><strong>El calendario aún no está listo</strong>Tu docente todavía no publica las sesiones.</div>
  <?php else:
    partial('weeks', [
      'weeks' => $weeks, 'avances' => $avances, 'conv' => $conv, 'actual' => $actual,
      'cellLink' => fn($s, $st) => ($puedeRegistrar && in_array($st, ['open', 'missing', 'done', 'late'], true)) ? url('avance', ['sesion' => $s['id']]) : null,
      'weekLink' => fn($w) => url('semana', ['n' => $w]),
    ]);
  endif; ?>
</section>

<?php if ($p): ?>
<section class="section" aria-labelledby="resumen">
  <div class="section-head">
    <h2 id="resumen">Proyecto</h2>
    <a class="small" href="<?= e(url('proyecto')) ?>"><?= ProjectState::editableByStudent($p['estado']) ? 'Editar' : 'Ver ficha completa' ?></a>
  </div>
  <dl class="dl">
    <dt>Descripción</dt>
    <dd><?= $p['descripcion'] ? nl2br(e($p['descripcion'])) : '<span class="muted">Sin descripción.</span>' ?></dd>
    <?php if ($p['objetivo_general']): ?><dt>Objetivo general</dt><dd><?= nl2br(e($p['objetivo_general'])) ?></dd><?php endif; ?>
    <dt>Tecnologías</dt>
    <dd>
      <?php if ($tecs): ?>
        <div class="tech-groups">
          <?php foreach ($porCat as $cat => $list): ?>
            <div class="tech-group"><span><?= e(categoria_label($cat)) ?></span><div class="chips"><?php foreach ($list as $t): ?><span class="chip<?= $t['aprobada'] ? '' : ' pending' ?>"><?= e($t['nombre']) ?></span><?php endforeach; ?></div></div>
          <?php endforeach; ?>
        </div>
        <?php if (ProjectState::techEditable($p['estado'])): ?><p style="margin-top:8px"><a class="small" href="<?= e(url('tecnologias')) ?>">Cambiar tecnologías</a></p><?php endif; ?>
      <?php elseif (ProjectState::techEditable($p['estado'])): ?>
        <a href="<?= e(url('tecnologias')) ?>">Definir tecnologías</a>
      <?php else: ?>
        <span class="muted">Se definen cuando el proyecto sea aprobado.</span>
      <?php endif; ?>
    </dd>
    <?php if ($p['repo_url']): ?><dt>Repositorio</dt><dd><a class="mono small" href="<?= e($p['repo_url']) ?>" target="_blank" rel="noopener"><?= e($p['repo_url']) ?></a></dd><?php endif; ?>
  </dl>
</section>
<?php endif; ?>
