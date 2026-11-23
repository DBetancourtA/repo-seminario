<?php
$req = ['lenguaje' => 'obligatorio', 'base_datos' => 'obligatorio', 'plataforma' => 'obligatorio'];
$err = errors();
?>
<div class="page-head">
  <div>
    <p class="eyebrow"><a href="<?= e(url('panel')) ?>">Mi seminario</a><span aria-hidden="true">/</span><span>Tecnologías</span></p>
    <h1>¿Con qué vas a construirlo?</h1>
    <p class="lead">Marca todo lo que usarás. Elige al menos un lenguaje, una base de datos y el tipo de plataforma. Puedes ajustarlo más adelante.</p>
  </div>
</div>

<form class="form" method="post" action="<?= e(url('tecnologias')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="field tech-search">
    <label for="tech-search">Buscar en el catálogo</label>
    <input class="input" id="tech-search" type="search" autocomplete="off" placeholder="Ej.: Laravel, Postgre, Flutter">
  </div>

  <div class="tech-pick">
    <?php foreach ($porCat as $cat => $list): if (!$list) continue; ?>
      <fieldset class="fieldset tech-cat">
        <legend class="sr-only"><?= e(categoria_label($cat)) ?></legend>
        <h3 aria-hidden="true"><?= e(categoria_label($cat)) ?> <?php if (isset($req[$cat])): ?><small><?= $req[$cat] ?></small><?php endif; ?></h3>
        <div class="tech-options">
          <?php foreach ($list as $t): ?>
            <label><input type="checkbox" name="tec[]" value="<?= (int)$t['id'] ?>"<?= in_array((int)$t['id'], $sel, true) ? ' checked' : '' ?>><?= e($t['nombre']) ?><?= $t['aprobada'] ? '' : ' <span class="small" style="opacity:.7">(propuesta)</span>' ?></label>
          <?php endforeach; ?>
        </div>
        <?php if (isset($err[$cat])): ?><p class="field-error"><?= e($err[$cat]) ?></p><?php endif; ?>
      </fieldset>
    <?php endforeach; ?>
  </div>

  <fieldset class="fieldset">
    <div class="legend-row"><legend>¿No está en la lista?</legend><p>Propón la tecnología; queda asociada a tu proyecto y el docente la agrega al catálogo.</p></div>
    <div class="row">
      <div class="field">
        <label for="nueva_nombre">Nombre</label>
        <input class="input" id="nueva_nombre" name="nueva_nombre" maxlength="80">
      </div>
      <div class="field">
        <label for="nueva_categoria">Categoría</label>
        <select class="select" id="nueva_categoria" name="nueva_categoria"<?= invalid('nueva') ?>>
          <option value="">Elige…</option>
          <?php foreach (categorias() as $c): ?><option value="<?= e($c) ?>"><?= e(categoria_label($c)) ?></option><?php endforeach; ?>
        </select>
        <?= field_error('nueva') ?>
      </div>
    </div>
  </fieldset>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit">Guardar tecnologías</button>
    <a class="btn btn-ghost" href="<?= e(url('panel')) ?>">Cancelar</a>
  </div>
</form>
