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
  <div class="note" style="margin-bottom:24px"><strong>Ficha cerrada</strong>Desde la semana <?= (int)$conv['semana_congelamiento'] ?> solo el docente puede modificar la ficha del proyecto; si necesitas una corrección, pídesela en clase. La excepción es el <a href="#repositorio">enlace del repositorio</a>, que puedes actualizar cuando quieras.</div>
<?php endif; ?>

<div class="grid-2">
  <section aria-labelledby="ficha">
    <div class="section-head"><h2 id="ficha">Ficha</h2></div>
    <?php partial('ficha', compact('p', 'objetivos', 'capturas', 'tecs')); ?>

    <?php if (Cambios::repoEditable($p)): ?>
    <div class="section" id="repositorio">
      <div class="section-head"><h2>Repositorio del proyecto</h2><span class="meta">Puedes actualizarlo en cualquier momento</span></div>
      <form method="post" action="<?= e(url('proyecto/repositorio')) ?>" class="form" style="gap:8px" novalidate>
        <?= csrf_field() ?>
        <label class="sr-only" for="repo_url">Enlace del repositorio</label>
        <div class="repo-inline">
          <input class="input mono" id="repo_url" name="repo_url" type="url" placeholder="https://github.com/usuario/proyecto" value="<?= e(old('repo_url', $p['repo_url'] ?? '')) ?>"<?= invalid('repo_url') ?>>
          <button class="btn" type="submit">Guardar enlace</button>
        </div>
        <?= field_error('repo_url') ?>
        <?php partial('repo_hint', ['for' => 'repo_url']); ?>
      </form>
    </div>
    <?php endif; ?>

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
