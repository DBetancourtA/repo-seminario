<?php $min = $u['rol'] === 'admin' ? 10 : 8; ?>
<div class="page-head">
  <div>
    <p class="eyebrow">Mi cuenta</p>
    <h1>Cambiar contraseña</h1>
    <p class="lead">Si el docente te dio una contraseña temporal, escríbela como contraseña actual y elige una nueva.</p>
  </div>
</div>
<form class="form panel panel-pad" style="max-width:480px" method="post" action="<?= e(url('cuenta/clave')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="field">
    <label for="actual">Contraseña actual</label>
    <input class="input" id="actual" name="actual" type="password" autocomplete="current-password" required<?= invalid('actual') ?>>
    <?= field_error('actual') ?>
  </div>
  <div class="field">
    <label for="nueva">Nueva contraseña</label>
    <input class="input" id="nueva" name="nueva" type="password" autocomplete="new-password" minlength="<?= $min ?>" required aria-describedby="nueva-help"<?= invalid('nueva') ?>>
    <p class="help" id="nueva-help">Mínimo <?= $min ?> caracteres.</p>
    <?= field_error('nueva') ?>
  </div>
  <div class="field">
    <label for="nueva2">Repite la nueva contraseña</label>
    <input class="input" id="nueva2" name="nueva2" type="password" autocomplete="new-password" required<?= invalid('nueva2') ?>>
    <?= field_error('nueva2') ?>
  </div>
  <div class="actions">
    <button class="btn btn-primary" type="submit">Guardar contraseña</button>
    <a class="btn btn-ghost" href="<?= e(url('')) ?>">Cancelar</a>
  </div>
</form>
