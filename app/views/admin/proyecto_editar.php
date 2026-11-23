<?php
$val = fn($c) => old($c, $valores[$c]);
?>
<div class="page-head">
  <div>
    <p class="eyebrow"><a href="<?= e(url('admin/proyectos')) ?>">Proyectos</a><span aria-hidden="true">/</span><a href="<?= e(url('admin/proyecto', ['id' => $p['id']])) ?>"><?= e($p['apellidos'] . ', ' . $p['nombres']) ?></a><span aria-hidden="true">/</span><span>Editar ficha</span></p>
    <h1>Editar ficha</h1>
    <p class="lead">Cada campo que cambies queda en el registro de cambios con su valor anterior. El estudiante lo ve en su ficha.</p>
  </div>
</div>

<?php if ($pend): ?>
  <div class="note note-accent" style="margin-bottom:24px"><strong>Hay <?= count($pend) ?> solicitud(es) del estudiante sin revisar</strong>Revísalas en la <a href="<?= e(url('admin/proyecto', ['id' => $p['id']])) ?>">ficha del proyecto</a> antes de editar esos campos, para no pisarlas.</div>
<?php endif; ?>

<div class="grid-2">
<form class="form" method="post" action="<?= e(url('admin/proyecto/editar', ['id' => $p['id']])) ?>" novalidate>
  <?= csrf_field() ?>
  <fieldset class="fieldset">
    <div class="legend-row"><legend>Estudiante</legend></div>
    <div class="row">
      <?php foreach (['nombres', 'apellidos'] as $c): ?>
        <div class="field">
          <label for="f-<?= $c ?>"><?= e(Cambios::label($c)) ?></label>
          <input class="input" id="f-<?= $c ?>" name="<?= $c ?>" value="<?= e($val($c)) ?>"<?= invalid($c) ?>>
          <?= field_error($c) ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="row-3">
      <div class="field">
        <label for="f-cedula">Cédula</label>
        <input class="input mono" id="f-cedula" name="cedula" inputmode="numeric" maxlength="10" value="<?= e($val('cedula')) ?>"<?= invalid('cedula') ?>>
        <?= field_error('cedula') ?>
      </div>
      <div class="field">
        <label for="f-email">Correo</label>
        <input class="input" id="f-email" name="email" type="email" value="<?= e($val('email')) ?>"<?= invalid('email') ?>>
        <?= field_error('email') ?>
      </div>
      <div class="field">
        <label for="f-telefono">Teléfono</label>
        <input class="input" id="f-telefono" name="telefono" type="tel" value="<?= e($val('telefono')) ?>"<?= invalid('telefono') ?>>
        <?= field_error('telefono') ?>
      </div>
    </div>
  </fieldset>

  <fieldset class="fieldset">
    <div class="legend-row"><legend>Proyecto</legend></div>
    <div class="field">
      <label for="f-titulo">Título <?= isset($pend['titulo']) ? '<span class="badge badge-accent">solicitud pendiente</span>' : '' ?></label>
      <input class="input" id="f-titulo" name="titulo" maxlength="200" value="<?= e($val('titulo')) ?>"<?= invalid('titulo') ?>>
      <?= field_error('titulo') ?>
    </div>
    <div class="field">
      <label for="f-descripcion">Descripción <?= isset($pend['descripcion']) ? '<span class="badge badge-accent">solicitud pendiente</span>' : '' ?></label>
      <textarea class="textarea" id="f-descripcion" name="descripcion" maxlength="1000" data-count data-max="1000"<?= invalid('descripcion') ?>><?= e($val('descripcion')) ?></textarea>
      <div class="counter" id="f-descripcion-count"></div>
      <?= field_error('descripcion') ?>
    </div>
    <div class="field">
      <label for="f-objetivo_general">Objetivo general <?= isset($pend['objetivo_general']) ? '<span class="badge badge-accent">solicitud pendiente</span>' : '' ?></label>
      <textarea class="textarea" id="f-objetivo_general" name="objetivo_general" rows="3" style="min-height:84px"><?= e($val('objetivo_general')) ?></textarea>
    </div>
    <div class="field">
      <label for="f-objetivos">Objetivos específicos <?= isset($pend['objetivos']) ? '<span class="badge badge-accent">solicitud pendiente</span>' : '' ?></label>
      <textarea class="textarea" id="f-objetivos" name="objetivos" rows="5" aria-describedby="obj-h"><?= e($val('objetivos')) ?></textarea>
      <p class="help" id="obj-h">Uno por línea, hasta 6.</p>
    </div>
    <div class="field">
      <label for="f-repo_url">Repositorio</label>
      <input class="input mono" id="f-repo_url" name="repo_url" type="url" value="<?= e($val('repo_url')) ?>"<?= invalid('repo_url') ?>>
      <?= field_error('repo_url') ?>
    </div>
  </fieldset>

  <fieldset class="fieldset">
    <div class="legend-row"><legend>Nota del cambio <span class="opt" style="font-weight:400">(opcional)</span></legend><p>Aparece junto a cada campo modificado en el registro.</p></div>
    <label class="sr-only" for="nota">Nota</label>
    <input class="input" id="nota" name="nota" maxlength="500" placeholder="Ej.: corrección de ortografía acordada en clase" value="<?= e(old('nota')) ?>">
  </fieldset>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Guardar cambios</button>
    <a class="btn btn-ghost" href="<?= e(url('admin/proyecto', ['id' => $p['id']])) ?>">Cancelar</a>
  </div>
</form>

<aside>
  <div class="section-head"><h2>Capturas</h2><span class="meta"><?= count($capturas) ?></span></div>
  <?php if (!$capturas): ?><p class="muted small">Sin capturas.</p><?php else: ?>
  <div class="shots">
    <?php foreach ($capturas as $c): $src = url('archivo', ['t' => 'proyecto', 'id' => $c['id']]); ?>
      <div class="shot">
        <a href="<?= e($src) ?>" target="_blank" rel="noopener"><img src="<?= e($src) ?>" alt="Captura del proyecto" loading="lazy"></a>
        <form method="post" action="<?= e(url('admin/captura/borrar')) ?>" data-confirm="¿Eliminar esta captura? Quedará en el registro de cambios.">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <button class="linkbtn" type="submit" aria-label="Eliminar captura"><?= icon('x', 16) ?></button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</aside>
</div>
